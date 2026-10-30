<?php
/** Assign SKUs from MySQL's auto-increment ID, within the product save transaction. */
final class ProductCode
{
    public static function assign(mysqli $conn, int $productId): string
    {
        if ($productId < 1) {
            throw new InvalidArgumentException('Invalid product ID.');
        }
        $base = 'PRD-' . str_pad((string) $productId, 6, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare('UPDATE products SET product_code = ? WHERE id = ?');
        try {
            // Preserve manually assigned legacy SKUs if one already uses this code.
            for ($suffix = 1; $suffix <= 100; $suffix++) {
                $code = $base . ($suffix === 1 ? '' : '-' . $suffix);
                $stmt->bind_param('si', $code, $productId);
                try {
                    $stmt->execute();
                    return $code;
                } catch (mysqli_sql_exception $exception) {
                    if ((int) $exception->getCode() !== 1062) {
                        throw $exception;
                    }
                }
            }
            throw new RuntimeException('Unable to assign a unique product SKU.');
        } finally {
            $stmt->close();
        }
    }
}