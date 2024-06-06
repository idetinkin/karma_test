<?php
declare(strict_types=1);

require dirname(__DIR__) . '/php/Db.php';

$options = getopt("p:u:");
$numProcesses = $options['p'] ?? 10;
$totalRecords = $options['u'] ?? 5000000;
$recordsPerProcess = (int)($totalRecords / $numProcesses);

function randomTimestamp(): ?string {
    if (mt_rand(1, 100) <= 80) {
        return null;
    } elseif (mt_rand(1, 100) <= 90) {
        return date('Y-m-d H:i:s', strtotime('+' . mt_rand(1, 5) . ' days'));
    } else {
        return date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 3) . ' days'));
    }
}

function randomConfirmed(): int {
    return mt_rand(1, 100) <= 15 ? 1 : 0;
}

function populate(int $start, int $end): void {
    $conn = getDbConnection();
    $conn->beginTransaction();
    try {
        for ($i = $start; $i <= $end; $i++) {
            $username = 'user' . $i;
            $email = 'user' . $i . '@example.com';
            $validts = randomTimestamp();
            $confirmed = randomConfirmed();
            $checked = 0;
            $valid = 0;
            $notified1 = null;
            $notified3 = null;

            $sql = "INSERT INTO users (username, email, validts, confirmed, checked, valid, notified1, notified3, can_receive_emails) 
                    VALUES (:username, :email, :validts, :confirmed, :checked, :valid, :notified1, :notified3, :can_receive_emails)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':validts' => $validts,
                ':confirmed' => $confirmed,
                ':checked' => $checked,
                ':valid' => $valid,
                ':notified1' => $notified1,
                ':notified3' => $notified3,
                ':can_receive_emails' => $confirmed,
            ]);

            if ($i % 1000 == 0) {
                $conn->commit();
                $conn->beginTransaction();
            }
        }
        $conn->commit();
        echo "Process $start to $end completed.\n";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Failed to populate users table: " . $e->getMessage() . "\n";
    }
}

if ($numProcesses > 1 && !function_exists('pcntl_fork')) {
    echo "PCNTL functions not available on this PHP installation, fallback to single thread mode";
    $numProcesses = 1;
}

$startTime = microtime(true);

for ($i = 0; $i < $numProcesses; $i++) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        die('could not fork');
    } elseif ($pid) {
        continue;
    } else {
        $start = $i * $recordsPerProcess + 1;
        $end = ($i + 1) * $recordsPerProcess;
        populate((int)$start, (int)$end);
        exit(0);
    }
}

// Wait for all child processes to complete
while (pcntl_wait($status) != -1);

$endTime = microtime(true);
$executionTime = $endTime - $startTime;
echo "All processes completed in $executionTime seconds.\n";
?>
