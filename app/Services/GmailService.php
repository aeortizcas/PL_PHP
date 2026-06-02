<?php

namespace App\Services;

use App\Models\Email;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GmailService
{
    protected $imap = null;
    protected bool $authenticated = false;

    public function __construct()
    {
        $this->connect();
    }

    public function __destruct()
    {
        if ($this->imap) {
            imap_close($this->imap);
        }
    }

    protected function connect(): void
    {
        $host = config('gmail.imap_host');
        $port = config('gmail.imap_port');
        $encryption = config('gmail.imap_encryption');
        $username = config('gmail.imap_username');
        $password = config('gmail.imap_password');

        if (!$username || !$password) {
            return;
        }

        $mailbox = sprintf('{%s:%s/imap/%s}%s', $host, $port, $encryption, config('gmail.imap_mailbox'));

        $this->imap = @imap_open($mailbox, $username, $password, 0, 1);

        if ($this->imap) {
            $this->authenticated = true;
        } else {
            Log::error('IMAP connection failed: ' . implode(', ', imap_errors() ?: []));
        }
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function syncInbox(int $maxResults = 50): array
    {
        $synced = [];

        if (!$this->imap) {
            return $synced;
        }

        try {
            $emails = imap_search($this->imap, 'ALL', SE_UID);

            if (!$emails) {
                return $synced;
            }

            $emails = array_slice(array_reverse($emails), 0, $maxResults);

            foreach ($emails as $uid) {
                try {
                    $email = $this->processMessage($uid);
                    if ($email) {
                        $synced[] = $email;
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to process IMAP message UID {$uid}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('IMAP sync error: ' . $e->getMessage());
        }

        return $synced;
    }

    public function getMessage(int $uid): ?Email
    {
        if (!$this->imap) {
            return null;
        }

        try {
            return $this->processMessage($uid);
        } catch (\Exception $e) {
            Log::error("Failed to get IMAP message UID {$uid}: " . $e->getMessage());
            return null;
        }
    }

    public function sendEmail(string $to, string $subject, string $body, ?string $bodyHtml = null): ?Email
    {
        try {
            $user = Auth::user();

            Mail::mailer('gmail-smtp')->send([], [], function ($message) use ($to, $subject, $body, $bodyHtml, $user) {
                $message->to($to)
                    ->subject($subject)
                    ->from(config('gmail.imap_username'), $user->name);

                if ($bodyHtml) {
                    $message->html($bodyHtml);
                    $message->text($body);
                } else {
                    $message->text($body);
                }
            });

            $email = Email::create([
                'user_id' => $user->id,
                'subject' => $subject,
                'body_plain' => $body,
                'body_html' => $bodyHtml,
                'from_email' => config('gmail.imap_username'),
                'from_name' => $user->name,
                'to_email' => $to,
                'label' => 'SENT',
                'is_read' => true,
                'sent_at' => now(),
            ]);

            return $email;
        } catch (\Exception $e) {
            Log::error('Email send error: ' . $e->getMessage());
            return null;
        }
    }

    public function markAsRead(string $uid): bool
    {
        if (!$this->imap) {
            return false;
        }

        try {
            imap_setflag_full($this->imap, $uid, '\\Seen', FT_UID);
            return true;
        } catch (\Exception $e) {
            Log::error("IMAP markAsRead error for UID {$uid}: " . $e->getMessage());
            return false;
        }
    }

    public function markAsUnread(string $uid): bool
    {
        if (!$this->imap) {
            return false;
        }

        try {
            imap_clearflag_full($this->imap, $uid, '\\Seen', FT_UID);
            return true;
        } catch (\Exception $e) {
            Log::error("IMAP markAsUnread error for UID {$uid}: " . $e->getMessage());
            return false;
        }
    }

    public function trashMessage(string $uid): bool
    {
        if (!$this->imap) {
            return false;
        }

        try {
            $host = config('gmail.imap_host');
            $port = config('gmail.imap_port');
            $encryption = config('gmail.imap_encryption');
            $trash = sprintf('{%s:%s/imap/%s}[Gmail]/Trash', $host, $port, $encryption);
            imap_mail_move($this->imap, $uid, $trash, CP_UID);
            return true;
        } catch (\Exception $e) {
            Log::error("IMAP trash error for UID {$uid}: " . $e->getMessage());
            return false;
        }
    }

    protected function processMessage(int $uid): ?Email
    {
        $user = Auth::user();
        $overview = imap_fetch_overview($this->imap, $uid, FT_UID);

        if (!$overview || !isset($overview[0])) {
            return null;
        }

        $msg = $overview[0];

        $existing = Email::where('user_id', $user->id)
            ->where('gmail_id', (string) $uid)
            ->first();

        $isRead = ($msg->seen ?? false);

        if ($existing) {
            $existing->update([
                'is_read' => $isRead,
            ]);
            return $existing;
        }

        $body = $this->extractBody($uid);
        $fromEmail = $msg->from ?? '';
        $fromName = '';

        if (preg_match('/^(.+?)\s*<(.+?)>$/', $fromEmail, $m)) {
            $fromName = trim($m[1]);
            $fromEmail = trim($m[2]);
        }

        $toEmail = $msg->to ?? '';

        return Email::create([
            'user_id' => $user->id,
            'gmail_id' => (string) $uid,
            'subject' => $msg->subject ?? '(no subject)',
            'body_plain' => $body['plain'],
            'body_html' => $body['html'],
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'to_email' => $toEmail,
            'label' => 'INBOX',
            'is_read' => $isRead,
            'received_at' => isset($msg->date) ? Carbon::parse($msg->date) : now(),
        ]);
    }

    protected function extractBody(int $uid): array
    {
        $body = ['plain' => null, 'html' => null];
        $structure = imap_fetchstructure($this->imap, $uid, FT_UID);

        if ($structure) {
            $this->extractBodyParts($uid, $structure, $body, '');
        }

        return $body;
    }

    protected function extractBodyParts(int $uid, $structure, array &$body, string $prefix): void
    {
        if (isset($structure->parts) && count($structure->parts) > 0) {
            foreach ($structure->parts as $i => $part) {
                $partPrefix = $prefix ? $prefix . '.' . ($i + 1) : ($i + 1);
                $this->extractBodyParts($uid, $part, $body, $partPrefix);
            }
        } else {
            $mimeType = strtolower($structure->subtype ?? '');
            $encoding = $structure->encoding ?? 0;

            $section = $prefix ?: '1';
            $content = imap_fetchbody($this->imap, $uid, $section, FT_UID);

            if ($content) {
                $content = $this->decodeContent($content, $encoding);

                if ($mimeType === 'plain' && !$body['plain']) {
                    $body['plain'] = $content;
                } elseif ($mimeType === 'html' && !$body['html']) {
                    $body['html'] = $content;
                }
            }
        }
    }

    protected function decodeContent(string $content, int $encoding): string
    {
        return match ($encoding) {
            1 => imap_8bit($content),
            2 => imap_binary($content),
            3 => imap_base64($content),
            4 => imap_qprint($content),
            default => $content,
        };
    }

    protected function ensureAuthenticated(): bool
    {
        if (!$this->authenticated || !$this->imap) {
            $this->connect();
        }
        return $this->authenticated;
    }
}
