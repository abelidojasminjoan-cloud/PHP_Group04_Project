<?php
declare(strict_types=1);
require_once __DIR__ . '/AuditContext.php';
final class ReturnManagement
{
    public static function rows(mysqli $conn, string $sql, string $types = '', array $params = []): array
    {
        $stmt = $conn->prepare($sql);
        try {
            if ($types !== '') $stmt->bind_param($types, ...$params);
            $stmt->execute(); return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } finally { $stmt->close(); }
    }
    public static function actor(mysqli $conn, int $actor, bool $lock = false): void
    {
        $user = self::rows($conn, 'SELECT id,role_id,status,failed_login_attempts,locked_until FROM users WHERE id=?' . ($lock ? ' FOR UPDATE' : ''), 'i', [$actor])[0] ?? null;
        if (!$user || (int)$user['role_id'] !== 1 || $user['status'] !== 'active'
            || isPureViaLoginLocked($conn, $user, securityPolicy($conn))) throw new DomainException('Active administrator access is required.');
    }
    public static function details(mysqli $conn, int $actor, int $id): array
    {
        self::actor($conn, $actor);
        $row = self::rows($conn, 'SELECT r.*,o.order_number,o.first_name,o.last_name FROM order_return_requests r JOIN orders o ON o.id=r.order_id WHERE r.id=?', 'i', [$id])[0] ?? null;
        if (!$row) throw new OutOfBoundsException('Return request not found.');
        $row['items'] = self::rows($conn, 'SELECT i.id,i.product_id,i.product_name,i.unit_price,ri.quantity FROM order_return_items ri JOIN order_items i ON i.id=ri.order_item_id WHERE ri.return_request_id=? ORDER BY i.id', 'i', [$id]);
        foreach ($row['items'] as &$item) {
            $item['variants'] = self::rows($conn, 'SELECT id,size_capacity,variant_label FROM product_variants WHERE product_id=? ORDER BY id', 'i', [(int)$item['product_id']]);
        }
        unset($item);
        $row['evidence'] = json_decode($row['evidence_json'] ?? '[]', true) ?: [];
        unset($row['evidence_json']);
        return $row;
    }
    public static function save(mysqli $conn, int $actor, array $data): void
    {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $revision = filter_var($data['revision'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
        $status = $data['status'] ?? null; $note = $data['internal_note'] ?? ''; $reference = $data['resolution_reference'] ?? '';
        if (!$id || $revision === false || !is_string($status) || !in_array($status, ['pending','approved','rejected','completed'], true)
            || !is_string($note) || !is_string($reference) || !mb_check_encoding($note . $reference, 'UTF-8') || mb_strlen($note)>2000 || mb_strlen($reference)>200) throw new InvalidArgumentException('Check the status, reference and review notes.');
        $note = trim($note); $reference = trim($reference);
        $restock = $data['restock'] ?? [];
        if (!is_array($restock) || count($restock)>200) throw new InvalidArgumentException('Invalid restock selection.');
        $conn->begin_transaction();
        try {
            self::actor($conn, $actor, true);
            $row = self::rows($conn, 'SELECT * FROM order_return_requests WHERE id=? FOR UPDATE', 'i', [$id])[0] ?? null;
            if (!$row) throw new OutOfBoundsException('Return request not found.');
            if ((int)$row['revision'] !== $revision) throw new DomainException('This return changed. Close and reopen Manage to load the latest record.');
            $allowed = ['pending'=>['pending','approved','rejected'], 'approved'=>['approved','rejected','completed']];
            if (!in_array($status, $allowed[$row['status']] ?? [], true)) throw new DomainException('This return cannot move to that status. Completed and rejected requests are read-only.');
            if ($note === '') throw new InvalidArgumentException('Add a review note explaining this decision.');
            if ($status === 'completed' && ($reference === '' || ($data['confirm_resolution'] ?? '') !== '1')) throw new InvalidArgumentException('Confirm the refund or replacement is already fulfilled and enter its transaction or shipment reference.');
            $items = self::rows($conn, 'SELECT i.id,i.product_id,ri.quantity FROM order_return_items ri JOIN order_items i ON i.id=ri.order_item_id WHERE ri.return_request_id=? ORDER BY i.id', 'i', [$id]);
            $byId = array_column($items, null, 'id'); $stock = []; $stockProductIds = [];
            foreach ($restock as $itemId=>$variantId) {
                if (!is_scalar($variantId) || ($variantId=filter_var($variantId,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]))===false || !isset($byId[$itemId])) throw new InvalidArgumentException('Invalid returned product selection.');
                if (!$variantId) continue;
                if ($status !== 'completed' || $row['restocked_at'] !== null || ($data['confirm_inspected'] ?? '') !== '1') throw new InvalidArgumentException('Restock only received, inspected and sellable items when completing the return.');
                $item = $byId[$itemId];
                $variant = self::rows($conn, 'SELECT product_id FROM product_variants WHERE id=?', 'i', [$variantId])[0] ?? null;
                if (!$variant || (int)$variant['product_id'] !== (int)$item['product_id']) throw new InvalidArgumentException('Choose a size belonging to the returned product.');
                $stock[$variantId] = ($stock[$variantId] ?? 0) + (int)$item['quantity'];
                $stockProductIds[(int)$item['product_id']] = true;
            }
            $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: null;
            $browser = pureViaAuditBrowser();
            if ($stock) {
                $productIds = array_keys($stockProductIds); sort($productIds);
                foreach ($productIds as $productId) self::rows($conn, 'SELECT id FROM products WHERE id=? FOR UPDATE', 'i', [$productId]);
                ksort($stock);
                foreach ($stock as $variantId=>$quantity) {
                    $v = self::rows($conn, 'SELECT id,product_id,stock_quantity,low_stock_threshold FROM product_variants WHERE id=? FOR UPDATE', 'i', [$variantId])[0] ?? null;
                    if (!$v || $quantity < 1 || (int)$v['stock_quantity']+$quantity>2147483647) throw new DomainException('This size is unavailable or its stock exceeds the allowed limit.');
                    $before=(int)$v['stock_quantity']; $after=$before+$quantity; $threshold=(int)$v['low_stock_threshold']; $productId=(int)$v['product_id'];
                    $notes='Return RET-'.$id.': '.$note; $key=hash('sha256','return:'.$id.':variant:'.$variantId); $fingerprint=hash('sha256',$key.':'.$quantity);
                    $stmt=$conn->prepare("INSERT INTO inventory_adjustments(variant_id,product_id,user_id,adjustment_type,reason,quantity,stock_before,stock_after,threshold_before,threshold_after,notes,request_key,request_fingerprint,ip_address,browser) VALUES(?,?,?,'add','customer_return',?,?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('iiiiiiiisssss',$variantId,$productId,$actor,$quantity,$before,$after,$threshold,$threshold,$notes,$key,$fingerprint,$ip,$browser); $stmt->execute(); $stmt->close();
                    $stmt=$conn->prepare('UPDATE product_variants SET stock_quantity=?,last_restocked_at=NOW() WHERE id=?'); $stmt->bind_param('ii',$after,$variantId);$stmt->execute();$stmt->close();
                }
                foreach ($productIds as $productId) {
                    $stmt=$conn->prepare("UPDATE products SET stock_quantity=(SELECT COALESCE(SUM(v.stock_quantity),0) FROM product_variants v WHERE v.product_id=?),status=IF(status='out_of_stock','active',status) WHERE id=?");$stmt->bind_param('ii',$productId,$productId);$stmt->execute();$stmt->close();
                }
            }
            $restocked = $stock ? date('Y-m-d H:i:s') : $row['restocked_at'];
            $stmt=$conn->prepare('UPDATE order_return_requests SET status=?,internal_note=?,resolution_reference=?,reviewed_by=?,restocked_at=?,revision=revision+1 WHERE id=?');
            $stmt->bind_param('sssisi',$status,$note,$reference,$actor,$restocked,$id);$stmt->execute();$stmt->close();
            $action='UPDATE';$module='Returns';$record=(string)$id;$description='Return '.$row['status'].' to '.$status.'. '.$note.($stock?' Inspected sellable stock restored.':' Stock unchanged.');
            $stmt=$conn->prepare('INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address,browser) VALUES(?,?,?,?,?,?,?)');$stmt->bind_param('issssss',$actor,$action,$module,$record,$description,$ip,$browser);$stmt->execute();$stmt->close();
            $conn->commit();
        } catch (Throwable $error) { $conn->rollback(); throw $error; }
    }
}