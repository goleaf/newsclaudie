<?php

declare(strict_types=1);

return [
    'pagination' => [
        'param' => 'per_page',
        'defaults' => [
            'grid' => 12,
            'table' => 15,
            'admin' => 20,
            'comments' => 10,
        ],
        'options' => [
            'grid' => [12, 24, 36],
            'table' => [15, 30, 60],
            'admin' => [10, 20, 50],
            'comments' => [10, 25, 50],
        ],
    ],
];

