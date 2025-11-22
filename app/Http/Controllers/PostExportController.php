<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PostExportRequest;
use App\Jobs\RunPostExport;
use App\Models\DataExport;
use App\Support\Exports\PostExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PostExportController extends Controller
{
    public function store(PostExportRequest $request): RedirectResponse
    {
        $filters = $request->filters();
        $format = $request->string('format')->lower()->value();

        $export = DataExport::query()->create([
            'user_id' => $request->user()->id,
            'type' => DataExport::TYPE_POSTS,
            'format' => $format,
            'status' => DataExport::STATUS_PENDING,
            'disk' => config('exports.disk', config('filesystems.default', 'local')),
            'filters' => $filters,
        ]);

        $exporter = new PostExporter();

        $totalRows = (clone $exporter->query($filters))->count();
        $threshold = (int) config('exports.max_sync_rows', 500);

        if ($totalRows > $threshold) {
            RunPostExport::dispatch($export);

            return back()->with('export_status', __('admin.exports.queued', [
                'format' => strtoupper($format),
                'count' => $totalRows,
            ]));
        }

        $export->forceFill(['status' => DataExport::STATUS_PROCESSING])->save();

        [$path, $rows] = $exporter->export($export);
        $expiresAt = Carbon::now()->addMinutes(config('exports.link_ttl_minutes', 30));

        $export->markCompleted($path, $rows, $expiresAt);

        $downloadUrl = URL::temporarySignedRoute(
            'admin.posts.export.download',
            $expiresAt,
            ['export' => $export->id],
        );

        return back()->with([
            'export_status' => __('admin.exports.ready', [
                'format' => strtoupper($format),
                'count' => $rows,
                'expires' => $export->expires_at?->toDayDateTimeString(),
            ]),
            'export_url' => $downloadUrl,
            'export_expires' => $export->expires_at?->toDayDateTimeString(),
            'export_rows' => $rows,
            'export_format' => strtoupper($format),
        ]);
    }

    public function download(DataExport $export): StreamedResponse
    {
        $user = request()->user();

        if ($user?->id !== $export->user_id) {
            abort(403);
        }

        if ($export->type !== DataExport::TYPE_POSTS) {
            abort(404);
        }

        if ($export->isExpired()) {
            abort(410, __('admin.exports.expired'));
        }

        if (! $export->isReady()) {
            abort(404);
        }

        return Storage::disk($export->disk)->download(
            $export->path,
            $export->downloadFilename(),
        );
    }
}
