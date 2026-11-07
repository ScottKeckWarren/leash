<?php

pest()->extend(Tests\TestCase::class)->in('Feature', 'Unit');

/**
 * Create a fresh, empty temporary directory and return its path.
 */
function makeTempDir(): string
{
    $dir = sys_get_temp_dir() . '/leash-test-' . bin2hex(random_bytes(6));
    mkdir($dir, 0777, true);

    return $dir;
}

/**
 * Recursively delete a directory created by makeTempDir().
 */
function removeDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
}
