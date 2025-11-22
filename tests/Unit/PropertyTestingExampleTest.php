<?php

declare(strict_types=1);

use Tests\Helpers\PropertyTesting;
use function Pest\Faker\fake;

/**
 * Example Property-Based Test
 * 
 * This test demonstrates the property-based testing setup with Pest Faker plugin.
 * It verifies that the configuration is working correctly with 100 iterations.
 * 
 * The minimum of 100 iterations is configured as per the design requirements.
 */

test('property-based testing is configured correctly', function () {
    // This test runs 100 times with random data
    // Property: String length should always be greater than 0
    PropertyTesting::run(function ($faker) {
        $randomString = $faker->word();
        
        expect(strlen($randomString))->toBeGreaterThan(0);
        expect($randomString)->toBeString();
    });
});

test('property test with numbers', function () {
    // Test that demonstrates numeric property testing with 100 iterations
    PropertyTesting::run(function ($faker) {
        $randomNumber = $faker->numberBetween(1, 100);
        
        // Property: Number should be within range
        expect($randomNumber)->toBeGreaterThanOrEqual(1);
        expect($randomNumber)->toBeLessThanOrEqual(100);
    });
});

test('property test with custom iterations', function () {
    // Demonstrates using custom iteration count
    PropertyTesting::run(function ($faker) {
        $email = $faker->email();
        
        // Property: Generated emails should be valid
        expect($email)->toContain('@');
        expect(filter_var($email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
    }, 50); // Custom iteration count
});
