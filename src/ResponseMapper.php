<?php

declare(strict_types=1);

namespace PhpMcp\PhpStan;

/**
 * Maps PHPStan JSON output to MCP response format.
 */
class ResponseMapper
{
    /**
     * Convert PHPStan JSON output to MCP response format with dual structure.
     *
     * @param array<string, mixed> $phpStanOutput
     *
     * @return array<string, mixed>
     */
    public function mapToMcpResponse(array $phpStanOutput, string $level = 'max'): array
    {
        $summary = $this->generateSummary($phpStanOutput, $level);
        $errors = $this->extractErrors($phpStanOutput);
        $fileBreakdown = $this->generateFileBreakdown($phpStanOutput);

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $this->generateHumanReadableSummary($summary),
                ],
            ],
            'summary' => $summary,
            'errors' => $errors,
            'files' => $fileBreakdown,
        ];
    }

    /**
     * Map a single PHPStan error message to enhanced MCP error format.
     *
     * @param array<mixed, mixed> $message
     *
     * @return array<string, mixed>
     */
    private function mapError(array $message, string $filePath): array
    {
        $lineValue = $message['line'] ?? 0;
        $line = is_int($lineValue) ? $lineValue : (is_numeric($lineValue) ? (int) $lineValue : 0);
        $cleanFilePath = $this->cleanFilePath($filePath);

        return [
            'file' => $cleanFilePath,
            'line' => $line,
            'message' => $message['message'] ?? 'Unknown error',
            'rule' => $message['identifier'] ?? 'unknown',
            'severity' => $this->mapSeverity($message),
            'location' => $cleanFilePath.':'.(string) $line,
            'ignorable' => (bool) ($message['ignorable'] ?? false),
        ];
    }

    /**
     * Map PHPStan severity/level to standardized severity based on identifier patterns.
     *
     * @param array<mixed, mixed> $message
     */
    private function mapSeverity(array $message): string
    {
        $identifierValue = $message['identifier'] ?? '';
        $identifier = is_string($identifierValue) ? $identifierValue : '';

        // Map based on PHPStan error patterns
        if (str_contains($identifier, 'deprecated')) {
            return 'warning';
        }

        if (str_contains($identifier, 'unused') || str_contains($identifier, 'dead')) {
            return 'info';
        }

        if (str_contains($identifier, 'missingType') && (bool) ($message['ignorable'] ?? false)) {
            return 'warning';
        }

        // Default to error for strict type checking and other critical issues
        return 'error';
    }

    /**
     * Generate structured summary statistics.
     *
     * @param array<string, mixed> $phpStanOutput
     *
     * @return array<string, mixed>
     */
    private function generateSummary(array $phpStanOutput, string $level): array
    {
        $totals = $phpStanOutput['totals'] ?? [];
        $totalErrors = is_array($totals) ? ($totals['errors'] ?? 0) : 0;
        $fileErrors = is_array($totals) ? ($totals['file_errors'] ?? 0) : 0;
        $files = $phpStanOutput['files'] ?? [];
        $filesAnalyzed = is_array($files) ? count($files) : (is_object($files) ? count((array) $files) : 0);

        $successRate = 0 === $totalErrors ? '100%' : '0%';
        $status = 0 === $totalErrors ? 'passed' : 'failed';

        return [
            'total_errors' => $totalErrors,
            'total_warnings' => 0, // PHPStan doesn't separate warnings in totals
            'files_analyzed' => $filesAnalyzed,
            'files_with_errors' => $fileErrors,
            'level' => $level,
            'success_rate' => $successRate,
            'status' => $status,
        ];
    }

    /**
     * Generate human-readable summary with status indicators.
     *
     * @param array<string, mixed> $summary
     */
    private function generateHumanReadableSummary(array $summary): string
    {
        $errorsValue = $summary['total_errors'] ?? 0;
        $errors = is_int($errorsValue) ? $errorsValue : 0;

        $filesValue = $summary['files_analyzed'] ?? 0;
        $files = is_int($filesValue) ? $filesValue : 0;

        $levelValue = $summary['level'] ?? 'max';
        $level = is_string($levelValue) ? $levelValue : 'max';

        $statusValue = $summary['status'] ?? 'unknown';
        $status = is_string($statusValue) ? $statusValue : 'unknown';

        $statusIcon = 'passed' === $status ? '✅' : '❌';
        $statusText = 'passed' === $status ? 'Analysis passed' : 'Analysis failed';

        if (0 === $errors) {
            return "PHPStan Analysis: No errors found in {$files} file(s) (Level {$level}) {$statusIcon} {$statusText}";
        }

        $errorText = 1 === $errors ? 'error' : 'errors';
        $fileText = 1 === $files ? 'file' : 'files';

        return "PHPStan Analysis: {$errors} {$errorText} found in {$files} {$fileText} (Level {$level}) {$statusIcon} {$statusText}";
    }

    /**
     * Extract and format all errors from PHPStan output.
     *
     * @param array<string, mixed> $phpStanOutput
     *
     * @return array<array<string, mixed>>
     */
    private function extractErrors(array $phpStanOutput): array
    {
        $errors = [];

        $files = $phpStanOutput['files'] ?? [];
        if (empty($files)) {
            return $errors;
        }

        // Convert stdClass to array if needed
        if (is_object($files)) {
            $files = (array) $files;
        }

        if (!is_array($files)) {
            return $errors;
        }

        foreach ($files as $filePath => $fileData) {
            // Convert stdClass to array if needed
            if (is_object($fileData)) {
                $fileData = (array) $fileData;
            }

            if (is_array($fileData) && isset($fileData['messages']) && is_array($fileData['messages'])) {
                foreach ($fileData['messages'] as $message) {
                    // Convert stdClass to array if needed
                    if (is_object($message)) {
                        $message = (array) $message;
                    }
                    if (is_array($message)) {
                        /* @var array<string, mixed> $message */
                        $errors[] = $this->mapError($message, (string) $filePath);
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Generate per-file breakdown with error counts.
     *
     * @param array<string, mixed> $phpStanOutput
     *
     * @return array<string, mixed>
     */
    private function generateFileBreakdown(array $phpStanOutput): array
    {
        $result = [];

        $files = $phpStanOutput['files'] ?? [];
        if (empty($files)) {
            return $result;
        }

        // Convert stdClass to array if needed
        if (is_object($files)) {
            $files = (array) $files;
        }

        if (!is_array($files)) {
            return $result;
        }

        foreach ($files as $filePath => $fileData) {
            // Convert stdClass to array if needed
            if (is_object($fileData)) {
                $fileData = (array) $fileData;
            }

            $cleanPath = $this->cleanFilePath((string) $filePath);
            $errorCount = 0;
            $messages = [];

            if (is_array($fileData) && isset($fileData['messages']) && is_array($fileData['messages'])) {
                $errorCount = count($fileData['messages']);
                foreach ($fileData['messages'] as $message) {
                    // Convert stdClass to array if needed
                    if (is_object($message)) {
                        $message = (array) $message;
                    }
                    if (is_array($message)) {
                        /* @var array<string, mixed> $message */
                        $messages[] = $this->mapError($message, (string) $filePath);
                    }
                }
            }

            $result[$cleanPath] = [
                'error_count' => $errorCount,
                'messages' => $messages,
            ];
        }

        return $result;
    }

    /**
     * Clean file path by removing absolute path prefix.
     */
    private function cleanFilePath(string $filePath): string
    {
        // Remove common prefixes like /app/ from Docker containers
        $cleanPath = str_replace('/app/', '', $filePath);

        // Remove leading slash if present
        return ltrim($cleanPath, '/');
    }

    /**
     * Create an error response for MCP.
     *
     * @return array<string, mixed>
     */
    public function createErrorResponse(string $errorMessage, int $errorCode = -32603): array
    {
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => "PHPStan Analysis Failed: {$errorMessage} ❌",
                ],
            ],
            'error' => [
                'code' => $errorCode,
                'message' => $errorMessage,
            ],
        ];
    }
}
