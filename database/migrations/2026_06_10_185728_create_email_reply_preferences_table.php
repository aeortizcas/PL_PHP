<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_reply_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tone')->default('professional');
            $table->string('language')->default('auto');
            $table->text('signature')->nullable();
            $table->text('style_notes')->nullable();
            $table->boolean('include_signature')->default(true);
            $table->boolean('learn_from_replies')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_reply_preferences');
    }
};
