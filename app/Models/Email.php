<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'gmail_id', 'thread_id', 'subject', 'body_plain', 'body_html',
    'summary', 'priority', 'needs_response', 'action_items',
    'from_email', 'from_name', 'to_email', 'to_name', 'cc', 'bcc',
    'label', 'is_read', 'is_starred', 'is_draft', 'has_attachments',
    'received_at', 'sent_at', 'triaged_at',
])]
class Email extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'cc' => 'array',
            'bcc' => 'array',
            'is_read' => 'boolean',
            'is_starred' => 'boolean',
            'is_draft' => 'boolean',
            'has_attachments' => 'boolean',
            'needs_response' => 'boolean',
            'received_at' => 'datetime',
            'sent_at' => 'datetime',
            'triaged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
