<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\RunPostExport;
use App\Models\Category;
use App\Models\DataExport;
use App\Models\Post;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use App\Support\Exports\PostExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PostExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_filtered_posts_as_csv(): void
    {
        Storage::fake('local');
        Config::set('exports.disk', 'local');
        Config::set('exports.directory', 'exports');
        Config::set('exports.max_sync_rows', 500);

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create(['slug' => 'news']);

        $posts = Post::factory()->count(2)->create();
        $posts->each(static fn (Post $post) => $post->categories()->attach($category));

        Notification::fake();

        $response = $this->actingAs($admin)->post(route('admin.posts.export'), [
            'format' => 'csv',
            'category' => 'news',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('export_url');

        $export = DataExport::query()->latest('id')->first();
        $this->assertNotNull($export);

        Storage::disk('local')->assertExists($export->path);

        $downloadUrl = $response->getSession()->get('export_url');
        $downloadResponse = $this->actingAs($admin)->get($downloadUrl);

        $downloadResponse->assertOk();
        $this->assertStringContainsString('.csv', (string) $downloadResponse->headers->get('content-disposition'));

        $contents = Storage::disk('local')->get($export->path);
        $this->assertStringContainsString('news', $contents);
        $this->assertStringContainsString($posts->first()->title, $contents);

        Notification::assertNothingSent();
    }

    public function test_large_exports_are_processed_in_background_and_notify(): void
    {
        Storage::fake('local');
        Config::set('exports.disk', 'local');
        Config::set('exports.directory', 'exports');
        Config::set('exports.max_sync_rows', 1);

        $admin = User::factory()->create(['is_admin' => true]);
        Post::factory()->count(3)->create();

        Queue::fake();
        Notification::fake();

        $response = $this->actingAs($admin)->post(route('admin.posts.export'), [
            'format' => 'json',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('export_status');

        Queue::assertPushed(RunPostExport::class);

        $export = DataExport::query()->latest('id')->firstOrFail();

        // Run the queued job manually to verify completion + notification.
        (new RunPostExport($export))->handle(new PostExporter());

        $export->refresh();

        $this->assertEquals(DataExport::STATUS_COMPLETED, $export->status);
        Storage::disk('local')->assertExists($export->path);

        Notification::assertSentTo(
            $admin,
            ExportReadyNotification::class,
            static fn (ExportReadyNotification $notification): bool => $notification->toArray($admin)['export_id'] === $export->id,
        );
    }
}
