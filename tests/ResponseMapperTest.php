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

        $result = $this->mapper->mapToMcpResponse($phpStanOutput);

        $this->assertEquals([
            'files' => [],
            'totalErrors' => 0,
        ], $result);
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
                        ],
                        [
                            'message' => 'Variable $test might not be defined.',
                            'line' => 15,
                            'identifier' => 'variable.undefined',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->mapper->mapToMcpResponse($phpStanOutput);

        $expected = [
            'files' => [
                [
                    'file' => 'src/Example.php',
                    'errors' => [
                        [
                            'line' => 42,
                            'message' => 'Property is never read, only written.',
                            'identifier' => 'property.onlyWritten',
                            'severity' => 'error',
                        ],
                        [
                            'line' => 15,
                            'message' => 'Variable $test might not be defined.',
                            'identifier' => 'variable.undefined',
                            'severity' => 'error',
                        ],
                    ],
                ],
            ],
            'totalErrors' => 2,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testCreateErrorResponse(): void
    {
        $result = $this->mapper->createErrorResponse('Test error message', -32602);

        $expected = [
            'error' => [
                'code' => -32602,
                'message' => 'Test error message',
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}
