<?php
declare(strict_types=1);

require_once __DIR__ . '/Db.php';
require_once __DIR__ . '/Common.php';
require_once __DIR__ . '/CheckEmail.php';

$options = getopt("l:p:");
$numProcesses = (int)($options['p'] ?? 1);
$limit = (int)($options['l'] ?? 10);

function getAndLockUsersForValidation(PDO $conn, int $limit): array
{
    if (!$conn->beginTransaction()) {
        die('Cannot begin transaction');
    }

    $query = "
        SELECT id, email, can_receive_emails
        FROM users
        WHERE confirmed = 0
        AND validts < NOW() + INTERVAL 96 HOUR
        AND checked = 0
        AND (locked_for_validation IS NULL OR locked_for_validation < NOW() - INTERVAL 1 HOUR)
        ORDER BY id
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
        $query = 'UPDATE users SET locked_for_validation = NOW() WHERE id IN (' . $placeholders . ')';
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

function validateEmails(PDO $conn, int $limit = 10): bool
{
    $users = getAndLockUsersForValidation($conn, $limit);
    if (!$users) {
        return false;
    }
    foreach ($users as $user) {
        $isEmailValid = check_email($user['email']);

        $canReceiveEmails = $user['can_receive_emails'];
        if ($isEmailValid === 1) {
            $canReceiveEmails = 1;
        }

        $stmt = $conn->prepare("UPDATE users SET checked = 1, valid = :valid, can_receive_emails = :can_receive_emails, locked_for_validation = null WHERE id = :id");
        $result = $stmt->execute(
            [
                'valid' => $isEmailValid,
                'can_receive_emails' => $canReceiveEmails,
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
        return validateEmails($conn, $limit);
    },
);