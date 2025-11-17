<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: 3306,
        'dbname' => getenv('DB_DATABASE') ?: 'chat',
        'user' => getenv('DB_USERNAME') ?: 'root',
        'pass' => getenv('DB_PASSWORD') ?: ''
    ],
    'jwt_secret' => getenv('JWT_SECRET') ?: 'secret',
    'openai_key' => getenv('OPENAI_API_KEY') ?: null,
    'bot_mode' => getenv('BOT_MODE') ?: 'ai'
];
