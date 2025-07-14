<?php

declare(strict_types=1);

namespace PhpMcp\PhpStan\Tests;

use PhpMcp\PhpStan\ResponseMapper;
use PHPUnit\Framework\TestCase;

class ResponseMapperTest extends TestCase
{
    private ResponseMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ResponseMapper();
    }

    public function testMapToMcpResponseWithNoErrors(): void
    {
        $phpStanOutput = [
            'totals' => ['errors' => 0, 'file_errors' => 0],
            'files' => [],
            'errors' => [],
        ];

        $result = $this->mapper->mapToMcpResponse($phpStanOutput, 'max');

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('files', $result);

        // Test MCP content structure
        $this->assertIsArray($result['content']);
        $this->assertCount(1, $result['content']);
        $this->assertEquals('text', $result['content'][0]['type']);
        $this->assertStringContainsString('No errors found', $result['content'][0]['text']);
        $this->assertStringContainsString('✅', $result['content'][0]['text']);

        // Test summary structure
        $this->assertEquals([
            'total_errors' => 0,
            'total_warnings' => 0,
            'files_analyzed' => 0,
            'files_with_errors' => 0,
            'level' => 'max',
            'success_rate' => '100%',
            'status' => 'passed',
        ], $result['summary']);

        // Test empty errors and files
        $this->assertEmpty($result['errors']);
        $this->assertEmpty($result['files']);
    }

    public function testMapToMcpResponseWithErrors(): void
    {
        $phpStanOutput = [
            'totals' => ['errors' => 2, 'file_errors' => 1],
            'files' => [
                'src/Example.php' => [
                    'errors' => 2,
                    'messages' => [
                        [
                            'message' => 'Property is never read, only written.',
                            'line' => 42,
                            'identifier' => 'property.onlyWritten',
                            'ignorable' => true,
                        ],
                        [
                            'message' => 'Variable $test might not be defined.',
                            'line' => 15,
                            'identifier' => 'variable.undefined',
                            'ignorable' => false,
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->mapper->mapToMcpResponse($phpStanOutput, '9');

        // Test MCP content structure
        $this->assertArrayHasKey('content', $result);
        $this->assertStringContainsString('2 errors found', $result['content'][0]['text']);
        $this->assertStringContainsString('❌', $result['content'][0]['text']);

        // Test summary structure
        $this->assertEquals([
            'total_errors' => 2,
            'total_warnings' => 0,
            'files_analyzed' => 1,
            'files_with_errors' => 1,
            'level' => '9',
            'success_rate' => '0%',
            'status' => 'failed',
        ], $result['summary']);

        // Test errors array structure
        $this->assertCount(2, $result['errors']);
        $this->assertEquals([
            'file' => 'src/Example.php',
            'line' => 42,
            'message' => 'Property is never read, only written.',
            'rule' => 'property.onlyWritten',
            'severity' => 'error',
            'location' => 'src/Example.php:42',
            'ignorable' => true,
        ], $result['errors'][0]);

        // Test files breakdown structure
        $this->assertArrayHasKey('src/Example.php', $result['files']);
        $this->assertEquals(2, $result['files']['src/Example.php']['error_count']);
        $this->assertCount(2, $result['files']['src/Example.php']['messages']);
    }

    public function testCreateErrorResponse(): void
    {
        $result = $this->mapper->createErrorResponse('Test error message', -32602);

        $expected = [
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'PHPStan Analysis Failed: Test error message ❌',
                ],
            ],
            'error' => [
                'code' => -32602,
                'message' => 'Test error message',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testSeverityMapping(): void
    {
        $phpStanOutput = [
            'totals' => ['errors' => 3, 'file_errors' => 1],
            'files' => [
                'src/Test.php' => [
                    'errors' => 3,
                    'messages' => [
                        [
                            'message' => 'Method is deprecated',
                            'line' => 10,
                            'identifier' => 'deprecated.method',
                        ],
                        [
                            'message' => 'Unused variable',
                            'line' => 20,
                            'identifier' => 'unused.variable',
                        ],
                        [
                            'message' => 'Missing type hint',
                            'line' => 30,
                            'identifier' => 'missingType.parameter',
                            'ignorable' => true,
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->mapper->mapToMcpResponse($phpStanOutput);

        // Test severity mappings
        $this->assertEquals('warning', $result['errors'][0]['severity']); // deprecated
        $this->assertEquals('info', $result['errors'][1]['severity']); // unused
        $this->assertEquals('warning', $result['errors'][2]['severity']); // missingType + ignorable
    }

    public function testFilePathCleaning(): void
    {
        $phpStanOutput = [
            'totals' => ['errors' => 1, 'file_errors' => 1],
            'files' => [
                '/app/src/Example.php' => [
                    'errors' => 1,
                    'messages' => [
                        [
                            'message' => 'Test error',
                            'line' => 10,
                            'identifier' => 'test.error',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->mapper->mapToMcpResponse($phpStanOutput);

        // Test that /app/ prefix is removed
        $this->assertEquals('src/Example.php', $result['errors'][0]['file']);
        $this->assertArrayHasKey('src/Example.php', $result['files']);
        $this->assertEquals('src/Example.php:10', $result['errors'][0]['location']);
    }
}
