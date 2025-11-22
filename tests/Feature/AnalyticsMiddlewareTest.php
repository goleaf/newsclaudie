<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\AnalyticsMiddleware;
use App\Models\PageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionProperty;
use Tests\TestCase;

final class AnalyticsMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private string $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnv = $this->app['env'];

        config([
            'hashing.anonymizer_salt' => 'testing-salt',
        ]);
    }

    protected function tearDown(): void
    {
        $this->app['env'] = $this->originalEnv;

        parent::tearDown();
    }

    public function test_it_records_page_view_when_enabled(): void
    {
        $this->app['env'] = 'production';

        config([
            'analytics.enabled' => true,
            'analytics.excluded_paths' => [],
        ]);

        $middleware = new AnalyticsMiddleware();

        $request = Request::create(
            '/articles/deep-dive',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_REFERER' => 'https://newsletter.example/posts/1',
                'HTTP_USER_AGENT' => 'Googlebot',
                'REMOTE_ADDR' => '10.0.0.20',
            ]
        );

        $this->runNewTerminatingCallbacks(function () use ($middleware, $request): void {
            $middleware->handle($request, static fn () => response('ok'));
        });

        $this->assertDatabaseCount('page_views', 1);

        $view = PageView::firstOrFail();
        $this->assertSame('/articles/deep-dive', $view->page);
        $this->assertSame('newsletter.example', $view->referrer);
        $this->assertSame('Googlebot', $view->user_agent);
    }

    public function test_it_skips_tracking_for_excluded_paths(): void
    {
        $this->app['env'] = 'production';

        config([
            'analytics.enabled' => true,
            'analytics.excluded_paths' => ['admin/*'],
        ]);

        $middleware = new AnalyticsMiddleware();

        $request = Request::create('/admin/panel', 'GET');

        $this->runNewTerminatingCallbacks(function () use ($middleware, $request): void {
            $middleware->handle($request, static fn () => response('ok'));
        });

        $this->assertDatabaseCount('page_views', 0);
    }

    /**
     * Run and clear any terminating callbacks registered during the operation.
     */
    private function runNewTerminatingCallbacks(callable $operation): void
    {
        $property = new ReflectionProperty($this->app, 'terminatingCallbacks');
        $property->setAccessible(true);

        /** @var array<int, callable> $before */
        $before = $property->getValue($this->app);

        $operation();

        /** @var array<int, callable> $after */
        $after = $property->getValue($this->app);

        $newCallbacks = array_slice($after, count($before));

        foreach ($newCallbacks as $callback) {
            $this->app->call($callback);
        }

        $property->setValue($this->app, $before);
    }
}



