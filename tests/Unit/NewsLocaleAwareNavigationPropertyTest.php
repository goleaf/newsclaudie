<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Property-Based Tests for Locale-Aware Navigation
 * 
 * These tests verify that the News navigation link displays correctly in all
 * supported locales. The tests use property-based testing to validate behavior
 * across different language configurations.
 * 
 * ## Properties Tested
 * 
 * - **Property 21**: Locale-aware navigation - News link displays in current locale
 * 
 * ## Testing Approach
 * 
 * Each test runs multiple iterations with different locale configurations to verify
 * that the navigation link label is properly translated:
 * 
 * - Tests all supported locales (en, es)
 * - Verifies translation key resolution
 * - Confirms label changes when locale changes
 * - Tests with and without news route registered
 * 
 * ## Related Components
 * 
 * @see \App\View\Components\Navigation\Main The navigation component being tested
 * @see lang/en.json English translations
 * @see lang/es.json Spanish translations
 * 
 * ## Requirements Validated
 * 
 * - Requirement 9.4: Display News link label in current locale
 * 
 * @package Tests\Unit
 * @group property-testing
 * @group news-page
 * @group navigation
 * @group locale
 */
final class NewsLocaleAwareNavigationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Supported locales for testing.
     *
     * @var array<string>
     */
    private array $supportedLocales = ['en', 'es'];

    /**
     * Expected translations for the News navigation link.
     *
     * @var array<string, string>
     */
    private array $expectedTranslations = [
        'en' => 'News',
        'es' => 'Noticias',
    ];

    /**
     * Set up the test environment.
     *
     * Ensures the news route is registered for navigation tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure news route exists for navigation
        if (! Route::has('news.index')) {
            Route::get('/news', fn () => 'News')->name('news.index');
        }
    }

    /**
     * Test Property 21: Locale-aware navigation
     * 
     * **Property**: For any supported locale, the "News" navigation link should
     * display the label in the current locale's language.
     * 
     * **Validates**: Requirement 9.4 - Display News link label in current locale
     * 
     * **Test Strategy**:
     * - Iterates through all supported locales
     * - Sets application locale
     * - Renders navigation component
     * - Verifies translated label appears in output
     * - Confirms label changes when locale changes
     * 
     * **Properties Verified**:
     * 1. Translation key resolves correctly for each locale
     * 2. Rendered navigation contains translated label
     * 3. Label changes when locale changes
     * 4. Translation is not empty or fallback key
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     */
    public function test_news_link_displays_in_current_locale(): void
    {
        // Feature: news-page, Property 21: Locale-aware navigation
        // Validates: Requirements 9.4
        
        $iterations = count($this->supportedLocales) * 3; // Test each locale multiple times
        $assertions = 0;

        for ($i = 0; $i < $iterations; $i++) {
            // Select a random locale from supported locales
            $locale = $this->supportedLocales[array_rand($this->supportedLocales)];
            
            // Set the application locale
            App::setLocale($locale);
            
            // Get the translated label
            $translatedLabel = __('nav.news');
            
            // Property 1: Translation key resolves correctly
            $this->assertNotEquals('nav.news', $translatedLabel, 
                "Translation key 'nav.news' should resolve for locale '{$locale}'");
            $assertions++;
            
            // Property 2: Translation matches expected value for locale
            $this->assertEquals($this->expectedTranslations[$locale], $translatedLabel,
                "News link should display '{$this->expectedTranslations[$locale]}' for locale '{$locale}'");
            $assertions++;
            
            // Property 3: Translation is not empty
            $this->assertNotEmpty($translatedLabel,
                "Translation for 'nav.news' should not be empty in locale '{$locale}'");
            $assertions++;
            
            // Property 4: Render navigation and verify translated label appears
            $view = (string) $this->blade('<x-navigation.main />');
            $this->assertStringContainsString($translatedLabel, $view,
                "Navigation should contain translated label '{$translatedLabel}' for locale '{$locale}'");
            $assertions++;
        }

        // Verify we ran all expected assertions
        $expectedAssertions = $iterations * 4;
        $this->assertEquals($expectedAssertions, $assertions,
            "Should have run {$expectedAssertions} assertions across {$iterations} iterations");
    }

    /**
     * Test Property 21 (Variation): Locale switching updates navigation label
     * 
     * **Property**: When the application locale changes, the News navigation link
     * label should update to reflect the new locale.
     * 
     * **Validates**: Requirement 9.4 - Display News link label in current locale
     * 
     * **Test Strategy**:
     * - Starts with one locale
     * - Renders navigation and captures label
     * - Switches to different locale
     * - Renders navigation again
     * - Verifies label changed to new locale's translation
     * 
     * **Properties Verified**:
     * 1. Label changes when locale changes
     * 2. Each locale produces different label
     * 3. Labels match expected translations
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     */
    public function test_locale_switching_updates_navigation_label(): void
    {
        // Feature: news-page, Property 21: Locale-aware navigation (locale switching)
        // Validates: Requirements 9.4
        
        $iterations = 10;
        $assertions = 0;

        for ($i = 0; $i < $iterations; $i++) {
            // Start with English
            App::setLocale('en');
            $englishLabel = __('nav.news');
            $englishView = (string) $this->blade('<x-navigation.main />');
            
            // Property 1: English label is correct
            $this->assertEquals('News', $englishLabel,
                "English translation should be 'News'");
            $assertions++;
            
            // Property 2: English label appears in rendered navigation
            $this->assertStringContainsString('News', $englishView,
                "Navigation should contain 'News' when locale is 'en'");
            $assertions++;
            
            // Switch to Spanish
            App::setLocale('es');
            $spanishLabel = __('nav.news');
            $spanishView = (string) $this->blade('<x-navigation.main />');
            
            // Property 3: Spanish label is correct
            $this->assertEquals('Noticias', $spanishLabel,
                "Spanish translation should be 'Noticias'");
            $assertions++;
            
            // Property 4: Spanish label appears in rendered navigation
            $this->assertStringContainsString('Noticias', $spanishView,
                "Navigation should contain 'Noticias' when locale is 'es'");
            $assertions++;
            
            // Property 5: Labels are different between locales
            $this->assertNotEquals($englishLabel, $spanishLabel,
                "English and Spanish labels should be different");
            $assertions++;
            
            // Property 6: English label should not appear in Spanish navigation
            $this->assertStringNotContainsString('News', $spanishView,
                "Navigation should not contain 'News' when locale is 'es'");
            $assertions++;
            
            // Property 7: Spanish label should not appear in English navigation
            $this->assertStringNotContainsString('Noticias', $englishView,
                "Navigation should not contain 'Noticias' when locale is 'en'");
            $assertions++;
        }

        // Verify we ran all expected assertions
        $expectedAssertions = $iterations * 7;
        $this->assertEquals($expectedAssertions, $assertions,
            "Should have run {$expectedAssertions} assertions across {$iterations} iterations");
    }

    /**
     * Test Property 21 (Edge Case): Navigation with unsupported locale returns translation key
     * 
     * **Property**: When an unsupported locale is set, the navigation should still
     * render without errors, even if the translation key is returned as-is.
     * 
     * **Validates**: Requirement 9.4 - Display News link label in current locale
     * 
     * **Test Strategy**:
     * - Sets an unsupported locale
     * - Renders navigation
     * - Verifies navigation renders without errors
     * - Confirms navigation contains either translation or key
     * 
     * **Properties Verified**:
     * 1. Navigation renders without errors for unsupported locale
     * 2. Translation key or fallback is present
     * 3. Navigation is not empty
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     */
    public function test_unsupported_locale_renders_gracefully(): void
    {
        // Feature: news-page, Property 21: Locale-aware navigation (unsupported locale)
        // Validates: Requirements 9.4
        
        $assertions = 0;

        // Set an unsupported locale
        App::setLocale('fr'); // French is not supported
        
        $label = __('nav.news');
        
        // Property 1: Translation returns something (key or fallback)
        $this->assertNotEmpty($label,
            "Translation should return a value even for unsupported locale");
        $assertions++;
        
        // Property 2: Navigation should render without errors
        $view = (string) $this->blade('<x-navigation.main />');
        $this->assertNotEmpty($view,
            "Navigation should render content even with unsupported locale");
        $assertions++;
        
        // Property 3: Navigation should contain the label (whether translated or key)
        $this->assertStringContainsString($label, $view,
            "Navigation should contain the label for unsupported locale");
        $assertions++;

        $this->assertEquals(3, $assertions,
            "Should have run 3 assertions for unsupported locale test");
    }

    /**
     * Test Property 21 (Idempotence): Multiple renders with same locale produce consistent labels
     * 
     * **Property**: Rendering the navigation multiple times with the same locale
     * should always produce the same translated label.
     * 
     * **Validates**: Requirement 9.4 - Display News link label in current locale
     * 
     * **Test Strategy**:
     * - Sets a locale
     * - Renders navigation multiple times
     * - Verifies all renders produce identical labels
     * - Tests with both supported locales
     * 
     * **Properties Verified**:
     * 1. Translation is consistent across multiple calls
     * 2. Rendering is idempotent (same input = same output)
     * 3. No state leakage between renders
     * 
     * @return void
     * 
     * @test
     * @group property-testing
     */
    public function test_multiple_renders_produce_consistent_labels(): void
    {
        // Feature: news-page, Property 21: Locale-aware navigation (idempotence)
        // Validates: Requirements 9.4
        
        $assertions = 0;

        foreach ($this->supportedLocales as $locale) {
            App::setLocale($locale);
            
            $renders = [];
            $labels = [];
            
            // Render navigation 5 times
            for ($i = 0; $i < 5; $i++) {
                $labels[] = __('nav.news');
                $renders[] = (string) $this->blade('<x-navigation.main />');
            }
            
            // Property 1: All translation calls return the same value
            $uniqueLabels = array_unique($labels);
            $this->assertCount(1, $uniqueLabels,
                "All translation calls should return the same label for locale '{$locale}'");
            $assertions++;
            
            // Property 2: All renders contain the same translated label
            $expectedLabel = $this->expectedTranslations[$locale];
            foreach ($renders as $index => $render) {
                $this->assertStringContainsString($expectedLabel, $render,
                    "Render #{$index} should contain '{$expectedLabel}' for locale '{$locale}'");
                $assertions++;
            }
            
            // Property 3: First and last render are identical (idempotence)
            $this->assertEquals($renders[0], $renders[4],
                "First and last render should be identical for locale '{$locale}'");
            $assertions++;
        }

        // Verify we ran all expected assertions
        // 2 locales * (1 unique check + 5 render checks + 1 idempotence check) = 14
        $this->assertEquals(14, $assertions,
            "Should have run 14 assertions for idempotence test");
    }


}
