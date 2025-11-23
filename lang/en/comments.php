<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Comment Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for comment-related messages
    | throughout the application.
    |
    */

    'created' => 'Your comment has been submitted and is awaiting moderation.',
    'updated' => 'Your comment has been updated successfully.',
    'deleted' => 'Your comment has been deleted successfully.',
    
    // Security: Spam detection messages
    'flagged_for_review' => 'Your comment has been flagged for review by our moderation team. This may be due to suspicious content patterns.',
    
    // Security: Rate limiting messages
    'rate_limit_exceeded' => 'You are posting comments too quickly. Please wait a moment before trying again.',
    'hourly_limit_exceeded' => 'You have reached the maximum number of comments allowed per hour. Please try again later.',
    
    // Moderation messages
    'approved' => 'Comment approved successfully.',
    'rejected' => 'Comment rejected successfully.',
    'pending' => 'Comment marked as pending review.',
    
    // Error messages
    'not_found' => 'Comment not found.',
    'unauthorized' => 'You are not authorized to perform this action.',
    'disabled' => 'Commenting is currently disabled.',
    'email_verification_required' => 'You must verify your email address before commenting.',
];

