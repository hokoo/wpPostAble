<?php

declare(strict_types=1);

const REQUIRED_LINE_PERCENT = 100.0;
const REQUIRED_METHOD_PERCENT = 100.0;

/**
 * @return array<string, int>
 */
function readProjectMetrics(string $cloverFile): array
{
    if (!is_file($cloverFile) || !is_readable($cloverFile)) {
        throw new RuntimeException("Clover report is missing or unreadable: {$cloverFile}");
    }

    $contents = file_get_contents($cloverFile);
    if (false === $contents || '' === $contents) {
        throw new RuntimeException("Clover report is empty: {$cloverFile}");
    }

    if (class_exists('DOMDocument')) {
        $attributes = readProjectMetricsWithDom($contents);
    } else {
        $attributes = readProjectMetricsWithoutXmlExtension($contents);
    }

    $required = array('statements', 'coveredstatements', 'methods', 'coveredmethods');
    $metrics = array();

    foreach ($required as $name) {
        if (!array_key_exists($name, $attributes) || !preg_match('/^[0-9]+$/D', $attributes[$name])) {
            throw new RuntimeException("Clover project metrics have no valid '{$name}' value.");
        }

        $metrics[$name] = (int) $attributes[$name];
    }

    if (0 === $metrics['statements']) {
        throw new RuntimeException('Clover reports zero executable source lines; refusing a vacuous pass.');
    }
    if (0 === $metrics['methods']) {
        throw new RuntimeException('Clover reports zero source methods; refusing a vacuous pass.');
    }
    if ($metrics['coveredstatements'] > $metrics['statements']) {
        throw new RuntimeException('Clover covered source lines exceed total source lines.');
    }
    if ($metrics['coveredmethods'] > $metrics['methods']) {
        throw new RuntimeException('Clover covered methods exceed total methods.');
    }

    return $metrics;
}

/**
 * @return array<string, string>
 */
function readProjectMetricsWithDom(string $contents): array
{
    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument();
    $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$loaded) {
        throw new RuntimeException('Clover report is not valid XML.');
    }

    $projects = $document->getElementsByTagName('project');
    if (1 !== $projects->length) {
        throw new RuntimeException('Clover report must contain exactly one project element.');
    }

    $project = $projects->item(0);
    $projectMetrics = null;
    foreach ($project->childNodes as $node) {
        if ($node instanceof DOMElement && 'metrics' === $node->tagName) {
            if (null !== $projectMetrics) {
                throw new RuntimeException('Clover project contains more than one aggregate metrics element.');
            }
            $projectMetrics = $node;
        }
    }

    if (!$projectMetrics instanceof DOMElement) {
        throw new RuntimeException('Clover project aggregate metrics are missing.');
    }

    $attributes = array();
    foreach ($projectMetrics->attributes as $attribute) {
        $attributes[$attribute->name] = $attribute->value;
    }

    return $attributes;
}

/**
 * Parse PHPUnit's deterministic Clover shape when the optional DOM extension is
 * unavailable. This fallback still reads the project aggregate rather than a
 * human-formatted coverage line.
 *
 * @return array<string, string>
 */
function readProjectMetricsWithoutXmlExtension(string $contents): array
{
    if (1 !== preg_match('/<project\b[^>]*>(.*)<\/project>/sD', $contents, $projectMatch)) {
        throw new RuntimeException('Clover report must contain one complete project element.');
    }

    if (1 !== preg_match('/<metrics\b([^<>]*)\/>\s*$/sD', trim($projectMatch[1]), $metricsMatch)) {
        throw new RuntimeException('Clover project aggregate metrics are missing or malformed.');
    }

    preg_match_all(
        '/([A-Za-z][A-Za-z0-9_-]*)\s*=\s*(["\'])(.*?)\2/s',
        $metricsMatch[1],
        $attributeMatches,
        PREG_SET_ORDER
    );

    $attributes = array();
    foreach ($attributeMatches as $match) {
        $attributes[$match[1]] = html_entity_decode($match[3], ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    return $attributes;
}

/**
 * @param array<string, mixed> $summary
 */
function writeSummary(string $summaryFile, array $summary): void
{
    $directory = dirname($summaryFile);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException("Cannot create coverage evidence directory: {$directory}");
    }

    $json = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (false === $json) {
        throw new RuntimeException('Cannot encode coverage evidence as JSON.');
    }
    if (false === file_put_contents($summaryFile, $json . PHP_EOL)) {
        throw new RuntimeException("Cannot write coverage evidence: {$summaryFile}");
    }
}

$cloverFile = $argv[1] ?? dirname(__DIR__) . '/coverage/clover.xml';
$summaryFile = $argv[2] ?? dirname(__DIR__) . '/coverage/summary.json';

try {
    $metrics = readProjectMetrics($cloverFile);
    $linePercent = 100.0 * $metrics['coveredstatements'] / $metrics['statements'];
    $methodPercent = 100.0 * $metrics['coveredmethods'] / $metrics['methods'];
    $passed = $linePercent >= REQUIRED_LINE_PERCENT && $methodPercent >= REQUIRED_METHOD_PERCENT;

    $summary = array(
        'schema_version' => 1,
        'status' => $passed ? 'pass' : 'fail',
        'scope' => 'src/',
        'thresholds' => array(
            'lines_percent' => REQUIRED_LINE_PERCENT,
            'methods_percent' => REQUIRED_METHOD_PERCENT,
        ),
        'coverage' => array(
            'lines' => array(
                'covered' => $metrics['coveredstatements'],
                'total' => $metrics['statements'],
                'percent' => round($linePercent, 2),
            ),
            'methods' => array(
                'covered' => $metrics['coveredmethods'],
                'total' => $metrics['methods'],
                'percent' => round($methodPercent, 2),
            ),
        ),
        'not_measured' => array('branches', 'mutation_score'),
    );
    writeSummary($summaryFile, $summary);

    printf(
        "Source coverage: lines %d/%d (%.2f%%, required %.2f%%); methods %d/%d (%.2f%%, required %.2f%%).\n",
        $metrics['coveredstatements'],
        $metrics['statements'],
        $linePercent,
        REQUIRED_LINE_PERCENT,
        $metrics['coveredmethods'],
        $metrics['methods'],
        $methodPercent,
        REQUIRED_METHOD_PERCENT
    );
    printf("Coverage evidence: %s\n", $summaryFile);

    if (!$passed) {
        $uncoveredLines = $metrics['statements'] - $metrics['coveredstatements'];
        $uncoveredMethods = $metrics['methods'] - $metrics['coveredmethods'];
        fwrite(
            STDERR,
            "Coverage gate failed: add behavior tests for {$uncoveredLines} uncovered source line(s) "
            . "and {$uncoveredMethods} uncovered method(s), then regenerate Clover.\n"
        );
        exit(1);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Coverage gate error: ' . $exception->getMessage() . PHP_EOL);
    exit(2);
}
