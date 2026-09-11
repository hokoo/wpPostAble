<?php

declare(strict_types=1);

function packageSmokeAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, 'Package install smoke failed: ' . $message . PHP_EOL);
        exit(1);
    }
}

function packageSmokeTypeName($type)
{
    return $type instanceof ReflectionNamedType ? $type->getName() : null;
}

function packageSmokeAssertSignature(ReflectionMethod $method, array $expected): void
{
    packageSmokeAssert($method->isPublic(), "{$method->getName()} is not public");
    packageSmokeAssert(
        $expected['return'] === packageSmokeTypeName($method->getReturnType()),
        "{$method->getName()} return type differs from the frozen contract"
    );
    if (null !== $method->getReturnType()) {
        packageSmokeAssert(!$method->getReturnType()->allowsNull(), "{$method->getName()} return type became nullable");
    }
    packageSmokeAssert(
        count($expected['parameters']) === $method->getNumberOfParameters(),
        "{$method->getName()} parameter count differs from the frozen contract"
    );

    foreach ($method->getParameters() as $index => $parameter) {
        list($name, $type) = $expected['parameters'][$index];
        packageSmokeAssert($name === $parameter->getName(), "{$method->getName()} parameter {$index} changed name");
        packageSmokeAssert(
            $type === packageSmokeTypeName($parameter->getType()),
            "{$method->getName()} parameter {$name} changed type"
        );
        if (null !== $parameter->getType()) {
            packageSmokeAssert(!$parameter->getType()->allowsNull(), "{$method->getName()} parameter {$name} became nullable");
        }
        packageSmokeAssert(!$parameter->isDefaultValueAvailable(), "{$method->getName()} parameter {$name} became optional");
        packageSmokeAssert(!$parameter->isPassedByReference(), "{$method->getName()} parameter {$name} became a reference");
        packageSmokeAssert(!$parameter->isVariadic(), "{$method->getName()} parameter {$name} became variadic");
    }
}

$consumerDirectory = isset($argv[1]) ? rtrim($argv[1], DIRECTORY_SEPARATOR) : '';
$archiveFile = $argv[2] ?? '';
$evidenceFile = $argv[3] ?? '';
$composerVersion = $argv[4] ?? '';
$artifactSource = $argv[5] ?? '';
$targetCommit = $argv[6] ?? '';
$autoloadFile = $consumerDirectory . '/vendor/autoload.php';
$installedDirectory = $consumerDirectory . '/vendor/hokoo/wppostable';

