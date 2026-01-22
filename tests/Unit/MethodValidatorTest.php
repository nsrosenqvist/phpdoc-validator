<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit;

use NsRosenqvist\PhpDocValidator\IssueType;
use NsRosenqvist\PhpDocValidator\MethodInfo;
use NsRosenqvist\PhpDocValidator\Parser\DocBlockParser;
use NsRosenqvist\PhpDocValidator\TypeComparator;
use NsRosenqvist\PhpDocValidator\Validator\MethodValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MethodValidatorTest extends TestCase
{
    private MethodValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new MethodValidator(
            new DocBlockParser(),
            new TypeComparator(),
        );
    }

    #[Test]
    public function validateReturnsEmptyArrayForValidMethod(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: ['name' => 'string'],
            returnType: 'string',
            docComment: <<<'DOC'
/**
 * @param string $name
 * @return string
 */
DOC,
        );

        $issues = $this->validator->validate($method);

        $this->assertSame([], $issues);
    }

    #[Test]
    public function validateDetectsExtraParam(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: [],
            docComment: <<<'DOC'
/**
 * @param string $extra
 */
DOC,
        );

        $issues = $this->validator->validate($method);

        $this->assertCount(1, $issues);
        $this->assertSame(IssueType::ExtraParam, $issues[0]->issueType);
        $this->assertSame('extra', $issues[0]->paramName);
    }

    #[Test]
    public function validateDetectsTypeMismatch(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: ['name' => 'string'],
            docComment: <<<'DOC'
/**
 * @param int $name
 */
DOC,
        );

        $issues = $this->validator->validate($method);

        $this->assertCount(1, $issues);
        $this->assertSame(IssueType::TypeMismatch, $issues[0]->issueType);
    }

    #[Test]
    public function validateDetectsReturnMismatch(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: [],
            returnType: 'string',
            docComment: <<<'DOC'
/**
 * @return int
 */
DOC,
        );

        $issues = $this->validator->validate($method);

        $this->assertCount(1, $issues);
        $this->assertSame(IssueType::ReturnMismatch, $issues[0]->issueType);
    }

    #[Test]
    public function validateReportsMissingParamWhenEnabled(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: ['name' => 'string'],
            docComment: '/** Empty doc */',
        );

        $issues = $this->validator->validate($method, reportMissing: true);

        $this->assertCount(1, $issues);
        $this->assertSame(IssueType::MissingParam, $issues[0]->issueType);
    }

    #[Test]
    public function validateReportsMissingReturnWhenEnabled(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: [],
            returnType: 'string',
            docComment: '/** Empty doc */',
        );

        $issues = $this->validator->validate($method, reportMissing: true);

        $this->assertCount(1, $issues);
        $this->assertSame(IssueType::MissingReturn, $issues[0]->issueType);
    }

    #[Test]
    public function validateDoesNotReportMissingByDefault(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: ['name' => 'string'],
            returnType: 'string',
            docComment: '/** Empty doc */',
        );

        $issues = $this->validator->validate($method, reportMissing: false);

        $this->assertSame([], $issues);
    }

    #[Test]
    public function validateSkipsReturnForConstructors(): void
    {
        $method = new MethodInfo(
            name: '__construct',
            line: 10,
            parameters: [],
            returnType: null,
            docComment: '/** Empty doc */',
        );

        $issues = $this->validator->validate($method, reportMissing: true);

        // Should not report missing @return for constructor
        $returnIssues = array_filter(
            $issues,
            fn($i) => $i->issueType->isReturnIssue()
        );

        $this->assertCount(0, $returnIssues);
    }

    #[Test]
    public function validateParamsOnlyValidatesParams(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: [],
            returnType: 'string',
            docComment: <<<'DOC'
/**
 * @param string $extra
 * @return int
 */
DOC,
        );

        $issues = $this->validator->validateParams($method);

        // Should only find the extra param, not the return mismatch
        $this->assertCount(1, $issues);
        $this->assertSame(IssueType::ExtraParam, $issues[0]->issueType);
    }

    #[Test]
    public function validateReturnOnlyValidatesReturn(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: [],
            returnType: 'string',
            docComment: <<<'DOC'
/**
 * @param string $extra
 * @return int
 */
DOC,
        );

        $issues = $this->validator->validateReturn($method);

        // Should only find the return mismatch, not the extra param
        $this->assertCount(1, $issues);
        $this->assertSame(IssueType::ReturnMismatch, $issues[0]->issueType);
    }

    #[Test]
    public function validateDetectsParamOrderMismatch(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: ['first' => 'int', 'second' => 'string'],
            docComment: <<<'DOC'
/**
 * @param string $second
 * @param int $first
 */
DOC,
        );

        $issues = $this->validator->validate($method);

        $this->assertCount(1, $issues);
        $this->assertSame(IssueType::ParamOrder, $issues[0]->issueType);
        $this->assertSame('first, second', $issues[0]->expectedType);
        $this->assertSame('second, first', $issues[0]->actualType);
    }

    #[Test]
    public function validateDoesNotReportParamOrderWhenCorrect(): void
    {
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: ['first' => 'int', 'second' => 'string'],
            docComment: <<<'DOC'
/**
 * @param int $first
 * @param string $second
 */
DOC,
        );

        $issues = $this->validator->validate($method);

        $this->assertSame([], $issues);
    }

    #[Test]
    public function validateDoesNotReportParamOrderWithMissingParams(): void
    {
        // When some params are missing from docs, we should only compare
        // the params that exist in both, and only report order if those are wrong
        $method = new MethodInfo(
            name: 'test',
            line: 10,
            parameters: ['first' => 'int', 'second' => 'string', 'third' => 'bool'],
            docComment: <<<'DOC'
/**
 * @param int $first
 * @param bool $third
 */
DOC,
        );

        $issues = $this->validator->validate($method);

        // Should not report param order issue since first and third are in correct relative order
        $this->assertSame([], $issues);
    }
}
