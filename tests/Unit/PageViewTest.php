<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

final class PageViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hashing.anonymizer_salt' => 'testing-salt']);
    }

    public function test_from_request_normalizes_paths_and_referrers(): void
    {
        $request = Request::create(
            'https://blog.local/posts/example',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_REFERER' => 'https://example.com/articles/123',
                'HTTP_USER_AGENT' => 'Googlebot',
                'REMOTE_ADDR' => '10.0.0.5',
            ]
        );

        $view = PageView::fromRequest($request);

        $this->assertSame('/posts/example', $view->page);
        $this->assertSame('example.com', $view->referrer);
        $this->assertSame('Googlebot', $view->user_agent);
        $this->assertSame(40, mb_strlen($view->anonymous_id));
    }

    public function test_from_request_handles_ref_query_parameter(): void
    {
        $request = Request::create(
            'https://blog.local/',
            'GET',
            ['ref' => 'newsletter.example'],
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'REMOTE_ADDR' => '10.0.0.10',
            ]
        );

        $view = PageView::fromRequest($request);

        $this->assertSame('?ref=newsletter.example', $view->referrer);
        $this->assertNull($view->user_agent);
    }
}

