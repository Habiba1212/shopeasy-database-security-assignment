<?php

if (!function_exists('logAudit')) {
    function logAudit($pdo, $user_id, $action, $description)
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_log
                (
                    user_id,
                    action_type,
                    action_description,
                    created_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    CURRENT_TIMESTAMP
                )
            ");

            $stmt->execute([
                $user_id,
                $action,
                $description
            ]);

        } catch (Exception $e) {
            // Silent fail for audit logging
        }
    }
}
?>
