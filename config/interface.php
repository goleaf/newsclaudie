<?php

declare(strict_types=1);

return [
    'pagination' => [
        'param' => 'per_page',
        'defaults' => [
            'admin' => 20,
            'comments' => (int) env('BLOGKIT_COMMENTS_PER_PAGE', 10),
            'posts' => 12,
            'categories' => 15,
            'category_posts' => 12,
        ],
        'options' => [
            'admin' => [10, 20, 50, 100],
            'comments' => [10, 25, 50, (int) env('BLOGKIT_COMMENTS_PER_PAGE', 10)],
            'posts' => [12, 18, 24, 36],
            'categories' => [12, 15, 24, 30],
            'category_posts' => [9, 12, 18, 24],
        ],
    ],
];
