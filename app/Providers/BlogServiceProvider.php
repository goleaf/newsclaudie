<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Torchlight\Commonmark\V2\TorchlightExtension;

/**
 * The Blog Kit Service Provider.
 */
final class BlogServiceProvider extends ServiceProvider
{
    /**
     * Uses Semantic Versioning.
     *
     * @see https://semver.org/
     */
    public const BLOGKIT_VERSION = '1.1.0-Dev';

    public function register(): void
    {
        if (! config('blog.torchlight.enabled')) {
            return;
        }

        $this->callAfterResolving('markdown.environment', static function ($environment): void {
            $environment->addExtension(new TorchlightExtension());
        });
    }

    public function boot(): void
    {
        //
    }
}
