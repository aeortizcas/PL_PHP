<?php

namespace App\Models;

use Database\Factories\CallLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Casting;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'contact_id', 'caller_number', 'direction', 'duration', 'disposition', 'notes', 'called_at'])]
class CallLog extends Model
{
    /** @use HasFactory<CallLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'duration' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function durationFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->duration) {
                    return null;
                }

                $minutes = floor($this->duration / 60);
                $seconds = $this->duration % 60;

                if ($minutes > 0) {
                    return $minutes . 'm ' . str_pad($seconds, 2, '0', STR_PAD_LEFT) . 's';
                }

                return $seconds . 's';
            }
        );
    }
}
