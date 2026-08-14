<?php

function akim_log(string $msg, string $level = 'INFO'): void
{
    $ts   = date('Y-m-d H:i:s');
    $line = "[$ts][$level] $msg" . PHP_EOL;
    error_log($line, 3, __DIR__ . '/../logs/akim.log');
}

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    akim_log("E{$errno}: {$errstr} en {$errfile}:{$errline}", 'ERROR');
    return false;
});
