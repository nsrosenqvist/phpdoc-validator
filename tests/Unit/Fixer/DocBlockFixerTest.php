<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit\Fixer;

use NsRosenqvist\PhpDocValidator\FileReport;
use NsRosenqvist\PhpDocValidator\Fixer\DocBlockFixer;
use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\IssueType;
use NsRosenqvist\PhpDocValidator\MethodInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DocBlockFixerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phpdoc-validator-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    #[Test]
    public function fixesParamOrder(): void
    {
        $content = <<<'PHP'
<?php

class Test
{
    /**
     * @param string $second
     * @param int $first
     */
    public function test(int $first, string $second): void
    {
    }
}
PHP;

        $filePath = $this->tempDir . '/Test.php';
        file_put_contents($filePath, $content);

        $method = new MethodInfo(
            name: 'test',
            line: 9,
            parameters: ['first' => 'int', 'second' => 'string'],
            returnType: 'void',
            docComment: "/**\n * @param string \$second\n * @param int \$first\n */",
            className: 'Test',
        );

        $report = new FileReport($filePath);
        $report->addMethodIssues($method, [
            new Issue(
                type: IssueType::ParamOrder->value,
                paramName: '@params',
                message: 'Parameter order mismatch',
                expectedType: 'first, second',
                actualType: 'second, first',
            ),
        ]);

        $fixer = new DocBlockFixer();
        $fixes = $fixer->fix($report, false);

        $this->assertSame(1, $fixes);

        $result = file_get_contents($filePath);
        $this->assertNotFalse($result);

        // Check that first now comes before second in the docblock
        $firstPos = strpos($result, '@param int $first');
        $secondPos = strpos($result, '@param string $second');

        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertLessThan($secondPos, $firstPos);
    }

    #[Test]
    public function addsMissingParamsWhenFlagSet(): void
    {
        $content = <<<'PHP'
<?php

class Test
{
    /**
     * Summary.
     */
    public function test(int $first, string $second): void
    {
    }
}
PHP;

        $filePath = $this->tempDir . '/Test.php';
        file_put_contents($filePath, $content);

        $method = new MethodInfo(
            name: 'test',
            line: 9,
            parameters: ['first' => 'int', 'second' => 'string'],
            returnType: 'void',
            docComment: "/**\n * Summary.\n */",
            className: 'Test',
        );

        $report = new FileReport($filePath);
        $report->addMethodIssues($method, [
            new Issue(
                type: IssueType::MissingParam->value,
                paramName: 'first',
                message: 'Missing @param for $first',
            ),
            new Issue(
                type: IssueType::MissingParam->value,
                paramName: 'second',
                message: 'Missing @param for $second',
            ),
        ]);

        $fixer = new DocBlockFixer();
        $fixes = $fixer->fix($report, true); // fixMissing = true

        $this->assertSame(2, $fixes);

        $result = file_get_contents($filePath);
        $this->assertNotFalse($result);
        $this->assertStringContainsString('@param int $first', $result);
        $this->assertStringContainsString('@param string $second', $result);
    }

    #[Test]
    public function doesNotAddMissingParamsWhenFlagNotSet(): void
    {
        $content = <<<'PHP'
<?php

class Test
{
    /**
     * Summary.
     */
    public function test(int $first): void
    {
    }
}
PHP;

        $filePath = $this->tempDir . '/Test.php';
        file_put_contents($filePath, $content);

        $method = new MethodInfo(
            name: 'test',
            line: 9,
            parameters: ['first' => 'int'],
            returnType: 'void',
            docComment: "/**\n * Summary.\n */",
            className: 'Test',
        );

        $report = new FileReport($filePath);
        $report->addMethodIssues($method, [
            new Issue(
                type: IssueType::MissingParam->value,
                paramName: 'first',
                message: 'Missing @param for $first',
            ),
        ]);

        $fixer = new DocBlockFixer();
        $fixes = $fixer->fix($report, false); // fixMissing = false

        $this->assertSame(0, $fixes);

        $result = file_get_contents($filePath);
        $this->assertNotFalse($result);
        $this->assertStringNotContainsString('@param int $first', $result);
    }

    #[Test]
    public function addsMissingReturnWhenFlagSet(): void
    {
        $content = <<<'PHP'
<?php

class Test
{
    /**
     * Summary.
     */
    public function test(): string
    {
        return '';
    }
}
PHP;

        $filePath = $this->tempDir . '/Test.php';
        file_put_contents($filePath, $content);

        $method = new MethodInfo(
            name: 'test',
            line: 9,
            parameters: [],
            returnType: 'string',
            docComment: "/**\n * Summary.\n */",
            className: 'Test',
        );

        $report = new FileReport($filePath);
        $report->addMethodIssues($method, [
            new Issue(
                type: IssueType::MissingReturn->value,
                paramName: '@return',
                message: 'Missing @return',
            ),
        ]);

        $fixer = new DocBlockFixer();
        $fixes = $fixer->fix($report, true);

        $this->assertSame(1, $fixes);

        $result = file_get_contents($filePath);
        $this->assertNotFalse($result);
        $this->assertStringContainsString('@return string', $result);
    }

    #[Test]
    public function createsNewDocBlockWhenNoneExists(): void
    {
        $content = <<<'PHP'
<?php

class Test
{
    public function test(int $first): string
    {
        return '';
    }
}
PHP;

        $filePath = $this->tempDir . '/Test.php';
        file_put_contents($filePath, $content);

        $method = new MethodInfo(
            name: 'test',
            line: 5,
            parameters: ['first' => 'int'],
            returnType: 'string',
            docComment: null,
            className: 'Test',
        );

        $report = new FileReport($filePath);
        $report->addMethodIssues($method, [
            new Issue(
                type: IssueType::MissingParam->value,
                paramName: 'first',
                message: 'Missing @param for $first',
            ),
            new Issue(
                type: IssueType::MissingReturn->value,
                paramName: '@return',
                message: 'Missing @return',
            ),
        ]);

        $fixer = new DocBlockFixer();
        $fixes = $fixer->fix($report, true);

        $this->assertSame(2, $fixes);

        $result = file_get_contents($filePath);
        $this->assertNotFalse($result);
        $this->assertStringContainsString('/**', $result);
        $this->assertStringContainsString('@param int $first', $result);
        $this->assertStringContainsString('@return string', $result);
        $this->assertStringContainsString('*/', $result);
    }

    #[Test]
    public function returnsZeroForNonExistentFile(): void
    {
        $filePath = $this->tempDir . '/NonExistent.php';

        $method = new MethodInfo(
            name: 'test',
            line: 5,
            parameters: ['first' => 'int'],
            returnType: 'string',
            docComment: null,
            className: 'Test',
        );

        $report = new FileReport($filePath);
        $report->addMethodIssues($method, [
            new Issue(
                type: IssueType::MissingParam->value,
                paramName: 'first',
                message: 'Missing @param for $first',
            ),
        ]);

        $fixer = new DocBlockFixer();
        $fixes = $fixer->fix($report, true);

        $this->assertSame(0, $fixes);
    }
}
