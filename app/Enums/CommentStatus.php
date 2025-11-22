<?php

declare(strict_types=1);

namespace App\Enums;

enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('admin.comments.status.pending'),
            self::Approved => __('admin.comments.status.approved'),
            self::Rejected => __('admin.comments.status.rejected'),
        };
    }
}
