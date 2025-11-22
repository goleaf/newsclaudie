<?php

declare(strict_types=1);

namespace App\Support\Exports;

use App\Models\DataExport;
use App\Models\Post;
use App\Scopes\PublishedScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PostExporter
{
    private const CHUNK = 200;

    private string $disk;
    private string $directory;

    public function __construct()
    {
        $this->disk = config('exports.disk', config('filesystems.default', 'local'));
        $this->directory = trim(config('exports.directory', 'exports'), '/');
    }

    public function query(array $filters): Builder
    {
        $query = Post::query()
            ->withoutGlobalScope('order')
            ->withoutGlobalScope(PublishedScope::class)
            ->with([
                'author:id,name,email',
                'categories:id,name,slug',
            ])
            ->withCount('comments')
            ->orderBy('id');

        if (! empty($filters['filterByTag'])) {
            $query->whereJsonContains('tags', $filters['filterByTag']);
        }

        if (! empty($filters['author'])) {
            $query->where('user_id', (int) $filters['author']);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('categories', static fn (Builder $builder): Builder => $builder->where('categories.slug', $filters['category']));
        }

        return $query;
    }

    /**
     * @return array{path: string, rows: int}
     */
    public function export(DataExport $export): array
    {
        $query = $this->query($export->filters ?? []);
        $format = $export->format === 'json' ? 'json' : 'csv';

        return $format === 'json'
            ? $this->writeJson($query, $export)
            : $this->writeCsv($query, $export);
    }

    /**
     * @return array{path: string, rows: int}
     */
    private function writeCsv(Builder $query, DataExport $export): array
    {
        $this->ensureDirectory();

        $path = $this->pathFor($export, 'csv');
        $handle = fopen('php://temp', 'w+');

        fputcsv($handle, [
            'id',
            'title',
            'slug',
            'status',
            'author_name',
            'author_email',
            'categories',
            'category_slugs',
            'tags',
            'published_at',
            'comments_count',
            'featured_image',
            'description',
            'body',
            'created_at',
            'updated_at',
        ]);

        $total = 0;

        $query->chunkById(self::CHUNK, function ($posts) use (&$total, $handle): void {
            foreach ($posts as $post) {
                $payload = $this->transform($post);
                $total++;

                fputcsv($handle, [
                    $payload['id'],
                    $payload['title'],
                    $payload['slug'],
                    $payload['status'],
                    $payload['author']['name'] ?? null,
                    $payload['author']['email'] ?? null,
                    implode('|', array_column($payload['categories'], 'name')),
                    implode('|', array_column($payload['categories'], 'slug')),
                    implode('|', $payload['tags']),
                    $payload['published_at'],
                    $payload['comments_count'],
                    $payload['featured_image'],
                    $payload['description'],
                    $payload['body'],
                    $payload['created_at'],
                    $payload['updated_at'],
                ]);
            }
        });

        rewind($handle);

        Storage::disk($this->disk)->put($path, stream_get_contents($handle));
        fclose($handle);

        return ['path' => $path, 'rows' => $total];
    }

    /**
     * @return array{path: string, rows: int}
     */
    private function writeJson(Builder $query, DataExport $export): array
    {
        $this->ensureDirectory();

        $path = $this->pathFor($export, 'json');
        $handle = fopen('php://temp', 'w+');

        fwrite($handle, "[\n");

        $total = 0;
        $first = true;

        $query->chunkById(self::CHUNK, function ($posts) use (&$first, &$total, $handle): void {
            foreach ($posts as $post) {
                $payload = $this->transform($post);
                $total++;

                $json = json_encode(
                    $payload,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                );

                if (! $first) {
                    fwrite($handle, ",\n");
                }

                $first = false;
                fwrite($handle, '  '.$json);
            }
        });

        fwrite($handle, "\n]\n");
        rewind($handle);

        Storage::disk($this->disk)->put($path, stream_get_contents($handle));
        fclose($handle);

        return ['path' => $path, 'rows' => $total];
    }

    private function transform(Post $post): array
    {
        $categories = $post->categories->map(static fn ($category) => [
            'name' => $category->name,
            'slug' => $category->slug,
        ])->values()->all();

        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->isPublished() ? 'published' : 'draft',
            'author' => [
                'name' => $post->author?->name,
                'email' => $post->author?->email,
            ],
            'categories' => $categories,
            'tags' => array_values($post->tags ?? []),
            'published_at' => $post->published_at?->toAtomString(),
            'comments_count' => $post->comments_count ?? 0,
            'featured_image' => $post->featured_image,
            'description' => $post->description,
            'body' => $post->body,
            'created_at' => $post->created_at?->toAtomString(),
            'updated_at' => $post->updated_at?->toAtomString(),
        ];
    }

    private function ensureDirectory(): void
    {
        if ($this->directory !== '') {
            Storage::disk($this->disk)->makeDirectory($this->directory);
        }
    }

    private function pathFor(DataExport $export, string $format): string
    {
        $filename = sprintf(
            '%s-%s.%s',
            $export->type,
            Str::uuid()->toString(),
            $format
        );

        return $this->directory
            ? "{$this->directory}/{$filename}"
            : $filename;
    }
}