packageSmokeAssert('' !== $consumerDirectory, 'consumer directory argument is missing');
packageSmokeAssert(is_file($autoloadFile), 'production autoloader is missing');
packageSmokeAssert(is_file($archiveFile), 'generated package archive is missing');
packageSmokeAssert('' !== $evidenceFile, 'evidence path argument is missing');
packageSmokeAssert('' !== $composerVersion, 'Composer version argument is missing');
packageSmokeAssert(
    1 === preg_match('/^[a-z0-9_-]+$/D', $artifactSource),
    'artifact source is missing or malformed'
);
packageSmokeAssert(
    1 === preg_match('/^[0-9a-f]{40}$/D', $targetCommit),
    'target commit is missing or malformed'
);
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
$installedIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($installedDirectory, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($installedIterator as $installedEntry) {
    $relativePath = substr($installedEntry->getPathname(), strlen($installedDirectory) + 1);
    packageSmokeAssert(!$installedEntry->isLink(), "package archive contains symlink {$relativePath}");
    packageSmokeAssert(
        1 !== preg_match('#(?:^|/)\.env[^/]*$#D', $relativePath),
        "package archive contains environment-like path {$relativePath}"
    );
}
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
packageSmokeAssert(false !== $installedRealPath, 'cannot resolve installed package directory');
$symbolFiles = array();
foreach (array_merge($interfaces, $traits, $classes) as $symbol) {
    $symbolFile = (new ReflectionClass($symbol))->getFileName();
    $symbolRealPath = false !== $symbolFile ? realpath($symbolFile) : false;
    packageSmokeAssert(false !== $symbolRealPath, "cannot resolve public symbol file {$symbol}");
    packageSmokeAssert(
        0 === strpos($symbolRealPath, $installedRealPath . DIRECTORY_SEPARATOR),
        "public symbol {$symbol} loaded from outside the installed package"
    );
    $symbolFiles[$symbol] = substr($symbolRealPath, strlen($installedRealPath) + 1);
}

$publicApiSignatures = array(
    'getPost' => array('return' => 'WP_Post', 'parameters' => array()),
    'savePost' => array('return' => 'self', 'parameters' => array()),
    'deletePost' => array('return' => 'void', 'parameters' => array()),
    'getPostType' => array('return' => 'string', 'parameters' => array()),
    'getTitle' => array('return' => 'string', 'parameters' => array()),
    'setTitle' => array('return' => 'self', 'parameters' => array(array('title', 'string'))),
    'getSlug' => array('return' => 'string', 'parameters' => array()),
    'setSlug' => array('return' => 'self', 'parameters' => array(array('slug', 'string'))),
    'getMenuOrder' => array('return' => 'int', 'parameters' => array()),
    'setMenuOrder' => array('return' => 'self', 'parameters' => array(array('menuOrder', 'int'))),
    'getStatus' => array('return' => 'string', 'parameters' => array()),
    'setStatus' => array('return' => 'self', 'parameters' => array(array('status', 'string'))),
    'setMetaField' => array(
        'return' => 'self',
        'parameters' => array(array('meta_key', 'string'), array('meta_value', null)),
    ),
    'getMetaField' => array('return' => null, 'parameters' => array(array('meta_key', 'string'))),
    'getMetaFields' => array('return' => 'array', 'parameters' => array()),
    'getParam' => array('return' => null, 'parameters' => array(array('param', 'string'))),
    'setParam' => array(
        'return' => 'void',
        'parameters' => array(array('param', 'string'), array('value', null)),
    ),
    'publish' => array('return' => 'self', 'parameters' => array()),
    'draft' => array('return' => 'self', 'parameters' => array()),
);
$interfaceReflection = new ReflectionClass('iTRON\\wpPostAble\\wpPostAble');
$traitReflection = new ReflectionClass('iTRON\\wpPostAble\\wpPostAbleTrait');
$expectedMethodNames = array_keys($publicApiSignatures);
$interfaceMethodNames = array_map(
    static function (ReflectionMethod $method): string {
        return $method->getName();
    },
    $interfaceReflection->getMethods(ReflectionMethod::IS_PUBLIC)
);
$traitMethodNames = array_map(
    static function (ReflectionMethod $method): string {
        return $method->getName();
    },
    $traitReflection->getMethods(ReflectionMethod::IS_PUBLIC)
);
sort($expectedMethodNames);
sort($interfaceMethodNames);
sort($traitMethodNames);
packageSmokeAssert($expectedMethodNames === $interfaceMethodNames, 'installed interface method set changed');
packageSmokeAssert($expectedMethodNames === $traitMethodNames, 'installed trait public method set changed');

foreach ($publicApiSignatures as $methodName => $signature) {
    packageSmokeAssertSignature($interfaceReflection->getMethod($methodName), $signature);
    packageSmokeAssertSignature($traitReflection->getMethod($methodName), $signature);
}
packageSmokeAssert($traitReflection->getProperty('post')->isPrivate(), 'installed trait $post property is not private');
foreach (array('wpPostAble', 'loadPost', 'loadPostObject') as $privateMethodName) {
    packageSmokeAssert($traitReflection->getMethod($privateMethodName)->isPrivate(), "{$privateMethodName} is not private");
}

$installedManifest = json_decode((string) file_get_contents($installedDirectory . '/composer.json'), true);
packageSmokeAssert(is_array($installedManifest), 'installed composer.json is invalid');
packageSmokeAssert('hokoo/wppostable' === ($installedManifest['name'] ?? null), 'installed package name changed');
packageSmokeAssert('library' === ($installedManifest['type'] ?? null), 'installed package type changed');
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
        'source' => $artifactSource,
        'target_commit' => $targetCommit,
        'format' => 'zip',
        'observed_sha256' => hash_file('sha256', $archiveFile),
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
        'files' => $symbolFiles,
    ),
    'public_api' => array(
        'method_count' => count($publicApiSignatures),
        'signatures_verified' => true,
        'private_composition_seams_verified' => true,
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
