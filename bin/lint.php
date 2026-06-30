<?php
/**
 * Portable replacement for `find ... | xargs php -l`.
 *
 * That pipeline only works in a Unix-like shell with GNU find/xargs.
 * On Windows PowerShell, `find` resolves to FIND.EXE (a totally
 * different tool) and `xargs` doesn't exist at all. Doing the walk in
 * PHP itself means `composer run lint` works the same way on Windows,
 * macOS and Linux.
 */

$root = dirname(__DIR__);
$excluded = [
    DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR,
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$hadError = false;
$checked = 0;

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();

    foreach ($excluded as $exclude) {
        if (strpos($path, $exclude) !== false) {
            continue 2;
        }
    }

    $checked++;

    $output = [];
    $status = 0;
    exec('php -l ' . escapeshellarg($path) . ' 2>&1', $output, $status);

    if ($status !== 0) {
        $hadError = true;
        echo implode("\n", $output), "\n";
    }
}

if ($hadError) {
    fwrite(STDERR, "Lint failed.\n");
    exit(1);
}

echo "OK — {$checked} PHP files linted, no syntax errors.\n";
exit(0);
