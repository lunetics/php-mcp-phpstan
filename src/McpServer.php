<?php

declare(strict_types=1);

namespace PhpMcp\PhpStan;

use PhpMcp\Server\Server;
use PhpMcp\Server\Tool\Tool;
use PhpMcp\Server\Tool\ToolCall;
use PhpMcp\Server\Tool\ToolResult;

/**
 * MCP server for PHPStan static analysis integration
 */
class McpServer
{
    private Server $server;
    private PhpStanRunner $phpStanRunner;
    private ResponseMapper $responseMapper;

    public function __construct()
    {
        $this->server = new Server();
        $this->phpStanRunner = new PhpStanRunner();
        $this->responseMapper = new ResponseMapper();
        
        $this->registerTools();
    }

    private function registerTools(): void
    {
        $this->server->addTool(new Tool(
            name: 'phpstan_analyze',
            description: 'Run PHPStan static analysis on PHP code',
            inputSchema: [
                'type' => 'object',
                'properties' => [
                    'path' => [
                        'type' => 'string',
                        'description' => 'File or directory path to analyze'
                    ]
                ],
                'required' => ['path']
            ],
            handler: $this->handlePhpStanAnalyze(...)
        ));
    }

    private function handlePhpStanAnalyze(ToolCall $call): ToolResult
    {
        try {
            $path = $call->getArgument('path');
            
            if (!is_string($path) || empty($path)) {
                return ToolResult::error('Invalid path parameter');
            }

            // Validate path exists and is accessible
            if (!file_exists($path)) {
                return ToolResult::error("Path does not exist: {$path}");
            }

            // Execute PHPStan analysis
            $phpStanOutput = $this->phpStanRunner->analyze($path);
            
            // Map PHPStan output to MCP response format
            $mcpResponse = $this->responseMapper->mapToMcpResponse($phpStanOutput);
            
            return ToolResult::success($mcpResponse);
            
        } catch (\Exception $e) {
            return ToolResult::error("Analysis failed: " . $e->getMessage());
        }
    }

    public function run(): void
    {
        $this->server->run();
    }

    public function getServer(): Server
    {
        return $this->server;
    }
}