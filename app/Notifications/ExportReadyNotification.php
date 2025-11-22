<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DataExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly DataExport $export,
        private readonly string $downloadUrl,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'export_id' => $this->export->id,
            'type' => $this->export->type,
            'format' => $this->export->format,
            'rows' => $this->export->total_rows,
            'url' => $this->downloadUrl,
            'expires_at' => $this->export->expires_at?->toIso8601String(),
            'message' => __('admin.exports.notification_ready', [
                'format' => strtoupper($this->export->format),
            ]),
        ];
    }
}
