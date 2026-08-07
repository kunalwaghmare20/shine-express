<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * WhatsApp sender for shared hosting (no Composer).
 *
 * Providers (WHATSAPP_PROVIDER):
 * - log      → write to storage/logs only (default / safe for local)
 * - cloud    → Meta WhatsApp Cloud API (needs token + phone number id)
 * - webhook  → POST JSON to WHATSAPP_WEBHOOK_URL (Interakt / custom gateway)
 */
final class WhatsAppService
{
    public function enabled(): bool
    {
        return filter_var(env_file('WHATSAPP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    public function provider(): string
    {
        return strtolower((string) env_file('WHATSAPP_PROVIDER', 'log'));
    }

    /**
     * @return array{ok:bool,status:string,response:?string}
     */
    public function send(string $phone, string $message, ?string $bookingId = null): array
    {
        $phone = $this->normalizePhone($phone);
        if ($phone === '') {
            return ['ok' => false, 'status' => 'INVALID_PHONE', 'response' => 'Missing phone'];
        }

        if (!$this->enabled()) {
            $this->logRow($bookingId, $phone, $message, 'log', 'SKIPPED', 'WHATSAPP_ENABLED=false');
            return ['ok' => false, 'status' => 'DISABLED', 'response' => 'WhatsApp disabled'];
        }

        $provider = $this->provider();
        $result = match ($provider) {
            'cloud' => $this->sendCloud($phone, $message),
            'webhook' => $this->sendWebhook($phone, $message),
            default => $this->sendLog($phone, $message),
        };

        $this->logRow(
            $bookingId,
            $phone,
            $message,
            $provider,
            $result['ok'] ? 'SENT' : 'FAILED',
            $result['response']
        );

        return $result;
    }

    /** Digits only, with country code (default 91 if 10-digit Indian number). */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 10) {
            $digits = '91' . $digits;
        }
        return $digits;
    }

    /** @return array{ok:bool,status:string,response:?string} */
    private function sendLog(string $phone, string $message): array
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = date('c') . " phone={$phone} msg=" . str_replace(["\r", "\n"], ' ', $message) . PHP_EOL;
        @file_put_contents($dir . '/whatsapp.log', $line, FILE_APPEND);
        return ['ok' => true, 'status' => 'LOGGED', 'response' => 'Written to storage/logs/whatsapp.log'];
    }

    /** @return array{ok:bool,status:string,response:?string} */
    private function sendCloud(string $phone, string $message): array
    {
        $token = (string) env_file('WHATSAPP_ACCESS_TOKEN', '');
        $phoneId = (string) env_file('WHATSAPP_PHONE_NUMBER_ID', '');
        $template = trim((string) env_file('WHATSAPP_TEMPLATE_NAME', ''));
        $lang = (string) env_file('WHATSAPP_TEMPLATE_LANG', 'en');

        if ($token === '' || $phoneId === '') {
            return ['ok' => false, 'status' => 'CONFIG', 'response' => 'Missing WHATSAPP_ACCESS_TOKEN or WHATSAPP_PHONE_NUMBER_ID'];
        }

        $url = 'https://graph.facebook.com/v19.0/' . rawurlencode($phoneId) . '/messages';

        if ($template !== '') {
            // Template body params: split message by " | " into ordered variables
            $parts = array_values(array_filter(array_map('trim', explode('|', $message))));
            $parameters = [];
            foreach ($parts as $part) {
                $parameters[] = ['type' => 'text', 'text' => mb_substr($part, 0, 1024)];
            }
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $lang],
                    'components' => $parameters === [] ? [] : [[
                        'type' => 'body',
                        'parameters' => $parameters,
                    ]],
                ],
            ];
        } else {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => mb_substr($message, 0, 4096)],
            ];
        }

        return $this->httpJson('POST', $url, $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);
    }

    /** @return array{ok:bool,status:string,response:?string} */
    private function sendWebhook(string $phone, string $message): array
    {
        $url = (string) env_file('WHATSAPP_WEBHOOK_URL', '');
        if ($url === '') {
            return ['ok' => false, 'status' => 'CONFIG', 'response' => 'Missing WHATSAPP_WEBHOOK_URL'];
        }
        $payload = [
            'phone' => $phone,
            'message' => $message,
            'source' => 'shine-express',
        ];
        $headers = ['Content-Type: application/json'];
        $hookToken = (string) env_file('WHATSAPP_WEBHOOK_TOKEN', '');
        if ($hookToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $hookToken;
        }
        return $this->httpJson('POST', $url, $payload, $headers);
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $headers
     * @return array{ok:bool,status:string,response:?string}
     */
    private function httpJson(string $method, string $url, array $payload, array $headers): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!function_exists('curl_init')) {
            $opts = [
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $headers),
                    'content' => $body,
                    'timeout' => 20,
                    'ignore_errors' => true,
                ],
            ];
            $raw = @file_get_contents($url, false, stream_context_create($opts));
            $ok = $raw !== false;
            return ['ok' => $ok, 'status' => $ok ? 'SENT' : 'FAILED', 'response' => is_string($raw) ? $raw : null];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $ok = $raw !== false && $code >= 200 && $code < 300;
        return [
            'ok' => $ok,
            'status' => $ok ? 'SENT' : 'FAILED',
            'response' => $raw !== false ? (string) $raw : $err,
        ];
    }

    private function logRow(?string $bookingId, string $phone, string $message, string $provider, string $status, ?string $response): void
    {
        try {
            Database::connection()->prepare(
                'INSERT INTO whatsapp_logs (id, booking_id, phone, message, provider, status, response_body)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([
                generate_id(),
                $bookingId,
                $phone,
                $message,
                $provider,
                $status,
                $response,
            ]);
        } catch (\Throwable $e) {
            // Table may not exist yet on older DBs — ignore for resilience
        }
    }
}
