<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator;

/**
 * Represents information about a method extracted from PHP source code.
 */
final readonly class MethodInfo
{
    /**
     * @param string $name Method name
     * @param int $line Line number where the method is defined
     * @param array<string, string|null> $parameters Parameter names mapped to their types (null if untyped)
     * @param string|null $returnType The return type from the signature (null if untyped)
     * @param string|null $docComment The PHPDoc comment, if present
     * @param string|null $className The class name if this is a class method, null for functions
     */
    public function __construct(
        public string $name,
        public int $line,
        public array $parameters,
        public ?string $returnType = null,
        public ?string $docComment = null,
        public ?string $className = null,
    ) {}

    public function getFullName(): string
    {
        if ($this->className !== null) {
            return $this->className . '::' . $this->name;
        }

        return $this->name;
    }

    public function hasDocComment(): bool
    {
        return $this->docComment !== null && trim($this->docComment) !== '';
    }
}
