<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DataExport;
use App\Notifications\ExportReadyNotification;
use App\Support\Exports\PostExporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Throwable;

final class RunPostExport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly DataExport $export)
    {
        $queue = config('exports.queue');

        if ($queue) {
            $this->onQueue($queue);
        }
    }

    public function handle(PostExporter $exporter): void
    {
        $this->export->forceFill(['status' => DataExport::STATUS_PROCESSING])->save();

        try {
            [$path, $rows] = $exporter->export($this->export);
            $expiresAt = Carbon::now()->addMinutes(config('exports.link_ttl_minutes', 30));

            $this->export->markCompleted($path, $rows, $expiresAt);

            if ($this->export->user) {
                $downloadUrl = URL::temporarySignedRoute(
                    'admin.posts.export.download',
                    $expiresAt,
                    ['export' => $this->export->id],
                );

                $this->export->user->notify(new ExportReadyNotification($this->export, $downloadUrl));
            }
        } catch (Throwable $throwable) {
            $this->export->markFailed();

            throw $throwable;
        }
    }
}
