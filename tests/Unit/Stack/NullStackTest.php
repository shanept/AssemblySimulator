<?php

namespace shanept\AssemblySimulatorTests\Unit\Stack;

use PHPUnit\Framework\TestCase;
use shanept\AssemblySimulator\Stack\NullStack;

/**
* @covers shanept\AssemblySimulator\Stack\NullStack
*/
class NullStackTest extends TestCase
{
    public function testGetStackReturnsNulString(): void
    {
        $stack = new NullStack();

        $this->assertEquals("\0\0\0", $stack->getOffset(0, 3));
        $this->assertEquals("\0\0\0\0\0", $stack->getOffset(1, 5));
    }

    public function testSetStack(): void
    {
        $stack = new NullStack();

        $this->expectNotToPerformAssertions();

        $stack->setOffset(0, "432");
        $stack->setOffset(PHP_INT_MAX, "123");
    }

    public function testClearStack(): void
    {
        $stack = new NullStack();

        $this->expectNotToPerformAssertions();

        $stack->clearOffset(3, 42);
        $stack->clearOffset(PHP_INT_MAX, PHP_INT_MAX);
    }

    public function testSetAddress(): void
    {
        $stack = new NullStack();

        $this->expectNotToPerformAssertions();

        $stack->setAddress(0);
        $stack->setAddress(PHP_INT_MIN);
        $stack->setAddress(PHP_INT_MAX);
    }

    public function testLimitSize(): void
    {
        $stack = new NullStack();

        $this->expectNotToPerformAssertions();

        $stack->limitSize(0);
        $stack->limitSize(PHP_INT_MIN);
        $stack->limitSize(PHP_INT_MAX);
    }

    public function testFullStack(): void
    {
        $stack = new NullStack();

        $stack->limitSize(0);

        $stack->setAddress(1);
        $stack->setOffset(43, "41");
        $this->assertEquals("\0\0", $stack->getOffset(43, 2));

        $stack->setAddress(100);
        $stack->setOffset(43, "ac");
        $this->assertEquals("\0\0", $stack->getOffset(43, 2));
    }
}
