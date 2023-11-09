<?php
declare(strict_types=1);

return [
    [
        'name' => 'objects',
        'mapping' => [
            'uname' => ['type' => 'text'],
            'type' => ['type' => 'text'],
            'status' => ['type' => 'text'],
            'deleted' => ['type' => 'boolean'],
            'publish_start' => ['type' => 'date'],
            'publish_end' => ['type' => 'date'],
            'title' => ['type' => 'text'],
            'description' => ['type' => 'text'],
            'body' => ['type' => 'text'],
        ],
    ],
];
