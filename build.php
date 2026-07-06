<?php

/**
 * Build script for pkg_nriforms.
 * Usage: php build.php
 * Produces dist/pkg_nriforms-<version>.zip from the source tree.
 */

$root = __DIR__;

// Read the version from the package manifest.
$manifest = file_get_contents($root . '/pkg_nriforms.xml');
preg_match('~<version>([^<]+)</version>~', $manifest, $m);
$version = $m[1] ?? 'dev';

@mkdir($root . '/build', 0755, true);
@mkdir($root . '/dist', 0755, true);

function zipDir(string $sourceDir, string $zipPath): void
{
    @unlink($zipPath);
    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        fwrite(STDERR, "Cannot create $zipPath\n");
        exit(1);
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($sourceDir) + 1));
    }

    $zip->close();
    echo "  built " . basename($zipPath) . "\n";
}

echo "Building pkg_nriforms $version\n";

zipDir($root . '/com_nriforms', $root . '/build/com_nriforms.zip');
zipDir($root . '/plg_fields_nriinputs', $root . '/build/plg_fields_nriinputs.zip');
zipDir($root . '/plg_system_nriforms', $root . '/build/plg_system_nriforms.zip');

// Assemble the package zip: manifest + README + the three extension zips
// under packages/.
$pkgPath = $root . '/dist/pkg_nriforms-' . $version . '.zip';
@unlink($pkgPath);
$zip = new ZipArchive();

if ($zip->open($pkgPath, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Cannot create $pkgPath\n");
    exit(1);
}

$zip->addFile($root . '/pkg_nriforms.xml', 'pkg_nriforms.xml');
$zip->addFile($root . '/README.md', 'README.md');
$zip->addFile($root . '/build/com_nriforms.zip', 'packages/com_nriforms.zip');
$zip->addFile($root . '/build/plg_fields_nriinputs.zip', 'packages/plg_fields_nriinputs.zip');
$zip->addFile($root . '/build/plg_system_nriforms.zip', 'packages/plg_system_nriforms.zip');
$zip->close();

echo "Package: dist/pkg_nriforms-$version.zip\n";
