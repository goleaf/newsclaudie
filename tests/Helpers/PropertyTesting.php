<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Closure;
use function Pest\Faker\fake;

/**
 * Property-Based Testing Helper
 * 
 * Provides utilities for property-based testing with configurable iterations.
 * Default minimum iterations: 100 (as per design requirements)
 */
class PropertyTesting
{
    /**
     * Run a property test with the specified number of iterations
     * 
     * @param Closure $test The test closure that receives a Faker instance
     * @param int $iterations Number of iterations (default: 100)
     * @return void
     */
    public static function run(Closure $test, int $iterations = 100): void
    {
        for ($i = 0; $i < $iterations; $i++) {
            $test(fake());
        }
    }
    
    /**
     * Get the default minimum iterations for property-based tests
     * 
     * @return int
     */
    public static function defaultIterations(): int
    {
        return (int) env('PEST_FAKER_ITERATIONS', 100);
    }
}
