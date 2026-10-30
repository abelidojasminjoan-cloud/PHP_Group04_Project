<?php
declare(strict_types=1);
require_once __DIR__ . '/PayPalClient.php';

final class PayPalWebhook
{
    public static function verify(array|stdClass $event, array $server, ?PayPalClient $client = null): bool
    {
        $webhookId = trim((string) ($_ENV['PAYPAL_WEBHOOK_ID'] ?? ''));
        if ($webhookId === '') throw new RuntimeException('PayPal webhook ID is not configured.');
        $body = ['webhook_id' => $webhookId, 'webhook_event' => $event];
        foreach (['auth_algo', 'cert_url', 'transmission_id', 'transmission_sig', 'transmission_time'] as $field) {
            $value = $server['HTTP_PAYPAL_' . strtoupper($field)] ?? '';
            if (!is_string($value) || trim($value) === '' || strlen($value) > 4096) return false;
            $body[$field] = $value;
        }
        // PayPal verifies the certificate; this app never fetches the supplied URL.
        $result = ($client ?? new PayPalClient())->request('POST', '/v1/notifications/verify-webhook-signature', $body);
        return ($result['verification_status'] ?? '') === 'SUCCESS';
    }
}
