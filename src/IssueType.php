<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator;

/**
 * Types of validation issues that can be detected.
 */
enum IssueType: string
{
    case ExtraParam = 'extra_param';
    case TypeMismatch = 'type_mismatch';
    case MissingParam = 'missing_param';
    case ReturnMismatch = 'return_mismatch';
    case MissingReturn = 'missing_return';
    case ParamOrder = 'param_order';

    public function isParamIssue(): bool
    {
        return in_array($this, [self::ExtraParam, self::TypeMismatch, self::MissingParam, self::ParamOrder], true);
    }

    public function isReturnIssue(): bool
    {
        return in_array($this, [self::ReturnMismatch, self::MissingReturn], true);
    }

    public function isMismatch(): bool
    {
        return in_array($this, [self::TypeMismatch, self::ReturnMismatch], true);
    }

    public function isMissing(): bool
    {
        return in_array($this, [self::MissingParam, self::MissingReturn], true);
    }

    public function isFixable(): bool
    {
        return in_array($this, [self::MissingParam, self::MissingReturn, self::ParamOrder], true);
    }
}
