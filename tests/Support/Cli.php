<?php

namespace Tests\Support;

/**
 * Runs a PHP script as a subprocess and captures its output.
 */
final class Cli
{
    /**
     * @param list<string> $args
     * @return array{exit: int, stdout: string, stderr: string}
     */
    public static function run(string $script, array $args, string $cwd): array
    {
        $command = array_merge([PHP_BINARY, '-d', 'display_errors=stderr', $script], $args);

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }
}
