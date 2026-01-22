<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator;

/**
 * Represents a single validation issue found in a method.
 */
final readonly class Issue
{
    /**
     * @deprecated Use IssueType::ExtraParam instead
     */
    public const TYPE_EXTRA_PARAM = 'extra_param';

    /**
     * @deprecated Use IssueType::TypeMismatch instead
     */
    public const TYPE_TYPE_MISMATCH = 'type_mismatch';

    /**
     * @deprecated Use IssueType::MissingParam instead
     */
    public const TYPE_MISSING_PARAM = 'missing_param';

    /**
     * @deprecated Use IssueType::ReturnMismatch instead
     */
    public const TYPE_RETURN_MISMATCH = 'return_mismatch';

    /**
     * @deprecated Use IssueType::MissingReturn instead
     */
    public const TYPE_MISSING_RETURN = 'missing_return';

    public IssueType $issueType;

    public function __construct(
        public string $type,
        public string $paramName,
        public string $message,
        public ?string $expectedType = null,
        public ?string $actualType = null,
    ) {
        $this->issueType = IssueType::from($type);
    }

    public function isExtraParam(): bool
    {
        return $this->issueType === IssueType::ExtraParam;
    }

    public function isTypeMismatch(): bool
    {
        return $this->issueType === IssueType::TypeMismatch;
    }

    public function isMissingParam(): bool
    {
        return $this->issueType === IssueType::MissingParam;
    }

    public function isReturnMismatch(): bool
    {
        return $this->issueType === IssueType::ReturnMismatch;
    }

    public function isMissingReturn(): bool
    {
        return $this->issueType === IssueType::MissingReturn;
    }

    public function isParamOrder(): bool
    {
        return $this->issueType === IssueType::ParamOrder;
    }

    public function isFixable(): bool
    {
        return $this->issueType->isFixable();
    }
}
