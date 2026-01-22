<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit;

use NsRosenqvist\PhpDocValidator\IssueType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IssueTypeTest extends TestCase
{
    #[Test]
    public function enumHasCorrectValues(): void
    {
        $this->assertSame('extra_param', IssueType::ExtraParam->value);
        $this->assertSame('type_mismatch', IssueType::TypeMismatch->value);
        $this->assertSame('missing_param', IssueType::MissingParam->value);
        $this->assertSame('return_mismatch', IssueType::ReturnMismatch->value);
        $this->assertSame('missing_return', IssueType::MissingReturn->value);
    }

    #[Test]
    public function isParamIssueReturnsTrueForParamTypes(): void
    {
        $this->assertTrue(IssueType::ExtraParam->isParamIssue());
        $this->assertTrue(IssueType::TypeMismatch->isParamIssue());
        $this->assertTrue(IssueType::MissingParam->isParamIssue());
    }

    #[Test]
    public function isParamIssueReturnsFalseForReturnTypes(): void
    {
        $this->assertFalse(IssueType::ReturnMismatch->isParamIssue());
        $this->assertFalse(IssueType::MissingReturn->isParamIssue());
    }

    #[Test]
    public function isReturnIssueReturnsTrueForReturnTypes(): void
    {
        $this->assertTrue(IssueType::ReturnMismatch->isReturnIssue());
        $this->assertTrue(IssueType::MissingReturn->isReturnIssue());
    }

    #[Test]
    public function isReturnIssueReturnsFalseForParamTypes(): void
    {
        $this->assertFalse(IssueType::ExtraParam->isReturnIssue());
        $this->assertFalse(IssueType::TypeMismatch->isReturnIssue());
        $this->assertFalse(IssueType::MissingParam->isReturnIssue());
    }

    #[Test]
    public function isMismatchReturnsTrueForMismatchTypes(): void
    {
        $this->assertTrue(IssueType::TypeMismatch->isMismatch());
        $this->assertTrue(IssueType::ReturnMismatch->isMismatch());
    }

    #[Test]
    public function isMismatchReturnsFalseForNonMismatchTypes(): void
    {
        $this->assertFalse(IssueType::ExtraParam->isMismatch());
        $this->assertFalse(IssueType::MissingParam->isMismatch());
        $this->assertFalse(IssueType::MissingReturn->isMismatch());
    }

    #[Test]
    public function isMissingReturnsTrueForMissingTypes(): void
    {
        $this->assertTrue(IssueType::MissingParam->isMissing());
        $this->assertTrue(IssueType::MissingReturn->isMissing());
    }

    #[Test]
    public function isMissingReturnsFalseForNonMissingTypes(): void
    {
        $this->assertFalse(IssueType::ExtraParam->isMissing());
        $this->assertFalse(IssueType::TypeMismatch->isMissing());
        $this->assertFalse(IssueType::ReturnMismatch->isMissing());
    }

    #[Test]
    public function canCreateFromValue(): void
    {
        $this->assertSame(IssueType::ExtraParam, IssueType::from('extra_param'));
        $this->assertSame(IssueType::TypeMismatch, IssueType::from('type_mismatch'));
        $this->assertSame(IssueType::MissingParam, IssueType::from('missing_param'));
        $this->assertSame(IssueType::ReturnMismatch, IssueType::from('return_mismatch'));
        $this->assertSame(IssueType::MissingReturn, IssueType::from('missing_return'));
    }
}
