<?php

namespace shanept\AssemblySimulatorTests\Unit\Stack;

use shanept\AssemblySimulator\Stack\ForgivingStack;

/**
 * @depends ForgivingStackTest
 * @covers shanept\AssemblySimulator\Stack\ForgivingStack
 */
class ForgivingStackTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array<int, array{int}>
     */
    public static function writeStackPastLimitsThrowsNoExceptionDataProvider(): array
    {
        return [
            [127],
            [0x3FFF],
            [0x4001],
            [0x5007],
        ];
    }

    /**
     * This unit test sets a stack of X bytes, then writes to X bytes into that
     * stack. This fails because our stack size is 1-based, whereas our stack
     * offset is 0-based. For example, a stack with a size of 1, will start at
     * offset 0. Attempts to write to offset 1 would overflow.
     *
     * @dataProvider writeStackPastLimitsThrowsNoExceptionDataProvider
     * @small
     */
    public function testWriteStackPastMemoryLimitThrowsNoException(
        int $stackSize
    ): void {
        $this->expectNotToPerformAssertions();

        $stack = new ForgivingStack();
        $stack->setAddress($stackSize);
        $stack->limitSize($stackSize);

        $stack->setOffset($stackSize - 3, "\x00\x00\x00\x00");

        $stack->setOffset(0, "fail");
    }

    /**
     * @return array<int, array{array<int, string>, string}>
     */
    public static function writeStackDataProvider(): array
    {
        return [
            [[0 => "\x3", 1 => "\x4"], "\x4\x3"],
            [[0 => "\x3", 2 => "\x4"], "\x4\x0\x3"],
            [[0 => "\x3", 4 => "\x2\x1"], "\x2\x1\x0\x0\x3"],
            [[1 => "\x2"], "\x2\x0"],
            [[126 => "\x34\x45"], "\x34\x45" . str_repeat("\x00", 125)],
        ];
    }

    /**
     * @dataProvider writeStackDataProvider
     * @small
     *
     * @param array<int, string> $values
     */
    public function testGetStackContents(array $values, string $expected): void
    {
        $stack = new ForgivingStack();

        $stackAddress = 0x3FFF;
        $stack->setAddress($stackAddress);
        $stack->limitSize(127);

        foreach ($values as $position => $value) {
            $stack->setOffset($stackAddress - $position, $value);
        }

        $this->assertEquals($expected, $stack->getStackContents());
    }

    /**
     * @dataProvider writeStackDataProvider
     * @small
     *
     * @param array<int, string> $values
     */
    public function testReadStackOffset(array $values, string $unused): void
    {
        $stack = new ForgivingStack();

        $stackAddress = 0x3FFF;
        $stack->setAddress($stackAddress);
        $stack->limitSize(127);

        foreach ($values as $position => $value) {
            $stack->setOffset($stackAddress - $position, $value);
        }

        foreach ($values as $position => $expected) {
            $length = strlen($expected);
            $actual = $stack->getOffset($stackAddress - $position, $length);

            $message = sprintf(
                'Assertion failure at position %d (0x%X).',
                $position,
                $stackAddress - $position,
            );

            $this->assertEquals($expected, $actual, $message);
        }
    }

    /**
     * @return array<int, array{array<int, string>, string}>
     */
    public static function overwriteStackDataProvider(): array
    {
        return [
            [
                [
                    3 => "\x34\x65\x42\x12",
                    5 => "\x60\x35",
                    4 => "\x00\x00",
                ],
                "\x60\x00\x00\x65\x42\x12",
            ],
            [
                [
                    2 => "\x45\x9A\x4F",
                    1 => "\x00",
                ],
                "\x45\x00\x4F",
            ],
        ];
    }

    /**
     * Now, this one will be more tricky. We will set up the stack then
     * overwrite part of it to see if it gives us the correct value.
     *
     * @dataProvider overwriteStackDataProvider
     * @small
     *
     * @param array<int, string> $writes
     */
    public function testOverwriteStack(array $writes, string $expected): void
    {
        $stack = new ForgivingStack();

        $stackAddress = 0x3FFF;
        $stack->setAddress($stackAddress);
        $stack->limitSize(127);

        foreach ($writes as $position => $value) {
            $stack->setOffset($stackAddress - $position, $value);
        }

        $this->assertEquals($expected, $stack->getStackContents());
    }

    public function testOverwriteStackWithIdenticalOffset(): void
    {
        $stack = new ForgivingStack();

        $stackAddress = 0x3FFF;
        $stack->setAddress($stackAddress);
        $stack->limitSize(127);

        $stack->setOffset(0x3FFE, "\xC3\x01");
        $stack->setOffset(0x3FFE, "\x02");

        $this->assertEquals("\x02\x01", $stack->getStackContents());
    }

    /**
     * @return array<int, array{array<int, string>, int, string}>
     */
    public static function clearStackAtDataProvider(): array
    {
        return [
            [
                [
                    0 => "\x43",
                    1 => "\x32",
                    2 => "\x65",
                    4 => "\x43\x42",
                ],
                4,
                "\x65\x32\x43",
            ],
            [
                [
                    0 => "\x43",
                    2 => "\x3\x4",
                    3 => "\x78",
                ],
                1,
                "\x78\x3\x0\x43",
            ],
            [
                [
                    0 => "\x32",
                    1 => "\x21",
                    2 => "\x54",
                    3 => "\x89",
                ],
                1,
                "\x89\x54\x0\x32",
            ],
            [
                [
                    0 => "\x42",
                    1 => "\x31",
                    2 => "\x64",
                    3 => "\x79",
                ],
                0,
                "\x79\x64\x31\x0",
            ],
            [
                [
                    2  => "\x12\x34\x56",
                    4  => "\x43\x21",
                    8  => "\x64\x68\x30\x66",
                    10  => "\x42\x42",
                    14 => "\xCC\xCC\xCC\xCC",
                    19 => "\x12\x00\x33\x00\x55",
                ],
                8,
                "\x12\x00\x33\x00\x55\xCC\xCC\xCC\xCC\x42\x42\x00\x00\x00\x00" .
                    "\x43\x21\x12\x34\x56",
            ],
        ];
    }

    /**
     * @dataProvider clearStackAtDataProvider
     * @small
     *
     * @param array<int, string> $stackArray
     */
    public function testClearStackAt(
        array $stackArray,
        int $clearIdx,
        string $expected
    ): void {
        $stack = new ForgivingStack();

        $stackAddress = 0x3FFF;
        $stack->setAddress($stackAddress);
        $stack->limitSize(127);

        foreach ($stackArray as $position => $value) {
            $stack->setOffset($stackAddress - $position, $value);
        }

        $length = strlen($stackArray[$clearIdx] ?? " ");
        $stack->clearOffset($stackAddress - $clearIdx, $length);

        $this->assertEquals($expected, $stack->getStackContents());
    }

    /**
     * @small
     */
    public function testReadStackUnderflowThrowsNoException(): void
    {
        $stack = new ForgivingStack();

        $stack->setAddress(0x10);
        $stack->limitSize(127);

        $this->assertEquals('', $stack->getOffset(0x11, 1));
    }

    /**
     * @small
     */
    public function testReadStackAtInvalidOffsetThrowsNoException(): void
    {
        $stack = new ForgivingStack();

        $stack->setAddress(0x3FFF);
        $stack->limitSize(127);

        $stack->setOffset(0x3FFA, "abcdef");

        $this->assertEquals('', $stack->getOffset(4560, 2));
    }

    /**
     * @small
     */
    public function testWriteStackUnderflowThrowsNoException(): void
    {
        $this->expectNotToPerformAssertions();

        $stack = new ForgivingStack();

        $stack->setAddress(0x10);
        $stack->limitSize(127);

        $stack->setOffset(0x11, " ");
    }

    /**
     * @small
     */
    public function testClearStackUnderflowThrowsNoException(): void
    {
        $this->expectNotToPerformAssertions();

        $stack = new ForgivingStack();

        $stack->setAddress(0x10);
        $stack->limitSize(127);

        $stack->clearOffset(0x11, 2);
    }

    /**
     * @small
     *
     * @depends testGetStackContents
     */
    public function testClearStackAtInvalidOffsetThrowsNoException(): void
    {
        $this->expectNotToPerformAssertions();

        $stack = new ForgivingStack();

        $stack->setAddress(0x3FFF);
        $stack->limitSize(127);

        $stack->setOffset(0x3FFF, "\x01");

        $stack->clearOffset(4560, 1);
    }

    /**
     * This function will still throw exceptions. It is expected.
     *
     * @small
     */
    public function testWriteStackSize(): void
    {
        $stack = new ForgivingStack();
        $stack->setAddress(99);
        $stack->limitSize(200);

        $stack->setOffset(90, "\0");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Attempted to limit size of the stack to %d bytes, smaller ' .
            'than the length of the data existing on the stack (%d bytes).',
            5,
            10,
        ));
        $stack->limitSize(5);
    }

    /**
     * @small
     *
     * @depends testGetStackContents
     */
    public function testClearStack(): void
    {
        $stack = new ForgivingStack();

        $stack->setAddress(90);
        $stack->limitSize(20);

        $stack->setOffset(80, "abc");

        $this->assertEquals("abc\0\0\0\0\0\0\0\0", $stack->getStackContents());

        $stack->clear();
        $this->assertEquals("", $stack->getStackContents());
    }
}
