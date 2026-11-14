<?php declare(strict_types=1);

$mode = $argv[1] ?? '';

if ($mode === 'sleep') {
    sleep(3);

    exit(0);
}

if ($mode === 'long-output') {
    echo str_repeat('a', 20_000) . 'END';

    exit(1);
}

echo 'ok';

exit(0);
