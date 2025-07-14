<?php

declare(strict_types=1);

namespace PhpMcp\PhpStan;

/**
 * Maps PHPStan JSON output to MCP response format.
 */
class ResponseMapper
{
    /**
     * Convert PHPStan JSON output to MCP response format.
     *
     * @param array<string, mixed> $phpStanOutput
     *
     * @return array<string, mixed>
     */
    public function mapToMcpResponse(array $phpStanOutput): array
    {
        $files = [];
        $totalErrors = 0;

        // Handle case where no errors were found
        if (empty($phpStanOutput['files']) || !is_array($phpStanOutput['files'])) {
            return [
                'files' => [],
                'totalErrors' => 0,
            ];
        }

        foreach ($phpStanOutput['files'] as $filePath => $fileData) {
            $errors = [];

            if (is_array($fileData) && isset($fileData['messages']) && is_array($fileData['messages'])) {
                /** @var array<string, mixed> $message */
                foreach ($fileData['messages'] as $message) {
                    $errors[] = $this->mapError($message);
                    ++$totalErrors;
                }
            }

            if (!empty($errors)) {
                $files[] = [
                    'file' => $filePath,
                    'errors' => $errors,
                ];
            }
        }

        return [
            'files' => $files,
            'totalErrors' => $totalErrors,
        ];
    }

    /**
     * Map a single PHPStan error message to MCP error format.
     *
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    private function mapError(array $message): array
    {
        return [
            'line' => $message['line'] ?? 0,
            'message' => $message['message'] ?? 'Unknown error',
            'identifier' => $message['identifier'] ?? 'unknown',
            'severity' => $this->mapSeverity($message),
        ];
    }

    /**
     * Map PHPStan severity/level to standardized severity.
     *
     * @param array<string, mixed> $message
     */
    private function mapSeverity(array $message): string
    {
        // PHPStan primarily reports errors, but we can map based on identifier patterns
        // or add logic here for different severity levels if needed

        // For MVP, we'll treat everything as 'error' since PHPStan focuses on errors
        // Future enhancement could map based on identifier patterns
        return 'error';
    }

    /**
     * Create an error response for MCP.
     *
     * @return array<string, mixed>
     */
    public function createErrorResponse(string $errorMessage, int $errorCode = -32603): array
    {
        return [
            'error' => [
                'code' => $errorCode,
                'message' => $errorMessage,
            ],
        ];
    }
}
