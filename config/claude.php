<?php

return [
    'api_key' => env('CLAUDE_API_KEY'),
    'model' => env('CLAUDE_MODEL', 'claude-sonnet-4-20250514'),
    'max_tokens' => env('CLAUDE_MAX_TOKENS', 2048),
    'timeout' => env('CLAUDE_TIMEOUT', 180),
];
