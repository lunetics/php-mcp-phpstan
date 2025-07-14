<?php

declare(strict_types=1);

namespace PhpMcp\PhpStan;

use PhpMcp\Server\Attributes\McpTool;

/**
 * MCP service providing PHPStan static analysis capabilities.
 */
class PhpStanMcpService
{
    private PhpStanRunner $phpStanRunner;
    private ResponseMapper $responseMapper;

    public function __construct()
    {
        $this->phpStanRunner = new PhpStanRunner();
        $this->responseMapper = new ResponseMapper();
    }

    /**
     * Run PHPStan static analysis on PHP code.
     *
     * @param string $path  File or directory path to analyze
     * @param string $level PHPStan analysis level (0-9 or max)
     *
     * @return array<string, mixed> Analysis results in MCP format
     */
    #[McpTool(name: 'phpstan_analyze', description: 'Run PHPStan static analysis on PHP code with configurable level')]
    public function analyzeCode(string $path, string $level = 'max'): array
    {
        try {
            // Validate path exists and is accessible
            if (!file_exists($path)) {
                throw new \InvalidArgumentException("Path does not exist: {$path}");
            }

            // Execute PHPStan analysis
            $phpStanOutput = $this->phpStanRunner->analyze($path);

            // Map PHPStan output to MCP response format
            return $this->responseMapper->mapToMcpResponse($phpStanOutput, $level);
        } catch (\Exception $e) {
            return $this->responseMapper->createErrorResponse('Analysis failed: '.$e->getMessage());
        }
    }
}
