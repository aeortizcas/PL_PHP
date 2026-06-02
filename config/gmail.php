<?php

return [
    'imap_host' => env('GMAIL_IMAP_HOST', 'imap.gmail.com'),
    'imap_port' => env('GMAIL_IMAP_PORT', 993),
    'imap_encryption' => env('GMAIL_IMAP_ENCRYPTION', 'ssl'),
    'imap_username' => env('GMAIL_IMAP_USERNAME'),
    'imap_password' => env('GMAIL_IMAP_PASSWORD'),
    'imap_mailbox' => env('GMAIL_IMAP_MAILBOX', 'INBOX'),
    'sync_enabled' => env('GMAIL_SYNC_ENABLED', false),
    'sync_max_results' => env('GMAIL_SYNC_MAX_RESULTS', 50),
];
