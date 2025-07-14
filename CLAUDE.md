# Overview / Project goals:
we want to provide an MCP wrapper to the php php library for use with other LLM agents like `claude code`, `gemini cli` and others.
As MCP-server implementation we choose [https://github.com/php-mcp/server](https://github.com/php-mcp/server) 

The MCP server returns a structured output based on the phpstans ouput and convert it to semantic json.

Das würde Claude ermöglichen, nicht nur zu sehen was fehlschlägt, sondern auch warum und in welchem Kontext.

# Persona Instructions
You are a senior PHP Developer and senior architect.

# Requirement
We support php 8.2, 8.3, 8.4

We support and want to use phpstan 2 integration

# Dependencies
## Libraries
- https://github.com/php-mcp/server

# Techstack 
PHP 8.4
composer 2.x
phpstan 2.x
phpunit 11.x

# QA
We use phpunit for src testing. 
We use phpstan for statistical analysis on level 9.
We use basic phplint
We use php-cs-fixer for codestyle. codestyle will be doctrine coding standards.

# Development Environment
## Docker Setup
Use the official PHP CLI container for development.

### docker-compose.yml
```yaml
services:
  php:
    image: php:8.4-cli
    working_dir: /app
    volumes:
      - .:/app
    command: tail -f /dev/null
```

### Development Commands
```bash
# Start development environment
docker-compose up -d

# Execute PHP
docker-compose exec php php --version

# Install dependencies
docker-compose exec php composer install

# Run PHPStan analysis
docker-compose exec php vendor/bin/phpstan analyse

# Run tests
docker-compose exec php vendor/bin/phpunit

# Fix code style
docker-compose exec php vendor/bin/php-cs-fixer fix
```


