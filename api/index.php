<?php

$lockFile = '/tmp/migrated.lock';

if (!file_exists($lockFile)) {
    $projectRoot = __DIR__ . '/..';
    shell_exec("cd $projectRoot && php artisan migrate --force 2>&1");
    shell_exec("cd $projectRoot && php artisan db:seed --class=AdminSeeder --force 2>&1");
    file_put_contents($lockFile, '1');
}

require __DIR__ . '/../public/index.php';