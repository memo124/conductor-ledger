<?php

return [

    'version' => trim((string) @file_get_contents(base_path('VERSION')) ?: '1.0.0'),

    'timezone' => env('APP_TIMEZONE', 'America/El_Salvador'),

    'registration_mode' => env('REGISTRATION_MODE', 'approval'),

    'encryption' => [
        'master_key' => env('MASTER_ENCRYPTION_KEY'),
        'dek_size' => 32,
        'cipher' => 'aes-256-gcm',
        'kdf' => [
            'memory_cost' => (int) env('ENCRYPTION_KDF_MEMORY', 65536),
            'time_cost' => (int) env('ENCRYPTION_KDF_TIME', 4),
            'threads' => (int) env('ENCRYPTION_KDF_THREADS', 1),
        ],
    ],

    'backup' => [
        'disk' => env('BACKUP_DISK', 'backup_local'),
        'cloud_disk' => env('BACKUP_CLOUD_DISK', 's3'),
        'retention_months' => (int) env('BACKUP_RETENTION_MONTHS', 12),
        'notify_email' => env('BACKUP_NOTIFY_EMAIL'),
        'pg_dump_binary' => env('PG_DUMP_BINARY', 'pg_dump'),
        'download_token_ttl_minutes' => (int) env('BACKUP_DOWNLOAD_TOKEN_TTL', 15),
    ],

    'security' => [
        'admin_email' => env('SECURITY_ADMIN_EMAIL'),
        'emergency_decrypt_rate_limit' => (int) env('EMERGENCY_DECRYPT_RATE_LIMIT', 5),
    ],

];
