<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LocaleControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_updates_the_locale_using_the_picker(): void
    {
        $response = $this->from('/')->post(route('locale.update'), [
            'locale' => 'es',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('locale', 'es');
        $this->assertSame('es', app()->getLocale());
    }

    /** @test */
    public function it_rejects_locales_that_are_not_supported(): void
    {
        $response = $this->post(route('locale.update'), [
            'locale' => 'zz',
        ]);

        $response->assertSessionHasErrors('locale');
        $this->assertSame(config('app.fallback_locale'), session('locale', config('app.locale')));
    }

    public function test_it_honors_supported_locales_configuration(): void
    {
        config(['app.supported_locales' => ['en', 'fr']]);

        $response = $this->from('/settings')->post(route('locale.update'), [
            'locale' => 'fr',
        ]);

        $response->assertRedirect('/settings');
        $this->assertSame('fr', session('locale'));
        $this->assertSame('fr', app()->getLocale());
    }

    public function test_it_uses_translated_error_message_for_invalid_locale(): void
    {
        config(['app.supported_locales' => ['en', 'fr']]);

        $response = $this->post(route('locale.update'), [
            'locale' => 'de',
        ]);

        $response->assertSessionHasErrors('locale');

        $errors = session('errors')->get('locale');

        $this->assertContains(__('validation.locale_invalid'), $errors);
    }
}
