# Agent Instructions

This document provides instructions for AI coding agents working on this project.

---

## Critical Rules

### Before Considering Any Task Complete

Always run the check suite before marking a task as done:

```bash
composer check
```

This command runs all quality checks in sequence:

1. **Code formatting** (`composer format:check`) - Verifies PER-CS compliance
2. **Static analysis** (`composer analyze`) - Runs PHPStan analysis
3. **Tests** (`composer test`) - Runs the PHPUnit test suite with Paratest

**All checks must pass for the code to be considered complete.**

### Version Control

Never try to commit changes yourself. The user is in charge of what gets saved and discarded.

### Iterative Development

Never implement something as just a placeholder or a method definition containing just `// TODO`, unless specifically asked to take an iterative approach.

### Documentation

- If you added or changed environment variables, document them
- If you make significant architectural changes, update `README.md` to reflect this

---

## Tech Stack

This is a PHP library with CLI functionality. Key packages and versions:

| Package | Version |
|---------|---------|
| PHP | ^8.2 |
| nikic/php-parser | ^5.0 |
| phpdocumentor/reflection-docblock | ^5.4 |
| symfony/console | ^6.4 \|\| ^7.0 |
| PHPUnit | ^12.0 |
| Paratest | ^7.7 |
| PHPStan | ^2.0 |
| PHP-CS-Fixer | ^3.64 |

---

## Code Quality

### Coding Standards

- All code must follow **PER-CS** (PER Coding Style) coding standard
- All PHP files must include `declare(strict_types=1);` at the top
- Use modern PHP 8.2+ standards with strict typing
- Prefer immutable value objects where appropriate
- Use enums instead of string constants where applicable

### Project Structure

```
phpdoc-validator/
├── bin/
│   └── phpdoc-validator      # CLI entry point
├── src/
│   ├── Cache/                # Result caching (CacheMode, CacheSignature, ValidationCache)
│   ├── Command/              # Symfony Console commands
│   ├── Formatter/            # Output formatters
│   ├── Parser/               # AST parsing components
│   ├── FileReport.php        # Single file report
│   ├── Issue.php             # Validation issue
│   ├── MethodInfo.php        # Method metadata
│   ├── PhpDocValidator.php   # Main orchestrator
│   ├── Report.php            # Aggregate report
│   └── TypeComparator.php    # Type comparison logic
└── tests/
    ├── Unit/                 # Unit tests
    ├── Feature/              # Integration/CLI tests
    └── fixtures/             # Test fixture PHP files
```

### Testing Standards

- Code should be adequately covered by tests
- Use PHPUnit's own mocking functionality, **never use Mockery**
- Use **camelCase** naming for test methods, NOT snake_case
- Use modern PHPUnit attributes (`#[Test]`, `#[DataProvider]`), not the `test*` prefix
- Tests go in `tests/Unit/` for unit tests and `tests/Feature/` for integration tests
- Test fixtures (sample PHP files) go in `tests/fixtures/`

### Available Commands

| Command | Description |
|---------|-------------|
| `composer check` | Run all checks (formatting, static analysis, tests) |
| `composer format` | Auto-fix PHP code formatting issues |
| `composer format:check` | Check PHP formatting without making changes |
| `composer analyze` | Run PHPStan static analysis |
| `composer test` | Run PHPUnit tests with Paratest |
| `composer test:coverage` | Run tests with HTML coverage report |

### Fixing Check Failures

| Check | Fix |
|-------|-----|
| PHP formatting errors | Run `composer format` to auto-fix |
| Static analysis errors | Review PHPStan output and fix type issues manually |
| Test failures | Debug and fix the failing tests |

---

## Development Guidelines

### Adding New Features

1. Write tests first (TDD approach recommended)
2. Implement the feature in `src/`
3. Run `composer check` to verify everything passes
4. Update documentation if needed

### File Naming Conventions

- Classes: PascalCase (e.g., `TypeComparator.php`)
- Interfaces: PascalCase with `Interface` suffix (e.g., `FormatterInterface.php`)
- Test files: Mirror source structure with `Test` suffix (e.g., `TypeComparatorTest.php`)

### Type Hints

- Always use parameter type hints
- Always use return type hints
- Use union types where appropriate (`string|int`)
- Use `?Type` for nullable parameters, `Type|null` for nullable returns
- If the native type hinting and parameter name is adequate for documentation purposes, there is no need to also define it using `@param` 
