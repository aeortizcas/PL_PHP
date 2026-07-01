<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('body_html');
            $table->string('priority')->nullable()->after('summary');
            $table->boolean('needs_response')->nullable()->after('priority');
            $table->text('action_items')->nullable()->after('needs_response');
            $table->timestamp('triaged_at')->nullable()->after('action_items');

            $table->index('priority');
            $table->index('needs_response');
            $table->index('triaged_at');
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['summary', 'priority', 'needs_response', 'action_items', 'triaged_at']);
        });
    }
};
