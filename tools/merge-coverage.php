<?php

/**
 * Merges worker subprocess coverage data into the main PHPUnit coverage report.
 *
 * Usage: php tools/merge-coverage.php <coverage.php> <clover.xml>
 *
 * Worker processes (tests/Util/worker.php) save their pcov coverage to temp files
 * named brick-lock-worker-coverage-<pid>.bin in sys_get_temp_dir(). This script
 * loads the serialized CodeCoverage object produced by PHPUnit (--coverage-php),
 * appends each worker's raw coverage data, then writes the merged clover.xml.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Report\Clover;

$coveragePhpFile = $argv[1] ?? __DIR__ . '/../coverage.php';
$outputFile      = $argv[2] ?? __DIR__ . '/../clover.xml';

/** @var CodeCoverage $coverage */
$coverage = require $coveragePhpFile;

$workerFiles = glob(sys_get_temp_dir() . '/brick-lock-worker-coverage-*.bin');
assert($workerFiles !== false);

foreach ($workerFiles as $file) {
    $data = file_get_contents($file);
    assert($data !== false);

    $workerData = unserialize($data);

    if (! is_array($workerData) || $workerData === []) {
        continue;
    }

    $rawData = RawCodeCoverageData::fromXdebugWithoutPathCoverage($workerData);
    $coverage->append($rawData, basename($file, '.bin'));

    unlink($file);
}

(new Clover())->process($coverage, $outputFile);
