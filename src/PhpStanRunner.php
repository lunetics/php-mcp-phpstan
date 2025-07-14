<?php

declare(strict_types=1);

namespace PhpMcp\PhpStan;

use Symfony\Component\Process\Process;

/**
 * Handles PHPStan execution and JSON output parsing.
 */
class PhpStanRunner
{
    private string $phpStanBinary;
    private int $timeoutSeconds;

    public function __construct(string $phpStanBinary = 'vendor/bin/phpstan', int $timeoutSeconds = 300)
    {
        $this->phpStanBinary = $phpStanBinary;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    /**
     * Execute PHPStan analysis on the given path.
     *
     * @return array<string, mixed>
     */
    public function analyze(string $path): array
    {
        $command = [
            $this->phpStanBinary,
            'analyse',
            '--error-format=prettyJson',
            '--level=max',
            '--no-progress',
            $path,
        ];

        $process = new Process($command);
        $process->setTimeout($this->timeoutSeconds);

        try {
            $process->run();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to execute PHPStan: '.$e->getMessage());
        }

        // PHPStan returns exit code > 0 when errors are found, which is expected
        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();

        // Check for actual execution errors (not analysis errors)
        if ($process->getExitCode() > 2) {
            throw new \RuntimeException('PHPStan execution failed: '.$errorOutput);
        }

        if (empty($output)) {
            // No errors found, return empty result
            return [
                'totals' => [
                    'errors' => 0,
                    'file_errors' => 0,
                ],
                'files' => [],
                'errors' => [],
            ];
        }

        return $this->parsePhpStanOutput($output);
    }

    /**
     * Parse PHPStan JSON output.
     *
     * @return array<string, mixed>
     */
    private function parsePhpStanOutput(string $output): array
    {
        $decoded = json_decode($output, false, 512, JSON_THROW_ON_ERROR);

        if (!$decoded instanceof \stdClass) {
            throw new \RuntimeException('Invalid PHPStan output format');
        }

        return (array) $decoded;
    }

    /**
     * Check if PHPStan binary is available.
     */
    public function isAvailable(): bool
    {
        $process = new Process([$this->phpStanBinary, '--version']);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Get PHPStan version.
     */
    public function getVersion(): ?string
    {
        $process = new Process([$this->phpStanBinary, '--version']);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());
        if (preg_match('/PHPStan - PHP Static Analysis Tool (\d+\.\d+\.\d+)/', $output, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
