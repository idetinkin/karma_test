<?php
declare(strict_types=1);

function send_email(string $from, string $to, string $text): void
{
    echo "Sending email to $to\n";
    sleep(rand(1, 10));
}
?>
