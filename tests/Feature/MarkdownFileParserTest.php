<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\MarkdownFileParser;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MarkdownFileParserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private array $tempMarkdownFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempMarkdownFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_it_resolves_the_absolute_markdown_path(): void
    {
        $path = MarkdownFileParser::getQualifiedFilepath('example-post');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('resources/markdown/example-post.md', $path);
    }

    public function test_it_syncs_a_markdown_file_into_the_database(): void
    {
        User::factory()->create([
            'id' => 1,
            'is_author' => true,
        ]);

        $message = MarkdownFileParser::sync('example-post');

        $this->assertStringStartsWith('Synced 1 posts in', $message);

        $post = Post::withoutGlobalScopes()->where('slug', 'example-post')->first();

        $this->assertNotNull($post);
        $this->assertSame('New feature: Write posts directly with Markdown files!', $post->title);
        $this->assertSame(['tags', 'are separated', 'with commas'], $post->tags);
        $this->assertNotNull($post->published_at);
    }

    public function test_it_throws_when_author_does_not_exist(): void
    {
        $this->createMarkdownFixture('invalid-author', [
            'title' => 'Missing Author',
            'description' => 'Fixture ensuring the author must exist',
            'published' => '2024-10-10 10:00',
            'tags' => 'investigation',
            'featured_image' => 'https://example.com/cover.jpg',
            'author' => 999,
        ]);

        $this->expectExceptionMessage('The selected author is invalid.');

        (new MarkdownFileParser())->parse('invalid-author');
    }

    public function test_it_rejects_invalid_publish_dates(): void
    {
        User::factory()->create([
            'id' => 7,
            'is_author' => true,
        ]);

        $this->createMarkdownFixture('invalid-date', [
            'title' => 'Broken Publish Date',
            'description' => 'Fixture with malformed timestamp',
            'published' => 'not-a-date',
            'tags' => 'ops',
            'featured_image' => 'https://example.com/cover.jpg',
            'author' => 7,
        ]);

        $this->expectExceptionMessage('The published does not match the format Y-m-d H:i.');

        (new MarkdownFileParser())->parse('invalid-date');
    }

    private function createMarkdownFixture(string $slug, array $frontMatter, string $body = "## Test Body\n\nContent."): void
    {
        $lines = ['---'];

        foreach ($frontMatter as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_numeric($value)) {
                $value = (string) $value;
            } else {
                $escaped = str_replace('"', '\"', (string) $value);
                $value = '"'.$escaped.'"';
            }

            $lines[] = "{$key}: {$value}";
        }

        $lines[] = '---';

        $content = implode("\n", $lines)."\n\n{$body}\n";
        $path = resource_path("markdown/{$slug}.md");

        file_put_contents($path, $content);

        $this->tempMarkdownFiles[] = $path;
    }
}
