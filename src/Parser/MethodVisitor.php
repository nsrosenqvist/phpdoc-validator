<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Parser;

use NsRosenqvist\PhpDocValidator\MethodInfo;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

/**
 * AST visitor that extracts method information from PHP source code.
 */
final class MethodVisitor extends NodeVisitorAbstract
{
    /**
     * @var list<MethodInfo>
     */
    private array $methods = [];

    private ?string $currentClass = null;

    private PrettyPrinter $printer;

    public function __construct()
    {
        $this->printer = new PrettyPrinter();
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Class_ || $node instanceof Interface_ || $node instanceof Trait_) {
            $this->currentClass = $node->name?->toString();
        }

        if ($node instanceof ClassMethod) {
            $this->methods[] = $this->extractMethodInfo($node, $this->currentClass);
        }

        if ($node instanceof Function_) {
            $this->methods[] = $this->extractMethodInfo($node, null);
        }

        return null;
    }

    public function leaveNode(Node $node): int|Node|null
    {
        if ($node instanceof Class_ || $node instanceof Interface_ || $node instanceof Trait_) {
            $this->currentClass = null;
        }

        return null;
    }

    /**
     * @return list<MethodInfo>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function reset(): void
    {
        $this->methods = [];
        $this->currentClass = null;
    }

    private function extractMethodInfo(ClassMethod|Function_ $node, ?string $className): MethodInfo
    {
        $parameters = [];

        foreach ($node->getParams() as $param) {
            $paramName = $param->var instanceof Node\Expr\Variable && is_string($param->var->name)
                ? $param->var->name
                : null;

            if ($paramName === null) {
                continue;
            }

            $type = null;
            if ($param->type !== null) {
                $type = $this->typeToString($param->type);
            }

            $parameters[$paramName] = $type;
        }

        $returnType = null;
        if ($node->getReturnType() !== null) {
            $returnType = $this->typeToString($node->getReturnType());
        }

        $docComment = $node->getDocComment()?->getText();

        return new MethodInfo(
            name: $node->name->toString(),
            line: $node->getStartLine(),
            parameters: $parameters,
            returnType: $returnType,
            docComment: $docComment,
            className: $className,
        );
    }

    private function typeToString(Node $type): string
    {
        if ($type instanceof Node\Identifier) {
            return $type->toString();
        }

        if ($type instanceof Node\Name) {
            return $type->toString();
        }

        if ($type instanceof Node\NullableType) {
            return '?' . $this->typeToString($type->type);
        }

        if ($type instanceof Node\UnionType) {
            $types = array_map(fn(Node $t) => $this->typeToString($t), $type->types);

            return implode('|', $types);
        }

        if ($type instanceof Node\IntersectionType) {
            $types = array_map(fn(Node $t) => $this->typeToString($t), $type->types);

            return implode('&', $types);
        }

        // Fallback to pretty printer for complex cases
        return $this->printer->prettyPrint([$type]);
    }
}
