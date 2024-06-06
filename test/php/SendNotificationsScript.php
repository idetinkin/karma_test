<?php
declare(strict_types=1);

require_once __DIR__ . '/Db.php';
require_once __DIR__ . '/Common.php';
require_once __DIR__ . '/SendEmail.php';

$options = getopt("l:p:");
$numProcesses = (int)($options['p'] ?? 1);
$limit = (int)($options['l'] ?? 10);

function getAndLockUsersForNotifying(PDO $conn, int $limit): array
{
    if (!$conn->beginTransaction()) {
        die('Cannot begin transaction');
    }

    $query = "
        SELECT id, email, username,
               (CASE WHEN validts < NOW() + INTERVAL 24 HOUR THEN 1 ELSE 0 END) AS need_to_notify1
        FROM users
        WHERE can_receive_emails = 1
        AND validts BETWEEN NOW() AND (NOW() + INTERVAL 72 HOUR)
        AND (
          (notified3 IS NULL) OR
          (validts < NOW() + INTERVAL 24 HOUR AND notified1 IS NULL)
        )
        AND (locked_for_sending IS NULL OR locked_for_sending < NOW() - INTERVAL 1 HOUR)
        ORDER BY validts
        LIMIT :limit
        FOR UPDATE
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute(
        [
            ':limit' => $limit,
        ]
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        $ids = array_column($rows, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = 'UPDATE users SET locked_for_sending = NOW() WHERE id IN (' . $placeholders . ')';
        $stmt = $conn->prepare($query);
        $result = $stmt->execute($ids);

        if (!$result) {
            $conn->rollBack();
            die('Cannot update the users table');
        }
    }

    if (!$conn->commit()) {
        $conn->rollBack();
        die('Cannot commit transaction');
    }

    return $rows;
}

function sendNotifications(PDO $conn, int $limit = 0): bool
{
    $users = getAndLockUsersForNotifying($conn, $limit);
    if (!$users) {
        return false;
    }
    foreach ($users as $user) {
        send_email(
            'no-reply@example.com',
            $user['email'],
            "{$user['username']}, your subscription is expiring soon"
        );

        $notified1 = $user['need_to_notify1'] == 1 ? 'NOW()' : 'NULL';
        $notified3 = 'IFNULL(notified3, NOW())';

        $stmt = $conn->prepare("UPDATE users SET notified1 = $notified1, notified3 = $notified3, locked_for_sending = null WHERE id = :id");
        $result = $stmt->execute(
            [
                'id' => $user['id'],
            ],
        );
        if (!$result) {
            die('Cannot update the users table');
        }
    }
    return true;
}

runInParallel(
    $numProcesses,
    function(PDO $conn) use ($limit) {
        return sendNotifications($conn, $limit);
    },
);