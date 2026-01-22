<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Feature;

use NsRosenqvist\PhpDocValidator\PhpDocValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PhpDocValidatorTest extends TestCase
{
    private string $fixturesPath;

    protected function setUp(): void
    {
        $this->fixturesPath = dirname(__DIR__) . '/fixtures';
    }

    #[Test]
    public function validClassHasNoIssues(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/ValidClass.php');

        // ValidClass should have no extra params or type mismatches
        $this->assertSame(0, $report->getIssueCount());
    }

    #[Test]
    public function detectsExtraParams(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/ExtraParamsClass.php');

        $this->assertTrue($report->hasIssues());

        // Should find at least 4 extra params across the methods
        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        $extraParamIssues = array_filter($issues, fn($i) => $i->isExtraParam());
        $this->assertGreaterThanOrEqual(4, count($extraParamIssues));
    }

    #[Test]
    public function detectsTypeMismatches(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/TypeMismatchClass.php');

        $this->assertTrue($report->hasIssues());

        // Should find type mismatches
        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        $typeMismatchIssues = array_filter($issues, fn($i) => $i->isTypeMismatch());
        $this->assertGreaterThanOrEqual(3, count($typeMismatchIssues));
    }

    #[Test]
    public function handlesTypeEquivalences(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/EdgeCasesClass.php');

        // The integer/boolean equivalence and PHPDoc-specific types should pass
        // Only the protected method with extra param should have an issue
        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        // protectedMethod has an extra param
        $this->assertGreaterThanOrEqual(1, count($issues));
    }

    #[Test]
    public function handlesInterfaces(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/SampleInterface.php');

        // Interface has one method with extra param
        $this->assertTrue($report->hasIssues());
        $this->assertSame(1, $report->getIssueCount());
    }

    #[Test]
    public function handlesTraits(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/SampleTrait.php');

        // Trait has one method with type mismatch
        $this->assertTrue($report->hasIssues());
        $this->assertSame(1, $report->getIssueCount());
    }

    #[Test]
    public function handlesFunctions(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/functions.php');

        // Functions file has issues
        $this->assertTrue($report->hasIssues());

        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        // At least 2 issues: extra param and type mismatch
        $this->assertGreaterThanOrEqual(2, count($issues));
    }

    #[Test]
    public function gracefullyHandlesSyntaxErrors(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/BrokenSyntax.php');

        $this->assertTrue($report->hasParseError());
        $this->assertNotNull($report->parseError);
        $this->assertStringContainsString('Parse error', $report->parseError);
    }

    #[Test]
    public function handlesComplexTypes(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/ComplexTypesClass.php');

        // Complex types should all be compatible
        $this->assertSame(0, $report->getIssueCount());
    }

    #[Test]
    public function reportsMissingParamsWhenEnabled(): void
    {
        $validator = new PhpDocValidator();
        $validator->setReportMissing(true);

        $report = $validator->validateFile($this->fixturesPath . '/MissingParamsClass.php');

        $this->assertTrue($report->hasIssues());

        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        $missingIssues = array_filter($issues, fn($i) => $i->isMissingParam());
        $this->assertGreaterThanOrEqual(4, count($missingIssues));
    }

    #[Test]
    public function doesNotReportMissingParamsByDefault(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/MissingParamsClass.php');

        // Without --missing flag, should have no issues
        $this->assertFalse($report->hasIssues());
    }

    #[Test]
    public function validateDirectoryScansAllFiles(): void
    {
        $validator = new PhpDocValidator();
        $validator->setExcludePatterns(['*BrokenSyntax*']);

        $report = $validator->validate([$this->fixturesPath]);

        $this->assertGreaterThanOrEqual(5, $report->getFilesScanned());
        $this->assertTrue($report->hasIssues());
    }

    #[Test]
    public function excludePatternsWork(): void
    {
        $validator = new PhpDocValidator();
        $validator->setExcludePatterns(['*ExtraParams*', '*TypeMismatch*', '*BrokenSyntax*', '*Missing*', '*Edge*', '*Sample*', '*functions*', '*ReturnTypes*']);

        $report = $validator->validate([$this->fixturesPath]);

        // With exclusions, should only scan ValidClass and ComplexTypesClass
        // which should have no issues
        $this->assertFalse($report->hasIssues());
    }

    #[Test]
    public function validateContentWorks(): void
    {
        $validator = new PhpDocValidator();

        $code = <<<'PHP'
<?php
class Test {
    /**
     * @param string $name
     * @param int $extra This doesn't exist
     */
    public function method(string $name): void {}
}
PHP;

        $report = $validator->validateContent($code);

        $this->assertTrue($report->hasIssues());
        $this->assertSame(1, $report->getIssueCount());
    }

    #[Test]
    public function handlesNonExistentFile(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile('/nonexistent/path/file.php');

        $this->assertTrue($report->hasParseError());
        $this->assertNotNull($report->parseError);
        $this->assertStringContainsString('Could not read', $report->parseError);
    }

    #[Test]
    public function detectsReturnTypeMismatches(): void
    {
        $validator = new PhpDocValidator();

        $report = $validator->validateFile($this->fixturesPath . '/ReturnTypesClass.php');

        $this->assertTrue($report->hasIssues());

        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        $returnMismatchIssues = array_filter($issues, fn($i) => $i->isReturnMismatch());
        // mismatchedReturn has int doc but string signature
        $this->assertGreaterThanOrEqual(1, count($returnMismatchIssues));
    }

    #[Test]
    public function validReturnTypesHaveNoIssues(): void
    {
        $validator = new PhpDocValidator();

        $code = <<<'PHP'
<?php
class Test {
    /**
     * @return string
     */
    public function getString(): string {
        return 'hello';
    }

    /**
     * @return int|null
     */
    public function getNullableInt(): ?int {
        return null;
    }

    /**
     * @return void
     */
    public function doNothing(): void {}

    /**
     * @return string[]
     */
    public function getArray(): array {
        return ['a', 'b'];
    }
}
PHP;

        $report = $validator->validateContent($code);

        $this->assertFalse($report->hasIssues());
    }

    #[Test]
    public function skipReturnValidationForConstructors(): void
    {
        $validator = new PhpDocValidator();
        $validator->setReportMissing(true);

        $code = <<<'PHP'
<?php
class Test {
    /**
     * @param string $name
     */
    public function __construct(string $name) {}

    /**
     * Destructor
     */
    public function __destruct() {}
}
PHP;

        $report = $validator->validateContent($code);

        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        $returnIssues = array_filter(
            $issues,
            fn($i) => $i->isReturnMismatch() || $i->isMissingReturn()
        );

        $this->assertCount(0, $returnIssues);
    }

    #[Test]
    public function reportsMissingReturnWhenEnabled(): void
    {
        $validator = new PhpDocValidator();
        $validator->setReportMissing(true);

        $report = $validator->validateFile($this->fixturesPath . '/ReturnTypesClass.php');

        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        $missingReturnIssues = array_filter($issues, fn($i) => $i->isMissingReturn());
        // missingReturnDoc has return type but no @return tag
        $this->assertGreaterThanOrEqual(1, count($missingReturnIssues));
    }

    #[Test]
    public function doesNotReportMissingReturnByDefault(): void
    {
        $validator = new PhpDocValidator();

        $code = <<<'PHP'
<?php
class Test {
    /**
     * No return doc.
     */
    public function method(): string {
        return 'hello';
    }
}
PHP;

        $report = $validator->validateContent($code);

        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        $missingReturnIssues = array_filter($issues, fn($i) => $i->isMissingReturn());
        $this->assertCount(0, $missingReturnIssues);
    }

    #[Test]
    public function noReturnValidationWhenNoReturnType(): void
    {
        $validator = new PhpDocValidator();
        $validator->setReportMissing(true);

        $code = <<<'PHP'
<?php
class Test {
    /**
     * No return type, no return doc - should be fine.
     */
    public function method() {
        return 'anything';
    }
}
PHP;

        $report = $validator->validateContent($code);

        $issues = [];
        foreach ($report->getMethodIssues() as $data) {
            foreach ($data['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        $returnIssues = array_filter(
            $issues,
            fn($i) => $i->isReturnMismatch() || $i->isMissingReturn()
        );

        $this->assertCount(0, $returnIssues);
    }

    #[Test]
    public function arrayNarrowingWorksForReturnTypes(): void
    {
        $validator = new PhpDocValidator();

        $code = <<<'PHP'
<?php
class Test {
    /**
     * @return string[]
     */
    public function getStrings(): array {
        return ['a'];
    }

    /**
     * @return array<int, User>
     */
    public function getUsers(): array {
        return [];
    }

    /**
     * @return list<string>
     */
    public function getList(): array {
        return [];
    }
}
PHP;

        $report = $validator->validateContent($code);

        $this->assertFalse($report->hasIssues());
    }
}
