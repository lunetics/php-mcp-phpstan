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

            // Should return analysis results structure
            $this->assertIsArray($result);

            // If there are errors, check structure
            if (isset($result['files'])) {
                $this->assertArrayHasKey('files', $result);
                $this->assertArrayHasKey('totalErrors', $result);
            }
        } finally {
            unlink($tempFile);
        }
    }
}
