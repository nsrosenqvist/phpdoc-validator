# PHPDoc Validator

Validates PHPDoc `@param` and `@return` tags against method signatures using AST parsing.

## Features

- **Type compatibility checking** — Understands PHPDoc-specific types like `list<T>`, `class-string<T>`, `positive-int`
- **Result caching** — Dramatically speeds up incremental runs.
- **Multiple output formats** — Pretty CLI output, JSON for tooling, GitHub Actions annotations
- **CI-friendly** — Exit codes for easy integration into build pipelines
- **Flexible scanning** — Scan directories or individual files with glob-based exclusions

## Installation

```bash
composer require --dev nsrosenqvist/phpdoc-validator
```

## Usage

### Basic Usage

```bash
# Scan the src/ directory
vendor/bin/phpdoc-validator src/

# Scan multiple paths
vendor/bin/phpdoc-validator src/ lib/ app/

# Scan with exclusions
vendor/bin/phpdoc-validator src/ --exclude="*Test.php" --exclude="*/fixtures/*"
```

### Output Formats

```bash
# Pretty output (default)
vendor/bin/phpdoc-validator src/ --format=pretty

# JSON for tooling
vendor/bin/phpdoc-validator src/ --format=json

# GitHub Actions annotations
vendor/bin/phpdoc-validator src/ --format=github
```

### Options

| Option | Short | Description |
|--------|-------|-------------|
| `--format` | `-f` | Output format: `pretty`, `json`, `github` |
| `--no-color` | | Disable colored output |
| `--exclude` | `-e` | Patterns to exclude (can be used multiple times) |
| `--missing` | `-m` | Also report missing `@param` and `@return` documentation |
| `--fix` | | Automatically fix issues (see [Auto-fixing](#auto-fixing)) |
| `--no-cache` | | Disable result caching |
| `--clear-cache` | | Clear the cache before running validation |
| `--cache-file` | | Path to the cache file (default: `.phpdoc-validator.cache`) |
| `--cache-mode` | | Cache invalidation mode: `hash` (default) or `mtime` |

### Exit Codes

| Code | Meaning |
|------|---------|
| 0 | No issues found |
| 1 | Issues were found |
| 2 | An error occurred |

## What It Checks

By default, PHPDoc Validator reports:

1. **Extra @param tags** — Documentation for parameters that don't exist in the method signature
2. **Type mismatches** — Documented types that don't match the signature types (both `@param` and `@return`)
3. **Return type mismatches** — `@return` types that don't match the method's return type signature

With `--missing`, it also reports:

4. **Missing @param tags** — Parameters in the signature that lack documentation
5. **Missing @return tags** — Methods with return types that lack `@return` documentation

Additionally, it always checks for:

6. **Parameter order** — `@param` tags that don't match the order of parameters in the signature

## Auto-fixing

PHPDoc Validator can automatically fix certain issues:

```bash
# Fix param order issues
vendor/bin/phpdoc-validator src/ --fix

# Also add missing @param and @return tags
vendor/bin/phpdoc-validator src/ --fix --missing
```

### What Can Be Fixed

| Issue | `--fix` | `--fix --missing` |
|-------|---------|-------------------|
| Parameter order | ✓ | ✓ |
| Missing `@param` | | ✓ |
| Missing `@return` | | ✓ |
| Type mismatches | | |
| Extra params | | |

Type mismatches and extra params cannot be auto-fixed because the PHPDoc often contains more specific type information than the native PHP signature.

## Type Compatibility

The validator understands that certain PHPDoc types are compatible with PHP native types:

| PHP Type | Compatible PHPDoc Types |
|----------|------------------------|
| `array` | `list<T>`, `non-empty-array<T>`, `array<K, V>` |
| `string` | `class-string<T>`, `numeric-string`, `callable-string` |
| `int` | `positive-int`, `negative-int`, `non-negative-int` |
| `callable` | `Closure`, `callable-string` |
| `iterable` | `iterable<T>` |

## Example Output

### Pretty Format

```
PHPDoc Parameter Validation Report
============================================

src/UserService.php:42
   Method: UserService::createUser()
   [X]  Extra @param $role not in method signature
   [!]  Type mismatch for $email: signature has 'string', doc has 'int'

Summary:
  Files scanned: 15
  Files with issues: 1
  Total issues: 2
```

### GitHub Actions Format

```
::error file=src/UserService.php,line=42,title=Extra @param::Extra @param $role not in method signature
::error file=src/UserService.php,line=42,title=Type mismatch::Type mismatch for $email: signature has 'string', doc has 'int'
```

## Caching

PHPDoc Validator caches validation results to dramatically speed up incremental runs. On subsequent runs, only files that have changed since the last validation are re-parsed.

```bash
# First run: validates all files and creates cache
vendor/bin/phpdoc-validator src/   # ~30s for large codebase

# Second run: uses cache, only validates changed files
vendor/bin/phpdoc-validator src/   # ~0.3s (100x faster)

# Disable caching
vendor/bin/phpdoc-validator src/ --no-cache

# Clear cache before running
vendor/bin/phpdoc-validator src/ --clear-cache
```

### Cache Modes

| Mode | Description |
|------|-------------|
| `hash` | Uses SHA-256 content hash (default, most reliable) |
| `mtime` | Uses file modification time (faster but can miss changes after git operations) |

The cache is stored in `.phpdoc-validator.cache` by default. Add this file to your `.gitignore`.

The cache automatically invalidates when:
- The validator version changes
- The PHP version changes
- The `--missing` flag setting changes

## CI Integration

### GitHub Actions

```yaml
- name: Validate PHPDoc
  run: vendor/bin/phpdoc-validator src/ --format=github
```

### GitLab CI

```yaml
phpdoc:
  script:
    - vendor/bin/phpdoc-validator src/
```

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyze

# Fix code style
composer format

# Run all checks
composer check
```

## License

MIT License. See [LICENSE](LICENSE) for details.
