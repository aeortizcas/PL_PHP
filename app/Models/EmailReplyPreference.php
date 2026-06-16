<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailReplyPreference extends Model
{
    protected $fillable = [
        'user_id', 'tone', 'language', 'signature',
        'style_notes', 'include_signature', 'learn_from_replies',
    ];

    protected function casts(): array
    {
        return [
            'include_signature' => 'boolean',
            'learn_from_replies' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
