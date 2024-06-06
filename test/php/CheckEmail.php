<?php
declare(strict_types=1);

function check_email(string $email): int
{
    echo "Checking email $email\n";
    sleep(rand(1, 60));
    return rand(1, 100) > 10 ? 1 : 0;
}
?>
