<?php

namespace Classes;

class Telegram
{
    private string  $botToken;
    private ?string $defaultChatId;
    private int     $timeout;

    public function __construct(?string $botToken = null, ?string $defaultChatId = null, int $timeout = 8)
    {
        $this->botToken = $botToken
            ?? (defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : (getenv('TELEGRAM_BOT_TOKEN') ?: ''));
        $this->defaultChatId = $defaultChatId
            ?? (defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : (getenv('TELEGRAM_CHAT_ID') ?: null));
        $this->timeout = $timeout;
    }

    public function sendMessage(string $chatId, string $text, array $options = []): array
    {
        $chatId = trim($chatId);
        if ($chatId === '' || trim($this->botToken) === '') {
            return ['success' => false, 'error' => 'Bot token o chatId no configurados'];
        }

        $url     = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $payload = array_merge(['chat_id' => $chatId, 'text' => $text], $options);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,            $url);
        curl_setopt($ch, CURLOPT_POST,           1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,        $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $err) {
            error_log("Telegram::sendMessage cURL error: {$err}");
            return ['success' => false, 'http_code' => $code ?: 0, 'error' => $err];
        }

        $body = json_decode($response, true);
        $ok   = isset($body['ok']) ? (bool)$body['ok'] : ($code >= 200 && $code < 300);
        if (!$ok) error_log('Telegram API error: ' . ($body['description'] ?? 'desconocido'));

        return ['success' => $ok, 'http_code' => $code, 'body' => $body,
                'error' => $ok ? null : ($body['description'] ?? 'Unknown error')];
    }

    public function sendToDefault(string $text, array $options = []): array
    {
        if (!$this->defaultChatId) {
            return ['success' => false, 'error' => 'default chatId no configurado'];
        }
        return $this->sendMessage($this->defaultChatId, $text, $options);
    }
}
