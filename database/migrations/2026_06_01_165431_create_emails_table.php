<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gmail_id')->unique()->nullable();
            $table->string('thread_id')->nullable();
            $table->string('subject')->nullable();
            $table->text('body_plain')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('label')->default('INBOX');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('gmail_id');
            $table->index('label');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
