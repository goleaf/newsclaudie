# Property-Based Testing Guide

## Overview

This project uses property-based testing to verify universal properties across all inputs. Property-based testing is configured with the Pest Faker plugin and runs a minimum of 100 iterations per test (as per design requirements).

## Installation

The property-based testing library is already installed:

```bash
composer require pestphp/pest-plugin-faker --dev
```

## Configuration

### Minimum Iterations

The minimum number of iterations is configured in two places:

1. **phpunit.xml**: Environment variable `PEST_FAKER_ITERATIONS=100`
2. **Default in Helper**: `PropertyTesting::defaultIterations()` returns 100

### Usage

There are two ways to write property-based tests:

#### Method 1: Using the PropertyTesting Helper (Recommended)

```php
use Tests\Helpers\PropertyTesting;

test('property test example', function () {
    PropertyTesting::run(function ($faker) {
        $randomValue = $faker->word();
        
        // Test your property here
        expect($randomValue)->toBeString();
    });
});
```

#### Method 2: Manual Loop with fake() Function

```php
use function Pest\Faker\fake;

test('property test example', function () {
    for ($i = 0; $i < 100; $i++) {
        $randomValue = fake()->word();
        
        // Test your property here
        expect($randomValue)->toBeString();
    }
});
```

## Custom Iteration Count

You can specify a custom iteration count:

```php
PropertyTesting::run(function ($faker) {
    // Your test logic
}, 200); // Run 200 iterations instead of default 100
```

## Available Faker Methods

The Faker library provides many generators for different data types:

- **Text**: `word()`, `sentence()`, `paragraph()`, `text()`
- **Numbers**: `numberBetween($min, $max)`, `randomDigit()`, `randomFloat()`
- **Internet**: `email()`, `url()`, `ipv4()`, `userName()`
- **Person**: `name()`, `firstName()`, `lastName()`
- **DateTime**: `dateTime()`, `date()`, `time()`
- **Address**: `address()`, `city()`, `country()`
- **Company**: `company()`, `jobTitle()`
- **Lorem**: `words($count)`, `sentences($count)`

Full documentation: https://fakerphp.github.io/

## Writing Property-Based Tests

### What is a Property?

A property is a characteristic or behavior that should hold true across all valid executions. For example:

- **Round-trip property**: `decode(encode(x)) == x`
- **Invariant property**: `sort(list).length == list.length`
- **Idempotence**: `f(x) == f(f(x))`

### Example: Testing Slug Generation

```php
test('slug generation property', function () {
    PropertyTesting::run(function ($faker) {
        $title = $faker->sentence();
        $slug = Str::slug($title);
        
        // Property: Slug should only contain lowercase letters, numbers, and hyphens
        expect($slug)->toMatch('/^[a-z0-9-]+$/');
        
        // Property: Slug should not start or end with hyphen
        expect($slug)->not->toStartWith('-');
        expect($slug)->not->toEndWith('-');
    });
});
```

### Example: Testing Validation

```php
test('email validation property', function () {
    PropertyTesting::run(function ($faker) {
        $email = $faker->email();
        
        // Property: Valid emails should pass validation
        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
        expect($validator->passes())->toBeTrue();
    });
});
```

## Test Tagging

All property-based tests should be tagged with a comment referencing the design document:

```php
/**
 * Feature: admin-livewire-crud, Property 1: Data persistence round-trip
 * Validates: Requirements 1.4, 2.5
 */
test('data persistence round-trip', function () {
    PropertyTesting::run(function ($faker) {
        // Test implementation
    });
});
```

## Running Property-Based Tests

Run all tests:
```bash
php artisan test
```

Run specific test file:
```bash
php artisan test --filter PropertyTestingExampleTest
```

Run with parallel execution:
```bash
php artisan test --parallel
```

## Best Practices

1. **Use meaningful property names**: Name tests after the property they verify
2. **Test universal properties**: Focus on rules that should hold for all inputs
3. **Use appropriate generators**: Choose Faker methods that match your input domain
4. **Document properties**: Add comments explaining what property is being tested
5. **Reference requirements**: Tag tests with the requirements they validate
6. **Minimum 100 iterations**: Always use at least 100 iterations (default)
7. **Avoid trivial properties**: Test meaningful invariants, not obvious facts

## Troubleshooting

### Test Failures

When a property test fails, it means the property doesn't hold for at least one generated input. To debug:

1. Check the failing assertion to see what value caused the failure
2. Add `dump($randomValue)` before the assertion to see the generated value
3. Reduce iterations temporarily to isolate the issue
4. Fix either the test (if property is wrong) or the code (if implementation is wrong)

### Performance

If tests are slow:

1. Reduce iterations for development (increase for CI)
2. Use `--parallel` flag for parallel execution
3. Profile specific tests with `--profile`

## Examples

See `tests/Unit/PropertyTestingExampleTest.php` for working examples.
