<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Firebase Cloud Messaging HTTP v1 (no Composer — uses cURL + OpenSSL JWT).
 */
final class FcmService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function enabled(): bool
    {
        return $this->setupStatus()['enabled'];
    }

    /** @return array{enabled:bool,reason:?string} */
    public function setupStatus(): array
    {
        if (!filter_var(env_file('FCM_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            return ['enabled' => false, 'reason' => 'Set FCM_ENABLED=true in .env'];
        }
        if ($this->projectId() === '') {
            return ['enabled' => false, 'reason' => 'FCM_PROJECT_ID is missing in .env (or upload storage/fcm-service-account.json)'];
        }
        if ($this->clientEmail() === '') {
            return ['enabled' => false, 'reason' => 'FCM_CLIENT_EMAIL is missing in .env (or upload storage/fcm-service-account.json)'];
        }
        if ($this->privateKey() === '') {
            return [
                'enabled' => false,
                'reason' => 'FCM private key is missing. In Firebase Console → Project settings → Service accounts → Generate new private key, '
                    . 'then upload the JSON file to storage/fcm-service-account.json (or paste FCM_PRIVATE_KEY into .env)',
            ];
        }

        return ['enabled' => true, 'reason' => null];
    }

    /** @param array<string, string> $data
     * @return array{attempted:int,sent:int,failed:int,skipped_reason:?string}
     */
    public function sendToUser(string $userId, string $title, string $body, array $data = []): array
    {
        if (!$this->enabled()) {
            $reason = (string) ($this->setupStatus()['reason'] ?? 'FCM disabled');
            $this->log('SKIP user=' . $userId . ' reason=' . $reason);
            return ['attempted' => 0, 'sent' => 0, 'failed' => 0, 'skipped_reason' => $reason];
        }

        $stmt = Database::connection()->prepare('SELECT token FROM device_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);
        $tokens = array_column($stmt->fetchAll(), 'token');
        if ($tokens === []) {
            $this->log('SKIP user=' . $userId . ' reason=no device tokens registered');
            return ['attempted' => 0, 'sent' => 0, 'failed' => 0, 'skipped_reason' => 'No device tokens'];
        }

        $sent = 0;
        $failed = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken((string) $token, $title, $body, $data)) {
                ++$sent;
            } else {
                ++$failed;
            }
        }

        $this->log(sprintf(
            'USER user=%s attempted=%d sent=%d failed=%d title=%s',
            $userId,
            count($tokens),
            $sent,
            $failed,
            mb_substr($title, 0, 80)
        ));

        return [
            'attempted' => count($tokens),
            'sent' => $sent,
            'failed' => $failed,
            'skipped_reason' => null,
        ];
    }

    /** @param array<string, string> $data */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if (!$this->enabled() || $token === '') {
            return false;
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return false;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => mb_substr($title, 0, 200),
                    'body' => mb_substr($body, 0, 1000),
                ],
                'data' => array_map('strval', $data),
                'android' => ['priority' => 'HIGH'],
            ],
        ];

        $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($this->projectId()) . '/messages:send';
        $result = $this->httpJson('POST', $url, $payload, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);

        if ($result['ok']) {
            $this->log('OK token=' . substr($token, 0, 16) . '… title=' . mb_substr($title, 0, 60));
        }

        return $result['ok'];
    }

    private function accessToken(): ?string
    {
        static $cached = null;
        static $expiresAt = 0;
        if ($cached !== null && time() < $expiresAt - 60) {
            return $cached;
        }

        $jwt = $this->buildJwt();
        if ($jwt === null) {
            return null;
        }

        $body = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        $raw = $this->rawPost(self::TOKEN_URL, $body, ['Content-Type: application/x-www-form-urlencoded']);
        if ($raw === null) {
            return null;
        }

        $json = json_decode($raw, true);
        if (!is_array($json) || empty($json['access_token'])) {
            $this->log('FCM token error: ' . $raw);
            return null;
        }

        $cached = (string) $json['access_token'];
        $expiresAt = time() + (int) ($json['expires_in'] ?? 3600);
        return $cached;
    }

    private function buildJwt(): ?string
    {
        $email = $this->clientEmail();
        $key = $this->privateKey();
        if ($email === '' || $key === '') {
            return null;
        }

        $now = time();
        $header = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->b64url(json_encode([
            'iss' => $email,
            'scope' => self::FCM_SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $input = $header . '.' . $claim;
        $signature = '';
        $ok = openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            $this->log('FCM JWT sign failed');
            return null;
        }

        return $input . '.' . $this->b64url($signature);
    }

    /** @return array<string, mixed>|null */
    private function serviceAccountJson(): ?array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached ?: null;
        }

        $path = STORAGE_PATH . '/fcm-service-account.json';
        if (!is_file($path)) {
            $cached = false;
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json)) {
            $cached = false;
            return null;
        }

        $cached = $json;
        return $json;
    }

    private function projectId(): string
    {
        $fromEnv = trim((string) env_file('FCM_PROJECT_ID', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $json = $this->serviceAccountJson();
        return trim((string) ($json['project_id'] ?? ''));
    }

    private function clientEmail(): string
    {
        $fromEnv = trim((string) env_file('FCM_CLIENT_EMAIL', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $json = $this->serviceAccountJson();
        return trim((string) ($json['client_email'] ?? ''));
    }

    private function privateKey(): string
    {
        $key = (string) env_file('FCM_PRIVATE_KEY', '');
        if ($key === '') {
            $json = $this->serviceAccountJson();
            if ($json !== null) {
                return str_replace('\\n', "\n", (string) ($json['private_key'] ?? ''));
            }
        }

        return str_replace('\\n', "\n", $key);
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** @param array<string, mixed> $payload */
    private function httpJson(string $method, string $url, array $payload, array $headers): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $raw = $this->rawPost($url, $body, $headers, $method);
        $ok = $raw !== null;
        if (!$ok) {
            $this->log('FCM send failed');
        }
        return ['ok' => $ok, 'response' => $raw];
    }

    private function rawPost(string $url, string $body, array $headers, string $method = 'POST'): ?string
    {
        if (function_exists('curl_init')) {
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
            curl_close($ch);
            if ($raw === false || $code < 200 || $code >= 300) {
                $this->log('FCM HTTP ' . $code . ': ' . (string) $raw);
                return null;
            }
            return (string) $raw;
        }

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
        return is_string($raw) ? $raw : null;
    }

    private function log(string $line): void
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/fcm.log', date('c') . ' ' . $line . PHP_EOL, FILE_APPEND);
    }
}
