<?php
return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'TTRPG Recap',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'env' => $_ENV['APP_ENV'] ?? 'development',
    ],
    
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'name' => $_ENV['DB_NAME'] ?? 'ttrpg_recap',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
    ],
    
    'apis' => [
        'openai' => [
            'key' => $_ENV['OPENAI_API_KEY'] ?? '',
            'whisper_model' => 'whisper-1',
        ],
        'anthropic' => [
            'key' => $_ENV['ANTHROPIC_API_KEY'] ?? '',
            'model' => 'claude-sonnet-4-20250514',
        ],
        'elevenlabs' => [
            'key' => $_ENV['ELEVENLABS_API_KEY'] ?? '',
            'enabled' => !empty($_ENV['ELEVENLABS_API_KEY']),
        ],
    ],
    
    'storage' => [
        'path' => $_ENV['STORAGE_PATH'] ?? 'storage',
        'max_upload_size_mb' => (int)($_ENV['MAX_UPLOAD_SIZE_MB'] ?? 500),
    ],
    
    'redis' => [
        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
    ],
];
