<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\MarkdownConverter;
use GrahamCampbell\Markdown\Facades\Markdown;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Output\RenderedContentInterface;
use Tests\TestCase;

final class MarkdownConverterTest extends TestCase
{
    public function test_it_converts_markdown_without_torchlight(): void
    {
        config([
            'blog.torchlight.enabled' => false,
            'blog.torchlight.attribution' => false,
        ]);

        Markdown::shouldReceive('convertToHtml')
            ->once()
            ->with('**Hello**')
            ->andReturn($this->fakeRenderedContent('<p><strong>Hello</strong></p>'));

        $converter = new MarkdownConverter('**Hello**');

        $this->assertSame('<p><strong>Hello</strong></p>', trim($converter->toHtml()));
    }

    public function test_it_injects_torchlight_markup_and_attribution(): void
    {
        config([
            'blog.torchlight.enabled' => true,
            'blog.torchlight.attribution' => true,
        ]);

        $html = "<pre>\n<!-- Syntax highlighted by torchlight.dev -->\ncode\n</pre>";

        Markdown::shouldReceive('convertToHtml')
            ->once()
            ->andReturn($this->fakeRenderedContent($html));

        $converter = new MarkdownConverter('```php echo 1; ```');
        $rendered = $converter->toHtml();

        $this->assertStringContainsString('<pre class="torchlight">', $rendered);
        $this->assertStringContainsString('torchlight.dev', $rendered);
    }

    public function test_torchlight_markup_is_ignored_when_disabled(): void
    {
        config([
            'blog.torchlight.enabled' => false,
            'blog.torchlight.attribution' => true,
        ]);

        $html = "<pre>\n<!-- Syntax highlighted by torchlight.dev -->\ncode\n</pre>";

        Markdown::shouldReceive('convertToHtml')
            ->once()
            ->andReturn($this->fakeRenderedContent($html));

        $converter = new MarkdownConverter('```php echo 1; ```');
        $rendered = $converter->toHtml();

        $this->assertStringNotContainsString('class="torchlight"', $rendered);
        $this->assertStringNotContainsString('Syntax highlighting provided by', $rendered);
    }

    private function fakeRenderedContent(string $html): RenderedContentInterface
    {
        return new class($html) implements RenderedContentInterface
        {
            public function __construct(private string $html) {}

            public function __toString(): string
            {
                return $this->html;
            }

            public function getDocument(): Document
            {
                return new Document();
            }

            public function getContent(): string
            {
                return $this->html;
            }
        };
    }
}
