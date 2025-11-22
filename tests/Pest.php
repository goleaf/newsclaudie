<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Pest\Browser\Browser;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Property-Based Testing Configuration
|--------------------------------------------------------------------------
|
| Property-based testing is configured with Pest Faker plugin.
| Minimum iterations: 100 (as per design requirements)
|
| Usage Example:
|
| use function Pest\Faker\fake;
|
| test('property test example', function () {
|     for ($i = 0; $i < 100; $i++) {
|         $randomValue = fake()->word();
|         
|         // Test your property here
|         expect($randomValue)->toBeString();
|     }
| });
|
| Available Faker methods: https://fakerphp.github.io/
|
*/
