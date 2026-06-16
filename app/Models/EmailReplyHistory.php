<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailReplyHistory extends Model
{
    protected $fillable = [
        'user_id', 'email_id', 'original_subject',
        'original_body', 'reply_body', 'tone_used',
        'ai_suggestion', 'was_edited',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }
}
