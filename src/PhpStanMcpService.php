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
     * @param string $path File or directory path to analyze
     *
     * @return array<string, mixed> Analysis results in MCP format
     */
    #[McpTool(name: 'phpstan_analyze', description: 'Run PHPStan static analysis on PHP code')]
    public function analyzeCode(string $path): array
    {
        try {
            // Validate path exists and is accessible
            if (!file_exists($path)) {
                throw new \InvalidArgumentException("Path does not exist: {$path}");
            }

            // Execute PHPStan analysis
            $phpStanOutput = $this->phpStanRunner->analyze($path);

            // Map PHPStan output to MCP response format
            return $this->responseMapper->mapToMcpResponse($phpStanOutput);
        } catch (\Exception $e) {
            return $this->responseMapper->createErrorResponse('Analysis failed: '.$e->getMessage());
        }
    }
}
