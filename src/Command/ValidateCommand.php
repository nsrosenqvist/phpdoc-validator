<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Command;

use NsRosenqvist\PhpDocValidator\Cache\CacheMode;
use NsRosenqvist\PhpDocValidator\Cache\CacheSignature;
use NsRosenqvist\PhpDocValidator\Cache\ValidationCache;
use NsRosenqvist\PhpDocValidator\Formatter\FormatterInterface;
use NsRosenqvist\PhpDocValidator\Formatter\GithubActionsFormatter;
use NsRosenqvist\PhpDocValidator\Formatter\JsonFormatter;
use NsRosenqvist\PhpDocValidator\Formatter\PrettyFormatter;
use NsRosenqvist\PhpDocValidator\PhpDocValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for validating PHPDoc @param tags.
 */
#[AsCommand(
    name: 'validate',
    description: 'Validate PHPDoc @param tags against method signatures'
)]
final class ValidateCommand extends Command
{
    public const EXIT_SUCCESS = 0;

    public const EXIT_ISSUES_FOUND = 1;

    public const EXIT_ERROR = 2;

    protected function configure(): void
    {
        $this
            ->addArgument(
                'paths',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Directories or files to scan (defaults to current directory)'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format: pretty, json, github',
                'pretty'
            )
            ->addOption(
                'no-color',
                null,
                InputOption::VALUE_NONE,
                'Disable colored output'
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Patterns to exclude from scanning'
            )
            ->addOption(
                'missing',
                'm',
                InputOption::VALUE_NONE,
                'Also report parameters that are missing @param documentation'
            )
            ->addOption(
                'no-cache',
                null,
                InputOption::VALUE_NONE,
                'Disable result caching'
            )
            ->addOption(
                'clear-cache',
                null,
                InputOption::VALUE_NONE,
                'Clear the cache before running validation'
            )
            ->addOption(
                'cache-file',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the cache file',
                ValidationCache::DEFAULT_CACHE_FILE
            )
            ->addOption(
                'cache-mode',
                null,
                InputOption::VALUE_REQUIRED,
                'Cache invalidation mode: hash (content-based) or mtime (modification time)',
                'hash'
            )
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command validates that PHPDoc @param tags match the actual method signatures.

<comment>Basic usage:</comment>
  <info>%command.full_name% src/</info>

<comment>Multiple paths:</comment>
  <info>%command.full_name% src/ lib/ app/</info>

<comment>With exclusions:</comment>
  <info>%command.full_name% src/ --exclude="*Test.php" --exclude="*/fixtures/*"</info>

<comment>Output formats:</comment>
  <info>%command.full_name% src/ --format=pretty</info>  (default, with colors)
  <info>%command.full_name% src/ --format=json</info>    (machine-readable)
  <info>%command.full_name% src/ --format=github</info>  (GitHub Actions annotations)

<comment>Report missing documentation:</comment>
  <info>%command.full_name% src/ --missing</info>

<comment>Caching (enabled by default):</comment>
  <info>%command.full_name% src/ --no-cache</info>       (disable caching)
  <info>%command.full_name% src/ --clear-cache</info>    (clear cache before run)
  <info>%command.full_name% src/ --cache-file=.my-cache</info>
  <info>%command.full_name% src/ --cache-mode=mtime</info> (faster but less reliable)

<comment>Exit codes:</comment>
  0 - No issues found
  1 - Issues were found
  2 - An error occurred
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string> $paths */
        $paths = $input->getArgument('paths');

        // Default to current directory if no paths provided
        if (empty($paths)) {
            $cwd = getcwd();
            if ($cwd === false) {
                $output->writeln('<error>Could not determine current working directory</error>');

                return self::EXIT_ERROR;
            }
            $paths = [$cwd];
        }

        /** @var string $format */
        $format = $input->getOption('format');

        /** @var list<string> $excludePatterns */
        $excludePatterns = $input->getOption('exclude');

        $noColor = $input->getOption('no-color') || !$this->supportsColors($output);
        $reportMissing = (bool) $input->getOption('missing');

        // Cache options
        $noCache = (bool) $input->getOption('no-cache');
        $clearCache = (bool) $input->getOption('clear-cache');

        /** @var string $cacheFile */
        $cacheFile = $input->getOption('cache-file');

        /** @var string $cacheModeOption */
        $cacheModeOption = $input->getOption('cache-mode');

        // Validate paths exist
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                $output->writeln("<error>Path does not exist: {$path}</error>");

                return self::EXIT_ERROR;
            }
        }

        // Resolve cache mode
        $cacheMode = $this->resolveCacheMode($cacheModeOption, $noCache);
        if ($cacheMode === null) {
            $output->writeln("<error>Invalid cache mode: {$cacheModeOption}. Use 'hash' or 'mtime'.</error>");

            return self::EXIT_ERROR;
        }

        // Create validator
        $validator = new PhpDocValidator();
        $validator->setExcludePatterns($excludePatterns);
        $validator->setReportMissing($reportMissing);

        // Configure caching
        if ($cacheMode->isEnabled()) {
            $signature = new CacheSignature(
                validatorVersion: $this->getValidatorVersion(),
                phpVersion: PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
                reportMissing: $reportMissing,
                cacheMode: $cacheMode,
            );
            $cache = new ValidationCache($cacheFile, $signature);

            if ($clearCache) {
                $cache->clear();
            }

            $validator->setCache($cache);
        }

        // Run validation
        try {
            $report = $validator->validate($paths);
        } catch (\Throwable $e) {
            $output->writeln("<error>Validation error: {$e->getMessage()}</error>");

            return self::EXIT_ERROR;
        }

        // Format and output
        $formatter = $this->createFormatter($format, $noColor);
        $basePath = count($paths) === 1 && is_dir($paths[0]) ? realpath($paths[0]) : getcwd();
        $formattedOutput = $formatter->format($report, $basePath ?: null);

        $output->writeln($formattedOutput);

        return $report->isClean() ? self::EXIT_SUCCESS : self::EXIT_ISSUES_FOUND;
    }

    private function resolveCacheMode(string $option, bool $noCache): ?CacheMode
    {
        if ($noCache) {
            return CacheMode::None;
        }

        return match (strtolower($option)) {
            'hash' => CacheMode::Hash,
            'mtime' => CacheMode::Mtime,
            default => null,
        };
    }

    private function createFormatter(string $format, bool $noColor): FormatterInterface
    {
        return match ($format) {
            'json' => new JsonFormatter(),
            'github' => new GithubActionsFormatter(),
            default => new PrettyFormatter(!$noColor),
        };
    }

    private function supportsColors(OutputInterface $output): bool
    {
        // Check if stdout supports colors
        if (function_exists('stream_isatty') && defined('STDOUT')) {
            return stream_isatty(STDOUT);
        }

        // Fallback: check TERM environment variable
        $term = getenv('TERM');

        return $term !== false && $term !== 'dumb';
    }

    /**
     * Get the validator version for cache signature.
     */
    private function getValidatorVersion(): string
    {
        // Try to get version from Composer's installed packages
        $installedPath = __DIR__ . '/../../vendor/composer/installed.php';

        if (file_exists($installedPath)) {
            /** @var array<string, mixed> $installed */
            $installed = require $installedPath;

            if (
                is_array($installed)
                && isset($installed['versions'])
                && is_array($installed['versions'])
                && isset($installed['versions']['nsrosenqvist/phpdoc-validator'])
                && is_array($installed['versions']['nsrosenqvist/phpdoc-validator'])
                && isset($installed['versions']['nsrosenqvist/phpdoc-validator']['version'])
                && is_string($installed['versions']['nsrosenqvist/phpdoc-validator']['version'])
            ) {
                return $installed['versions']['nsrosenqvist/phpdoc-validator']['version'];
            }
        }

        // Fallback: use a development version indicator
        return 'dev';
    }
}
