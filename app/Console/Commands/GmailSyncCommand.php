<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GmailService;
use Illuminate\Console\Command;

class GmailSyncCommand extends Command
{
    protected $signature = 'gmail:sync {--max=50 : Max emails to fetch}';
    protected $description = 'Sync Gmail inbox emails via IMAP';

    public function handle(): void
    {
        $max = (int) $this->option('max');

        foreach (User::all() as $user) {
            $this->info("Syncing emails for {$user->email}...");
            \Illuminate\Support\Facades\Auth::onceUsingId($user->id);

            $gmail = app(GmailService::class);

            if (!$gmail->isAuthenticated()) {
                $this->warn("  Skipping {$user->email} - IMAP not configured.");
                continue;
            }

            $synced = $gmail->syncInbox($max);
            $this->info("  Synced " . count($synced) . " emails.");
        }
    }
}
