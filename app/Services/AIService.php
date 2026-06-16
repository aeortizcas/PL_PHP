<?php

namespace App\Services;

use App\Models\EmailReplyHistory;
use App\Models\EmailReplyPreference;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('ai.ollama_url');
        $this->model = config('ai.model');
        $this->timeout = config('ai.timeout');
    }

    public function suggestEmailReply(
        string $subject,
        string $fromName,
        string $body,
        ?int $userId = null,
        ?string $threadContext = null,
        ?string $fromEmail = null,
    ): ?string {
        $prompt = <<<SYSTEM
Eres un asistente de correo electrónico que analiza mensajes y redacta respuestas.

--- ANÁLISIS DEL EMAIL ---
ASUNTO: {$subject}
DE: {$fromName}
CUERPO:
{$body}

SYSTEM;

        if ($threadContext) {
            $prompt .= "HISTORIAL DE CONVERSACIÓN:\n{$threadContext}\n\n";
        }

        $prompt .= "--- ANÁLISIS ---\n";
        $prompt .= "Identifica el propósito del email, el tono del remitente, y los puntos clave que necesitan respuesta.\n\n";

        if ($userId) {
            $prefs = EmailReplyPreference::where('user_id', $userId)->first();
            if ($prefs) {
                $prompt .= "--- PREFERENCIAS DEL USUARIO ---\n";
                $prompt .= "Tono: {$prefs->tone}\n";
                if ($prefs->style_notes) {
                    $prompt .= "Estilo: {$prefs->style_notes}\n";
                }
                if ($prefs->language && $prefs->language !== 'auto') {
                    $prompt .= "Idioma: {$prefs->language}\n";
                }
                $prompt .= "\n";
            }

            if ($prefs && $prefs->learn_from_replies) {
                $pastReplies = EmailReplyHistory::where('user_id', $userId)
                    ->where('original_subject', 'like', '%' . $this->cleanSubject($subject) . '%')
                    ->orWhere(function ($q) use ($userId, $fromEmail) {
                        if ($fromEmail) {
                            $q->where('user_id', $userId)
                              ->whereHas('email', fn($e) => $e->where('from_email', $fromEmail));
                        }
                    })
                    ->latest()
                    ->take(3)
                    ->get();

                if ($pastReplies->isNotEmpty()) {
                    $prompt .= "--- RESPUESTAS ANTERIORES (aprende el estilo del usuario) ---\n";
                    foreach ($pastReplies as $idx => $reply) {
                        $prompt .= "Caso {$idx}:\n";
                        $prompt .= "  Email: " . mb_substr($reply->original_body, 0, 200) . "\n";
                        $prompt .= "  Usuario respondió: " . mb_substr($reply->reply_body, 0, 300) . "\n\n";
                    }
                }
            }
        }

        $prompt .= <<<INSTRUCTIONS
--- INSTRUCCIONES ---
1. Primero haz un breve análisis del email (qué solicita, tono, urgencia).
2. Luego escribe la respuesta.
3. Responde en el MISMO IDIOMA del email recibido.
4. No inventes información que no esté en el mensaje.
5. No uses frases como "He analizado tu email" o "Basado en tu mensaje".
6. Escribe la respuesta de forma natural, como lo haría un humano.

Formato de salida:
ANÁLISIS:
<breve análisis>

RESPUESTA:
<la respuesta>

INSTRUCTIONS;

        return $this->generate($prompt);
    }

    public function generate(string $prompt): ?string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/generate", [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.7,
                        'num_predict' => 500,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['response'] ?? null;

                if ($text) {
                    return trim($text);
                }
            }

            Log::error('Ollama API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Ollama request failed: ' . $e->getMessage());
            return null;
        }
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    protected function cleanSubject(string $subject): string
    {
        return preg_replace('/^(Re:|Fwd:|Fw:)\s*/i', '', $subject);
    }
}
