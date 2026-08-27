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
        return WhatsAppConfig::enabled();
    }

    public function provider(): string
    {
        return WhatsAppConfig::provider();
    }

    public function broadcastTemplateName(): string
    {
        return WhatsAppConfig::broadcastTemplateName();
    }

    /**
     * Promotional / all-customer sends. Cloud API requires an approved Marketing template
     * so Meta will deliver without a 24-hour customer reply.
     *
     * @return array{ready:bool,enabled:bool,provider:string,reason:?string,hint:?string}
     */
    public function broadcastSetupStatus(): array
    {
        $status = $this->setupStatus();
        if (!$status['ready']) {
            return $status;
        }

        if ($this->provider() === 'cloud' && $this->broadcastTemplateName() === '') {
            return [
                'ready' => false,
                'enabled' => true,
                'provider' => 'cloud',
                'reason' => 'Promotional broadcasts need an approved Meta Marketing template. Set it under Admin → WhatsApp settings.',
                'hint' => 'Create the template in Meta Business Manager (category Marketing). After it is Approved, WhatsApp delivers it even if the customer has not replied in 24 hours.',
            ];
        }

        return $status;
    }

    /**
     * @return array{ready:bool,enabled:bool,provider:string,reason:?string,hint:?string}
     */
    public function setupStatus(): array
    {
        $provider = $this->provider();

        if (!$this->enabled()) {
            return [
                'ready' => false,
                'enabled' => false,
                'provider' => $provider,
                'reason' => 'WhatsApp is disabled. Turn it on under Admin → WhatsApp settings.',
                'hint' => 'For a dry run without Meta, set provider to log. Messages go to storage/logs/whatsapp.log.',
            ];
        }

        if ($provider === 'cloud') {
            $token = WhatsAppConfig::accessToken();
            $phoneId = WhatsAppConfig::phoneNumberId();
            if ($token === '' || $phoneId === '') {
                return [
                    'ready' => false,
                    'enabled' => true,
                    'provider' => $provider,
                    'reason' => 'Missing Cloud API access token or phone number ID. Add them under Admin → WhatsApp settings.',
                    'hint' => 'Get both from Meta for Developers → WhatsApp → API Setup.',
                ];
            }

            return [
                'ready' => true,
                'enabled' => true,
                'provider' => $provider,
                'reason' => null,
                'hint' => null,
            ];
        }

        if ($provider === 'webhook') {
            if (WhatsAppConfig::webhookUrl() === '') {
                return [
                    'ready' => false,
                    'enabled' => true,
                    'provider' => $provider,
                    'reason' => 'Missing webhook URL. Add it under Admin → WhatsApp settings.',
                    'hint' => 'Set the Interakt / gateway webhook URL, or switch provider to cloud or log.',
                ];
            }

            return [
                'ready' => true,
                'enabled' => true,
                'provider' => $provider,
                'reason' => null,
                'hint' => null,
            ];
        }

        return [
            'ready' => true,
            'enabled' => true,
            'provider' => 'log',
            'reason' => null,
            'hint' => 'Provider is log — messages are written to storage/logs/whatsapp.log, not delivered on WhatsApp. Set provider to cloud under WhatsApp settings for real delivery.',
        ];
    }

    /**
     * @param array{
     *   as_text?:bool,
     *   template_name?:string,
     *   template_lang?:string,
     *   template_params?:list<string>
     * } $options
     * @return array{ok:bool,status:string,response:?string}
     */
    public function send(string $phone, string $message, ?string $bookingId = null, array $options = []): array
    {
        $phone = $this->normalizePhone($phone);
        if ($phone === '') {
            return ['ok' => false, 'status' => 'INVALID_PHONE', 'response' => 'Missing or invalid phone number'];
        }

        if (!$this->enabled()) {
            $this->logRow($bookingId, $phone, $message, 'log', 'SKIPPED', 'WHATSAPP_ENABLED=false');
            return ['ok' => false, 'status' => 'DISABLED', 'response' => 'WhatsApp is disabled (WHATSAPP_ENABLED=false)'];
        }

        $provider = $this->provider();
        $result = match ($provider) {
            'cloud' => $this->sendCloud($phone, $message, $options),
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
        $written = @file_put_contents($dir . '/whatsapp.log', $line, FILE_APPEND);
        if ($written === false) {
            return ['ok' => false, 'status' => 'FAILED', 'response' => 'Could not write storage/logs/whatsapp.log (check folder permissions)'];
        }
        return ['ok' => true, 'status' => 'LOGGED', 'response' => 'Written to storage/logs/whatsapp.log'];
    }

    /**
     * @param array{
     *   as_text?:bool,
     *   template_name?:string,
     *   template_lang?:string,
     *   template_params?:list<string>
     * } $options
     * @return array{ok:bool,status:string,response:?string}
     */
    private function sendCloud(string $phone, string $message, array $options = []): array
    {
        $token = WhatsAppConfig::accessToken();
        $phoneId = WhatsAppConfig::phoneNumberId();

        if ($token === '' || $phoneId === '') {
            return ['ok' => false, 'status' => 'CONFIG', 'response' => 'Missing WHATSAPP_ACCESS_TOKEN or WHATSAPP_PHONE_NUMBER_ID'];
        }

        $url = 'https://graph.facebook.com/v21.0/' . rawurlencode($phoneId) . '/messages';
        $asText = !empty($options['as_text']);
        $template = trim((string) ($options['template_name'] ?? ''));
        $lang = trim((string) ($options['template_lang'] ?? ''));
        if ($lang === '') {
            $lang = WhatsAppConfig::templateLang();
        }

        if (!$asText && $template === '') {
            $template = WhatsAppConfig::reminderTemplateName();
        }

        if (!$asText && $template !== '') {
            $parts = $options['template_params'] ?? null;
            if (!is_array($parts) || $parts === []) {
                $parts = array_values(array_filter(array_map('trim', explode('|', $message))));
            }
            $parameters = [];
            foreach ($parts as $part) {
                $parameters[] = [
                    'type' => 'text',
                    'text' => $this->templateSafeText((string) $part),
                ];
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

    /** Meta template body params cannot contain newlines or 5+ consecutive spaces. */
    private function templateSafeText(string $text): string
    {
        $text = preg_replace("/\r\n|\r|\n/", ' ', $text) ?? $text;
        $text = preg_replace('/ {5,}/', '    ', $text) ?? $text;
        $text = trim($text);

        return mb_substr($text === '' ? '-' : $text, 0, 1024);
    }

    /** @return array{ok:bool,status:string,response:?string} */
    private function sendWebhook(string $phone, string $message): array
    {
        $url = WhatsAppConfig::webhookUrl();
        if ($url === '') {
            return ['ok' => false, 'status' => 'CONFIG', 'response' => 'Missing webhook URL'];
        }
        $payload = [
            'phone' => $phone,
            'message' => $message,
            'source' => 'shine-express',
        ];
        $headers = ['Content-Type: application/json'];
        $hookToken = WhatsAppConfig::webhookToken();
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
        if ($body === false) {
            return ['ok' => false, 'status' => 'FAILED', 'response' => 'Could not encode WhatsApp payload'];
        }

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
            $statusLine = $http_response_header[0] ?? '';
            preg_match('/\s(\d{3})\b/', $statusLine, $m);
            $code = (int) ($m[1] ?? 0);
            $ok = is_string($raw) && $code >= 200 && $code < 300;
            $response = is_string($raw) ? $raw : 'HTTP request failed';

            return [
                'ok' => $ok,
                'status' => $ok ? 'SENT' : 'FAILED',
                'response' => $this->formatProviderResponse($response, $code),
            ];
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
        $response = $raw !== false ? (string) $raw : $err;

        return [
            'ok' => $ok,
            'status' => $ok ? 'SENT' : 'FAILED',
            'response' => $this->formatProviderResponse($response, $code),
        ];
    }

    private function formatProviderResponse(string $raw, int $httpCode): string
    {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            if ($httpCode >= 400) {
                return 'HTTP ' . $httpCode . ': ' . mb_substr($raw, 0, 500);
            }
            return $raw;
        }

        $err = $decoded['error'] ?? null;
        if (!is_array($err)) {
            return $raw;
        }

        $code = (int) ($err['code'] ?? 0);
        $sub = (int) ($err['error_subcode'] ?? 0);
        $msg = trim((string) ($err['error_user_msg'] ?? $err['message'] ?? 'WhatsApp API error'));
        $hint = $this->graphErrorHint($code, $sub);
        $parts = array_filter([
            $code > 0 ? '#' . $code : null,
            $msg !== '' ? $msg : null,
            $hint,
        ]);

        return implode(' — ', $parts);
    }

    private function graphErrorHint(int $code, int $subcode): ?string
    {
        return match (true) {
            $code === 190, $code === 102 => 'Access token expired or invalid. Generate a new token in Meta Developer Console (temporary tokens last 24 hours).',
            $code === 131047 => 'Customer has not messaged you in the last 24 hours. Approve a marketing template and set it under WhatsApp settings.',
            $code === 131026 => 'Message undeliverable. Check the number is on WhatsApp and includes country code (91…).',
            $code === 131030 => 'This number is not in the Meta allowed list. While the app is in development, add test numbers in WhatsApp → API Setup.',
            $code === 132000 => 'Template parameter count does not match the approved template. Check broadcast template params in WhatsApp settings.',
            $code === 132001 => 'That template name is not approved for this WhatsApp number. Create and approve it in Meta Business Manager.',
            $code === 133010 => 'WhatsApp Business account is not ready (display name / billing).',
            $code === 100 => 'Invalid API request. Check phone number ID, template language code, and body variables.',
            $code === 4, $code === 80007, $subcode === 130429 => 'Rate limited by Meta. Wait and retry with fewer recipients.',
            default => null,
        };
    }

    private function logRow(?string $bookingId, string $phone, string $message, string $provider, string $status, ?string $response): void
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = date('c') . " status={$status} provider={$provider} phone={$phone} response="
            . str_replace(["\r", "\n"], ' ', (string) $response) . PHP_EOL;
        @file_put_contents($dir . '/whatsapp.log', $line, FILE_APPEND);

        try {
            Database::connection()->prepare(
                'INSERT INTO whatsapp_logs (id, booking_id, phone, message, provider, status, response_body)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([
                $this->logId(),
                $bookingId,
                $phone,
                $message,
                $provider,
                $status,
                $response,
            ]);
        } catch (\Throwable $e) {
            // Table may not exist yet, or id column may still be CHAR(24)
        }
    }

    /** Fits both CHAR(24) (migration 004) and VARCHAR(36) PKs. */
    private function logId(): string
    {
        return substr(str_replace('-', '', generate_id()), 0, 24);
    }
}
