# PHP-MCP-PHPStan

Model Context Protocol (MCP) server for PHPStan static analysis integration with LLM agents like Claude Code and Gemini CLI.

## Overview

This MCP server enables AI assistants to run PHPStan static analysis and receive structured, semantic JSON responses instead of raw text output. This allows AI to understand not just what fails, but why and in what context.

## Features

- **MCP Integration**: JSON-RPC 2.0 compliant server following MCP standards
- **PHPStan Integration**: Executes PHPStan with `prettyJson` format for maximum information
- **Structured Output**: Converts PHPStan results to semantic MCP response format
- **Error Handling**: Proper JSON-RPC error codes and meaningful error messages
- **Docker Support**: Development environment with official PHP 8.4 CLI container

## Installation

### Using Docker (Recommended)

1. Clone the repository:
```bash
git clone <repository-url>
cd php-mcp-phpstan
```

2. Start the development environment:
```bash
docker-compose up -d
```

3. Install dependencies:
```bash
docker-compose exec php composer install
```

### Local Installation

1. Ensure PHP 8.2+ is installed
2. Install dependencies:
```bash
composer install
```

## Usage

### As MCP Server

Start the MCP server:
```bash
./bin/server.php
```

### In IDE (e.g., VS Code)

Add to your `.vscode/mcp.json`:
```json
{
  "servers": {
    "phpstan": {
      "type": "stdio",
      "command": "/path/to/php-mcp-phpstan/bin/server.php"
    }
  }
}
```

### Docker Usage

```bash
# Run PHPStan analysis
docker-compose exec php vendor/bin/phpstan analyse src/

# Run tests
docker-compose exec php composer test

# Fix code style
docker-compose exec php composer cs-fix
```

## MCP Tool

The server provides one tool:

### `phpstan_analyze`

Runs PHPStan static analysis on the specified path.

**Parameters:**
- `path` (string, required): File or directory path to analyze

**Response Format:**
```json
{
  "files": [
    {
      "file": "src/Example.php",
      "errors": [
        {
          "line": 42,
          "message": "Property is never read, only written.",
          "identifier": "property.onlyWritten",
          "severity": "error"
        }
      ]
    }
  ],
  "totalErrors": 1
}
```

## Development

### Running Tests

```bash
# Local
composer test

# Docker
docker-compose exec php composer test
```

### Code Quality

```bash
# PHPStan analysis
composer phpstan

# Code style fixing
composer cs-fix

# Code style check
composer cs-check
```

## Technical Stack

- **PHP**: 8.2+ (supporting 8.2, 8.3, 8.4)
- **MCP Library**: php-mcp/server
- **Static Analysis**: PHPStan 2.x
- **Testing**: PHPUnit 11.x
- **Code Style**: PHP-CS-Fixer (Doctrine standards)
- **Development**: Docker + Docker Compose

## Architecture

```
php-mcp-phpstan/
├── src/
│   ├── McpServer.php      # Main MCP server class
│   ├── PhpStanRunner.php  # PHPStan execution
│   └── ResponseMapper.php # JSON response mapping
├── bin/
│   └── server.php         # MCP server entry point
├── tests/                 # PHPUnit tests
└── docker-compose.yml     # Development environment
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and code quality checks
5. Submit a pull request

## License

MIT License - see LICENSE file for details