<?php

namespace App\Services;

use App\Models\Email;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    protected string $apiKey;
    protected string $model;
    protected int $maxTokens;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('claude.api_key');
        $this->model = config('claude.model');
        $this->maxTokens = config('claude.max_tokens');
        $this->timeout = config('claude.timeout');
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function triageEmails(Collection $emails): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $results = [];
        $batches = $emails->chunk(10);

        foreach ($batches as $batch) {
            try {
                $batchResults = $this->triageBatch($batch);
                $results = array_merge($results, $batchResults);
            } catch (\Exception $e) {
                Log::error('Claude triage batch failed: ' . $e->getMessage());
            }
        }

        return $results;
    }

    protected function triageBatch(Collection $emails): array
    {
        $emailList = $emails->map(fn(Email $e, $idx) => sprintf(
            "[%d] Subject: %s\nFrom: %s\nBody: %s",
            $idx,
            $e->subject ?? '(no subject)',
            $e->from_name ?: $e->from_email,
            mb_substr($e->body_plain ?? $e->body_html ?? '', 0, 2000)
        ))->implode("\n\n");

        $prompt = <<<PROMPT
You are an AI legal assistant triaging emails for a law firm. For each email below, provide:

1. **summary** — A brief 1-2 sentence summary in the same language as the email.
2. **priority** — HIGH (urgent/time-sensitive), MEDIUM (needs attention this week), or LOW (informational/no rush).
3. **needs_response** — true if the sender is asking a question, requesting action, or expecting a reply; false if it's a notification, receipt, or FYI-only.
4. **action_items** — Key dates, deadlines, or specific requests mentioned (or empty string if none).

Respond with ONLY valid JSON — an array of objects, one per email in the same order:

```json
[
  {"email_index":0,"summary":"...","priority":"HIGH","needs_response":true,"action_items":"..."},
  {"email_index":1,"summary":"...","priority":"LOW","needs_response":false,"action_items":""}
]
```

Emails:
{$emailList}
PROMPT;

        $response = $this->sendMessage($prompt);

        if (!$response) {
            return [];
        }

        return $this->parseTriageResponse($response, $emails);
    }

    protected function sendMessage(string $prompt): ?string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $this->model,
                    'max_tokens' => $this->maxTokens,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['content'][0]['text'] ?? null;

                if ($text) {
                    return trim($text);
                }
            }

            Log::error('Claude API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (ConnectionException $e) {
            Log::error('Claude API connection failed: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('Claude request failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function parseTriageResponse(string $text, Collection $emails): array
    {
        $json = $text;

        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $text, $m)) {
            $json = trim($m[1]);
        }

        $parsed = json_decode($json, true);

        if (!is_array($parsed)) {
            $json = preg_replace('/^[^{]*/', '', $json);
            $json = preg_replace('/[^}]*$/', '', $json);
            $parsed = json_decode($json, true);
        }

        if (!is_array($parsed)) {
            Log::warning('Failed to parse Claude triage response', ['raw' => $text]);
            return [];
        }

        $results = [];
        $emailArray = $emails->values();

        foreach ($parsed as $item) {
            $idx = $item['email_index'] ?? null;
            if (!isset($emailArray[$idx])) {
                continue;
            }

            $email = $emailArray[$idx];
            $priority = in_array(strtoupper($item['priority'] ?? ''), ['HIGH', 'MEDIUM', 'LOW'])
                ? strtolower($item['priority'])
                : 'medium';

            $results[] = [
                'email' => $email,
                'summary' => mb_substr($item['summary'] ?? '', 0, 500),
                'priority' => $priority,
                'needs_response' => !empty($item['needs_response']),
                'action_items' => mb_substr($item['action_items'] ?? '', 0, 500),
            ];
        }

        return $results;
    }

    public function suggestEmailReply(
        string $subject,
        string $fromName,
        string $body,
        ?string $tone = 'professional',
        ?string $language = 'auto',
        ?string $styleNotes = null,
        ?string $pastReplies = null,
    ): ?string {
        if (!$this->isAvailable()) {
            return null;
        }

        $langInstruction = match ($language) {
            'en' => 'Respond in English.',
            'es' => 'Respond in Spanish.',
            'fr' => 'Respond in French.',
            'pt' => 'Respond in Portuguese.',
            default => 'Respond in the SAME LANGUAGE as the original email.',
        };

        $prompt = <<<PROMPT
You are a legal assistant drafting email replies. Analyze the incoming email and write a reply.

TONE: {$tone}
{$langInstruction}
STYLE NOTES: {$styleNotes}

INCOMING EMAIL:
Subject: {$subject}
From: {$fromName}
Body:
{$body}
PROMPT;

        if ($pastReplies) {
            $prompt .= "\n\nLEARN FROM THESE PAST REPLIES (match the style):\n{$pastReplies}";
        }

        $prompt .= <<<INSTRUCTIONS

Format your response exactly as:
ANÁLISIS:
<brief analysis of what this email needs>

RESPUESTA:
<the draft reply>

Rules:
- Do not invent information not in the message.
- Do not use phrases like "I have analyzed your email" or "Based on your message."
- Write naturally, as a human would.
INSTRUCTIONS;

        return $this->sendMessage($prompt);
    }
}
