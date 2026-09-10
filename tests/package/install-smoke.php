<?php

declare(strict_types=1);

function packageSmokeAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, 'Package install smoke failed: ' . $message . PHP_EOL);
        exit(1);
    }
}

$consumerDirectory = isset($argv[1]) ? rtrim($argv[1], DIRECTORY_SEPARATOR) : '';
$archiveFile = $argv[2] ?? '';
$evidenceFile = $argv[3] ?? '';
$composerVersion = $argv[4] ?? '';
$autoloadFile = $consumerDirectory . '/vendor/autoload.php';
$installedDirectory = $consumerDirectory . '/vendor/hokoo/wppostable';

packageSmokeAssert('' !== $consumerDirectory, 'consumer directory argument is missing');
packageSmokeAssert(is_file($autoloadFile), 'production autoloader is missing');
packageSmokeAssert(is_file($archiveFile), 'generated package archive is missing');
packageSmokeAssert('' !== $evidenceFile, 'evidence path argument is missing');
packageSmokeAssert('' !== $composerVersion, 'Composer version argument is missing');
packageSmokeAssert(is_dir($installedDirectory), 'package was not installed under vendor/');
packageSmokeAssert(!is_link($installedDirectory), 'path package was symlinked instead of mirrored');

$allowedPackageEntries = array(
    'CHANGELOG.md',
    'CONTRIBUTING.md',
    'LICENSE',
    'README.md',
    'VERSIONING.md',
    'composer.json',
    'docs',
    'src',
);
$packageEntries = array_values(array_diff(scandir($installedDirectory) ?: array(), array('.', '..')));
sort($allowedPackageEntries);
sort($packageEntries);
packageSmokeAssert(
    $allowedPackageEntries === $packageEntries,
    'package archive root differs from the production allowlist: ' . json_encode($packageEntries)
);

$forbiddenPackagePaths = array(
    '.agents',
    '.codex',
    '.env',
    '.env.localdev',
    '.github',
    '.idea',
    'coverage',
    'docker',
    'local-dev',
    'scripts',
    'tests',
    'vendor',
);
foreach ($forbiddenPackagePaths as $relativePath) {
    packageSmokeAssert(
        !file_exists($installedDirectory . '/' . $relativePath),
        "package archive contains forbidden development path {$relativePath}"
    );
}
packageSmokeAssert(
    array() === (glob($installedDirectory . '/.env*') ?: array()),
    'package archive contains an environment file'
);
packageSmokeAssert(!is_dir($consumerDirectory . '/vendor/phpunit'), 'PHPUnit was installed by a --no-dev install');
packageSmokeAssert(!is_dir($consumerDirectory . '/vendor/brain'), 'Brain Monkey was installed by a --no-dev install');

require $autoloadFile;

$interfaces = array(
    'iTRON\\wpPostAble\\wpPostAble',
    'iTRON\\wpPostAble\\Exceptions\\wpException',
);
$traits = array(
    'iTRON\\wpPostAble\\wpPostAbleTrait',
);
$classes = array(
    'iTRON\\wpPostAble\\Exceptions\\wppaException',
    'iTRON\\wpPostAble\\Exceptions\\wppaCreatePostException',
    'iTRON\\wpPostAble\\Exceptions\\wppaLoadPostException',
    'iTRON\\wpPostAble\\Exceptions\\wppaSavePostException',
    'iTRON\\wpPostAble\\Exceptions\\wppaDeletePostException',
    'iTRON\\wpPostAble\\Exceptions\\wppaParamException',
);

foreach ($interfaces as $interface) {
    packageSmokeAssert(interface_exists($interface), "public interface {$interface} did not autoload");
}
foreach ($traits as $trait) {
    packageSmokeAssert(trait_exists($trait), "public trait {$trait} did not autoload");
}
foreach ($classes as $class) {
    packageSmokeAssert(class_exists($class), "public class {$class} did not autoload");
}

$installedRealPath = realpath($installedDirectory);
$interfaceFile = (new ReflectionClass('iTRON\\wpPostAble\\wpPostAble'))->getFileName();
$interfaceRealPath = false !== $interfaceFile ? realpath($interfaceFile) : false;
packageSmokeAssert(false !== $installedRealPath, 'cannot resolve installed package directory');
packageSmokeAssert(false !== $interfaceRealPath, 'cannot resolve autoloaded interface file');
packageSmokeAssert(
    0 === strpos($interfaceRealPath, $installedRealPath . DIRECTORY_SEPARATOR),
    'public API loaded from the source checkout instead of the installed package'
);

$installedManifest = json_decode((string) file_get_contents($installedDirectory . '/composer.json'), true);
packageSmokeAssert(is_array($installedManifest), 'installed composer.json is invalid');
packageSmokeAssert('hokoo/wppostable' === ($installedManifest['name'] ?? null), 'installed package name changed');
packageSmokeAssert(!array_key_exists('version', $installedManifest), 'package hardcodes a Composer version');
packageSmokeAssert('>=7.4' === ($installedManifest['require']['php'] ?? null), 'PHP runtime constraint changed');
packageSmokeAssert('*' === ($installedManifest['require']['ext-json'] ?? null), 'JSON extension constraint changed');
packageSmokeAssert(
    'src' === ($installedManifest['autoload']['psr-4']['iTRON\\wpPostAble\\'] ?? null),
    'public PSR-4 mapping changed'
);

$lock = json_decode((string) file_get_contents($consumerDirectory . '/composer.lock'), true);
packageSmokeAssert(is_array($lock), 'isolated consumer lock file is invalid');
packageSmokeAssert(array() === ($lock['packages-dev'] ?? null), 'development packages are present in the consumer lock');

$installedPackage = null;
foreach ($lock['packages'] ?? array() as $package) {
    if ('hokoo/wppostable' === ($package['name'] ?? null)) {
        $installedPackage = $package;
        break;
    }
}
packageSmokeAssert(is_array($installedPackage), 'consumer lock does not contain hokoo/wppostable');
packageSmokeAssert('0.0.0' === ($installedPackage['version'] ?? null), 'unexpected synthetic smoke version');

$summary = array(
    'schema_version' => 1,
    'status' => 'pass',
    'package' => 'hokoo/wppostable',
    'version_source' => 'external-test-metadata',
    'synthetic_version' => '0.0.0',
    'artifact' => array(
        'format' => 'zip',
        'sha256' => hash_file('sha256', $archiveFile),
        'bytes' => filesize($archiveFile),
        'root_entries' => $packageEntries,
    ),
    'install' => array(
        'no_dev' => true,
        'mirrored' => true,
        'classmap_authoritative' => true,
    ),
    'runtime' => array(
        'php' => PHP_VERSION,
        'composer' => $composerVersion,
    ),
    'public_symbols' => array(
        'interfaces' => $interfaces,
        'traits' => $traits,
        'classes' => $classes,
    ),
    'composer_lock' => array(
        'content_hash' => $lock['content-hash'] ?? null,
        'package_count' => count($lock['packages'] ?? array()),
        'development_package_count' => count($lock['packages-dev'] ?? array()),
    ),
);

$evidenceDirectory = dirname($evidenceFile);
packageSmokeAssert(
    is_dir($evidenceDirectory) || mkdir($evidenceDirectory, 0777, true) || is_dir($evidenceDirectory),
    'cannot create package evidence directory'
);
$json = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
packageSmokeAssert(false !== $json, 'cannot encode package evidence');
packageSmokeAssert(
    false !== file_put_contents($evidenceFile, $json . PHP_EOL),
    'cannot write package evidence'
);

printf("Package install smoke passed: %s\n", $evidenceFile);
