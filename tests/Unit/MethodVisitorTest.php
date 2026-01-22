<?php

declare(strict_types=1);

namespace NsRosenqvist\PhpDocValidator\Tests\Unit;

use NsRosenqvist\PhpDocValidator\MethodInfo;
use NsRosenqvist\PhpDocValidator\Parser\MethodVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MethodVisitorTest extends TestCase
{
    private \PhpParser\Parser $parser;

    private MethodVisitor $visitor;

    protected function setUp(): void
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->visitor = new MethodVisitor();
    }

    /**
     * @return list<MethodInfo>
     */
    private function extractMethods(string $code): array
    {
        $ast = $this->parser->parse($code);
        $this->assertNotNull($ast);

        $this->visitor->reset();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        return $this->visitor->getMethods();
    }

    #[Test]
    public function extractsClassMethod(): void
    {
        $code = <<<'PHP'
<?php
class MyClass {
    public function myMethod(string $name, int $age): void {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertCount(1, $methods);
        $this->assertSame('myMethod', $methods[0]->name);
        $this->assertSame('MyClass', $methods[0]->className);
        $this->assertSame(['name' => 'string', 'age' => 'int'], $methods[0]->parameters);
    }

    #[Test]
    public function extractsStandaloneFunction(): void
    {
        $code = <<<'PHP'
<?php
function myFunction(string $name): void {}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertCount(1, $methods);
        $this->assertSame('myFunction', $methods[0]->name);
        $this->assertNull($methods[0]->className);
        $this->assertSame(['name' => 'string'], $methods[0]->parameters);
    }

    #[Test]
    public function extractsDocComment(): void
    {
        $code = <<<'PHP'
<?php
class MyClass {
    /**
     * This is a doc comment.
     * @param string $name The name
     */
    public function myMethod(string $name): void {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertCount(1, $methods);
        $this->assertTrue($methods[0]->hasDocComment());
        $this->assertNotNull($methods[0]->docComment);
        $this->assertStringContainsString('@param string $name', $methods[0]->docComment);
    }

    #[Test]
    public function handlesMethodWithoutDocComment(): void
    {
        $code = <<<'PHP'
<?php
class MyClass {
    public function myMethod(string $name): void {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertCount(1, $methods);
        $this->assertFalse($methods[0]->hasDocComment());
        $this->assertNull($methods[0]->docComment);
    }

    #[Test]
    public function extractsNullableTypes(): void
    {
        $code = <<<'PHP'
<?php
class MyClass {
    public function myMethod(?string $name, int|null $age): void {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertSame('?string', $methods[0]->parameters['name']);
        $this->assertSame('int|null', $methods[0]->parameters['age']);
    }

    #[Test]
    public function extractsUnionTypes(): void
    {
        $code = <<<'PHP'
<?php
class MyClass {
    public function myMethod(string|int $value): void {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertSame('string|int', $methods[0]->parameters['value']);
    }

    #[Test]
    public function extractsIntersectionTypes(): void
    {
        $code = <<<'PHP'
<?php
class MyClass {
    public function myMethod(Countable&Iterator $value): void {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertSame('Countable&Iterator', $methods[0]->parameters['value']);
    }

    #[Test]
    public function handlesUntypedParameters(): void
    {
        $code = <<<'PHP'
<?php
class MyClass {
    public function myMethod($name, $age): void {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertSame(['name' => null, 'age' => null], $methods[0]->parameters);
    }

    #[Test]
    public function extractsInterfaceMethods(): void
    {
        $code = <<<'PHP'
<?php
interface MyInterface {
    public function myMethod(string $name): void;
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertCount(1, $methods);
        $this->assertSame('myMethod', $methods[0]->name);
        $this->assertSame('MyInterface', $methods[0]->className);
    }

    #[Test]
    public function extractsTraitMethods(): void
    {
        $code = <<<'PHP'
<?php
trait MyTrait {
    public function myMethod(string $name): void {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertCount(1, $methods);
        $this->assertSame('myMethod', $methods[0]->name);
        $this->assertSame('MyTrait', $methods[0]->className);
    }

    #[Test]
    public function extractsConstructorWithPromotion(): void
    {
        $code = <<<'PHP'
<?php
class MyClass {
    public function __construct(
        public string $name,
        protected int $age,
        private bool $active = true,
    ) {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertCount(1, $methods);
        $this->assertSame('__construct', $methods[0]->name);
        $this->assertSame(['name' => 'string', 'age' => 'int', 'active' => 'bool'], $methods[0]->parameters);
    }

    #[Test]
    public function extractsLineNumber(): void
    {
        $code = <<<'PHP'
<?php

class MyClass {

    public function myMethod(string $name): void {}
}
PHP;

        $methods = $this->extractMethods($code);

        $this->assertSame(5, $methods[0]->line);
    }

    #[Test]
    public function resetClearsMethods(): void
    {
        $code = <<<'PHP'
<?php
function first(): void {}
PHP;

        $this->extractMethods($code);
        $this->assertCount(1, $this->visitor->getMethods());

        $this->visitor->reset();
        $this->assertCount(0, $this->visitor->getMethods());
    }
}
