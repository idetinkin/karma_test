<?php
declare(strict_types=1);

function runInParallel(int $numProcesses, callable $callback): void
{
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
            $conn = getDbConnection();
            while (true) {
                try {
                    $result = $callback($conn);
                    if (!$result) {
                        break;
                    }
                } catch (PDOException $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }

                    // Check if it is a deadlock and retry
                    if ($e->getCode() === '40001') {
                        usleep(random_int(100, 1000));
                        continue;
                    }
                    throw $e;
                }
            }
            exit(0);
        }
    }

    while (pcntl_wait($status) != -1);

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    echo "All processes completed in $executionTime seconds.\n";
}
