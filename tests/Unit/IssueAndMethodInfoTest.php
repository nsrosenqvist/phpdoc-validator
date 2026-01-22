<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit;

use NsRosenqvist\PhpDocValidator\Issue;
use NsRosenqvist\PhpDocValidator\MethodInfo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IssueAndMethodInfoTest extends TestCase
{
    #[Test]
    public function issueIsExtraParamReturnsTrueForExtraParamType(): void
    {
        $issue = new Issue(Issue::TYPE_EXTRA_PARAM, 'name', 'message');

        $this->assertTrue($issue->isExtraParam());
        $this->assertFalse($issue->isTypeMismatch());
        $this->assertFalse($issue->isMissingParam());
    }

    #[Test]
    public function issueIsTypeMismatchReturnsTrueForTypeMismatchType(): void
    {
        $issue = new Issue(Issue::TYPE_TYPE_MISMATCH, 'name', 'message', 'string', 'int');

        $this->assertTrue($issue->isTypeMismatch());
        $this->assertFalse($issue->isExtraParam());
        $this->assertFalse($issue->isMissingParam());
    }

    #[Test]
    public function issueIsMissingParamReturnsTrueForMissingParamType(): void
    {
        $issue = new Issue(Issue::TYPE_MISSING_PARAM, 'name', 'message');

        $this->assertTrue($issue->isMissingParam());
        $this->assertFalse($issue->isExtraParam());
        $this->assertFalse($issue->isTypeMismatch());
    }

    #[Test]
    public function issueConstructorSetsAllProperties(): void
    {
        $issue = new Issue(
            type: Issue::TYPE_TYPE_MISMATCH,
            paramName: 'count',
            message: 'Type mismatch',
            expectedType: 'int',
            actualType: 'string',
        );

        $this->assertSame(Issue::TYPE_TYPE_MISMATCH, $issue->type);
        $this->assertSame('count', $issue->paramName);
        $this->assertSame('Type mismatch', $issue->message);
        $this->assertSame('int', $issue->expectedType);
        $this->assertSame('string', $issue->actualType);
    }

    #[Test]
    public function methodInfoGetFullNameReturnsClassAndMethodForClassMethod(): void
    {
        $method = new MethodInfo('doSomething', 10, [], null, null, 'MyClass');

        $this->assertSame('MyClass::doSomething', $method->getFullName());
    }

    #[Test]
    public function methodInfoGetFullNameReturnsMethodOnlyForFunction(): void
    {
        $method = new MethodInfo('doSomething', 10, []);

        $this->assertSame('doSomething', $method->getFullName());
    }

    #[Test]
    public function methodInfoHasDocCommentReturnsTrueWhenPresent(): void
    {
        $method = new MethodInfo('test', 10, [], null, '/** @param string $x */');

        $this->assertTrue($method->hasDocComment());
    }

    #[Test]
    public function methodInfoHasDocCommentReturnsFalseWhenNull(): void
    {
        $method = new MethodInfo('test', 10, []);

        $this->assertFalse($method->hasDocComment());
    }

    #[Test]
    public function methodInfoHasDocCommentReturnsFalseWhenEmpty(): void
    {
        $method = new MethodInfo('test', 10, [], null, '   ');

        $this->assertFalse($method->hasDocComment());
    }
}
