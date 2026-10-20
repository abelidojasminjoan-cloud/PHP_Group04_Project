<?php
declare(strict_types=1);

function securityAudit(
    mysqli $conn,
    string $action,
    string $description,
    ?int $recordId = null
): void {
    $adminId = (int) ($_SESSION['user_id'] ?? 0) ?: null;
    $module = 'security';
    $record = $recordId === null
        ? null
        : (string) $recordId;
    $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: null;

    $stmt = $conn->prepare(
        "INSERT INTO audit_logs
         (user_id, action, module, record_id,
          description, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        'isssss',
        $adminId,
        $action,
        $module,
        $record,
        $description,
        $ip
    );

    $stmt->execute();
    $stmt->close();
}
