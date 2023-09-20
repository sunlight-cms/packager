<?php

namespace SunlightPackager;

function log(string $msg, ...$params): void
{
    vprintf($msg, $params);
    echo "\n";
}

function fail(string $msg, ...$params): void
{
    throw new FailureException(vsprintf($msg, $params));
}

function render_template(string $name, array $replacements): string
{
    $template = file_get_contents(__DIR__ . '/../template/' . $name);

    return strtr($template, $replacements);
}

function exec_formatted(string $cmd, ...$params): array
{
    $cmd = vsprintf($cmd, array_map('escapeshellarg', $params));

    exec($cmd, $output, $status);

    $status === 0 or fail('Command `%s` has failed with exit code %d', $cmd, $status);

    return $output;
}

function exception_handler(\Throwable $e): void
{
    fwrite(STDERR, sprintf("ERROR: %s\n", $e->getMessage()));

    if (!$e instanceof FailureException) {
        fwrite(STDERR, "\n");
        fwrite(STDERR, $e->getTraceAsString());
        fwrite(STDERR, "\n");
    }

    exit(1);
}

function error_handler($severity, $message, $file, $line) {
    if ($severity & error_reporting()) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    return true;
}
