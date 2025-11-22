<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Livewire\Concerns\ManagesSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithPagination;
use Tests\TestCase;

final class ManagesSearchTest extends TestCase
{
    private function makeComponent(): object
    {
        return new class extends Component
        {
            use ManagesSearch;
            use WithPagination;

            public function render()
            {
                return '<div></div>';
            }
        };
    }

    public function test_initializes_with_null_search(): void
    {
        $component = $this->makeComponent();

        $this->assertNull($component->search);
    }

    public function test_updated_search_normalizes_value(): void
    {
        $component = $this->makeComponent();

        $component->updatedSearch('  test search  ');

        $this->assertSame('test search', $component->search);
    }

    public function test_updated_search_handles_null(): void
    {
        $component = $this->makeComponent();

        $component->updatedSearch(null);

        $this->assertSame('', $component->search);
    }

    public function test_clear_search_resets_to_null(): void
    {
        $component = $this->makeComponent();
        $component->search = 'test';

        $component->clearSearch();

        $this->assertNull($component->search);
    }

    public function test_get_search_term_returns_normalized_value(): void
    {
        $component = $this->makeComponent();
        $component->search = '  test  ';

        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('getSearchTerm');
        $method->setAccessible(true);

        $this->assertSame('test', $method->invoke($component));
    }

    public function test_apply_search_returns_query_when_search_empty(): void
    {
        $component = $this->makeComponent();
        $component->search = null;

        $query = $this->createMock(Builder::class);
        $query->expects($this->never())->method('where');

        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('applySearch');
        $method->setAccessible(true);

        $result = $method->invoke($component, $query, ['name', 'email']);

        $this->assertSame($query, $result);
    }

    public function test_apply_search_returns_query_when_fields_empty(): void
    {
        $component = $this->makeComponent();
        $component->search = 'test';

        $query = $this->createMock(Builder::class);
        $query->expects($this->never())->method('where');

        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('applySearch');
        $method->setAccessible(true);

        $result = $method->invoke($component, $query, []);

        $this->assertSame($query, $result);
    }

    public function test_apply_search_adds_where_clauses(): void
    {
        $component = $this->makeComponent();
        $component->search = 'test';

        // Create a real query builder using a test model
        $model = new class extends Model
        {
            protected $table = 'test_table';
        };

        $query = $model->newQuery();

        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('applySearch');
        $method->setAccessible(true);

        $result = $method->invoke($component, $query, ['name', 'email']);

        // Verify the query has where clauses
        $this->assertInstanceOf(Builder::class, $result);
        
        // Get the SQL to verify the where clauses were added
        $sql = $result->toSql();
        $this->assertStringContainsString('where', strtolower($sql));
    }

    public function test_normalize_search_trims_whitespace(): void
    {
        $component = $this->makeComponent();

        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('normalizeSearch');
        $method->setAccessible(true);

        $this->assertSame('test', $method->invoke($component, '  test  '));
        $this->assertSame('test search', $method->invoke($component, '  test search  '));
        $this->assertSame('', $method->invoke($component, '   '));
        $this->assertSame('', $method->invoke($component, null));
    }
}
