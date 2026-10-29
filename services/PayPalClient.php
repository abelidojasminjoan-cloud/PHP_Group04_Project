<?php
declare(strict_types=1);

class PayPalClient
{
    public static function configured(): bool
    {
        return trim((string) ($_ENV['PAYPAL_CLIENT_ID'] ?? '')) !== ''
            && trim((string) ($_ENV['PAYPAL_CLIENT_SECRET'] ?? '')) !== ''
            && function_exists('curl_init');
    }

    public static function mode(): string
    {
        $mode = $_ENV['PAYPAL_MODE'] ?? 'sandbox';
        if (!in_array($mode, ['sandbox', 'live'], true)) throw new RuntimeException('Invalid PayPal mode.');
        return $mode;
    }

    public function browserClientToken(): string
    {
        if (!self::configured()) throw new DomainException('PayPal card payments are not configured yet.');
        $base = self::mode() === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        // This is a restricted SDK initialization token, never the server API access token.
        $result = $this->send($base . '/v1/oauth2/token', 'POST',
            'grant_type=client_credentials&response_type=client_token&intent=sdk_init',
            ['Content-Type: application/x-www-form-urlencoded'], true);
        if (empty($result['access_token'])) throw new RuntimeException('Unable to initialize PayPal card fields.');
        return $result['access_token'];
    }

    public function request(string $method, string $path, ?array $body = null, ?string $requestId = null): array
    {
        if (!self::configured()) throw new DomainException('PayPal is not configured yet. Please choose Cash on Delivery.');
        $base = self::mode() === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        $auth = $this->send($base . '/v1/oauth2/token', 'POST', 'grant_type=client_credentials',
            ['Content-Type: application/x-www-form-urlencoded'], true);
        if (empty($auth['access_token'])) throw new RuntimeException('PayPal authentication failed.');
        $headers = ['Authorization: Bearer ' . $auth['access_token'], 'Content-Type: application/json', 'Prefer: return=representation'];
        if ($requestId !== null) $headers[] = 'PayPal-Request-Id: ' . $requestId;
        return $this->send($base . $path, $method, $body === null ? null : json_encode($body === [] ? new stdClass() : $body, JSON_THROW_ON_ERROR), $headers);
    }

    private function send(string $url, string $method, ?string $body, array $headers, bool $auth = false): array
    {
        $curl = curl_init($url);
        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS];
        if ($body !== null) $options[CURLOPT_POSTFIELDS] = $body;
        if ($auth) $options[CURLOPT_USERPWD] = $_ENV['PAYPAL_CLIENT_ID'] . ':' . $_ENV['PAYPAL_CLIENT_SECRET'];
        curl_setopt_array($curl, $options);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_errno($curl);
        curl_close($curl);
        if ($raw === false || $error) throw new RuntimeException('Unable to reach PayPal. Retry payment status before starting another order.');
        $result = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        if ($status < 200 || $status >= 300 || !is_array($result)) {
            error_log('PureVia PayPal API HTTP ' . $status . '; debug ID: ' . ($result['debug_id'] ?? 'unavailable'));
            throw new RuntimeException('PayPal could not complete this request. You can retry from your payment page.');
        }
        return $result;
    }

    public static function approvalUrl(array $result): string
    {
        foreach ($result['links'] ?? [] as $link) {
            if (!in_array($link['rel'] ?? '', ['payer-action', 'approve'], true)) continue;
            $url = $link['href'] ?? '';
            $expected = self::mode() === 'live' ? 'www.paypal.com' : 'www.sandbox.paypal.com';
            if (parse_url($url, PHP_URL_SCHEME) === 'https' && parse_url($url, PHP_URL_HOST) === $expected
                && parse_url($url, PHP_URL_USER) === null && parse_url($url, PHP_URL_PORT) === null) return $url;
        }
        throw new RuntimeException('PayPal did not return a valid approval link.');
    }

    /** Approval alone is not payment. Require the exact completed capture and order identity. */
    public static function completedCapture(array $remote, array $local): string
    {
        $units = $remote['purchase_units'] ?? [];
        if (($remote['id'] ?? '') !== $local['paypal_order_id'] || ($remote['status'] ?? '') !== 'COMPLETED'
            || count($units) !== 1 || ($units[0]['custom_id'] ?? '') !== (string) $local['order_id']) {
            throw new DomainException('PayPal payment is not completed. Your order is not marked paid.');
        }
        $captures = $units[0]['payments']['captures'] ?? [];
        if (count($captures) !== 1) throw new DomainException('Unexpected PayPal capture. Contact support.');
        $capture = $captures[0];
        if (($capture['status'] ?? '') !== 'COMPLETED' || empty($capture['id'])
            || ($capture['amount']['currency_code'] ?? '') !== 'PHP'
            || ($capture['amount']['value'] ?? '') !== $local['total_amount']) {
            throw new DomainException('PayPal payment amount or status did not match. Contact support.');
        }
        return $capture['id'];
    }
}
