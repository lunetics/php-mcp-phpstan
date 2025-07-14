<?php

declare(strict_types=1);

namespace PhpMcp\PhpStan\Tests;

use PhpMcp\PhpStan\PhpStanMcpService;
use PHPUnit\Framework\TestCase;

class PhpStanMcpServiceTest extends TestCase
{
    private PhpStanMcpService $service;

    protected function setUp(): void
    {
        $this->service = new PhpStanMcpService();
    }

    public function testAnalyzeCodeWithNonExistentPath(): void
    {
        $result = $this->service->analyzeCode('/non/existent/path');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Path does not exist', $result['error']['message']);
    }

    public function testAnalyzeCodeWithValidPath(): void
    {
        // Create a temporary PHP file with an error
        $tempFile = tempnam(sys_get_temp_dir(), 'phpstan_test_').'.php';
        file_put_contents($tempFile, '<?php
class Test {
    private $unused;
    public function test() {
        $undefined->method();
    }
}');

        try {
            $result = $this->service->analyzeCode($tempFile);

            // Should return MCP dual structure format
            $this->assertIsArray($result);
            $this->assertArrayHasKey('content', $result);
            $this->assertArrayHasKey('summary', $result);
            $this->assertArrayHasKey('errors', $result);
            $this->assertArrayHasKey('files', $result);

            // Test MCP content structure
            $this->assertIsArray($result['content']);
            $this->assertCount(1, $result['content']);
            $this->assertEquals('text', $result['content'][0]['type']);
            $this->assertIsString($result['content'][0]['text']);

            // Test summary structure
            $this->assertArrayHasKey('total_errors', $result['summary']);
            $this->assertArrayHasKey('status', $result['summary']);
            $this->assertArrayHasKey('level', $result['summary']);
        } finally {
            unlink($tempFile);
        }
    }
}
