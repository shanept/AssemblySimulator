<?php

namespace shanept\AssemblySimulatorTests\Integration\InstructionRegistration;

use shanept\AssemblySimulator\Simulator;

/**
 * @phpstan-type InstructionClassString class-string<\shanept\AssemblySimulator\Instruction\AssemblyInstruction>
 */
abstract class InstructionRegistrationTestBase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var callable[]
     */
    private static $registrations;

    public static function setUpBeforeClass(): void
    {
        $className = static::getClassFqdn();
        $instruction = new $className();
        self::$registrations = $instruction->register();
    }

    public static function tearDownAfterClass(): void
    {
        self::$registrations = [];
    }

    /**
     * @return InstructionClassString
     */
    abstract public static function getClassFqdn(): string;

    /**
     * @return array<int, array{class-string, int}>
     */
    abstract public static function registrationForOperandDataProvider(): array;

    /**
     * @dataProvider registrationForOperandDataProvider
     *
     * @param InstructionClassString $fullyQualifiedClassName
     */
    public function testRegistrationForOperand(
        string $fullyQualifiedClassName,
        int $operand,
    ): void {
        if (! array_key_exists($operand, self::$registrations)) {
            $classNameParts = explode('\\', $fullyQualifiedClassName);

            $message = sprintf(
                'No registrations for opcode 0x%X in %s Instruction.',
                $operand,
                $classNameParts[count($classNameParts) - 1],
            );

            $this->fail($message);
        } elseif (! is_callable(self::$registrations[$operand])) {
            $classNameParts = explode('\\', $fullyQualifiedClassName);

            $message = sprintf(
                'Registration in %s Instruction for opcode 0x%X is not callable.',
                $classNameParts[count($classNameParts) - 1],
                $operand,
            );

            $this->fail($message);
        } elseif (! is_array(self::$registrations[$operand])) {
            $this->markTestSkipped('Non-array callbacks not supported by test case.');
        }

        // We can now unset the registration to mark that we have tested it.
        $functionName = self::$registrations[$operand][1];
        unset(self::$registrations[$operand]);

        $mockCall = $this->getMockBuilder($fullyQualifiedClassName)
                         ->onlyMethods([$functionName])
                         ->getMock();

        $mockCall->expects($this->once())
                 ->method($functionName)
                 ->willReturnCallback(function () {
                     // We are using a compile error because it's almost guaranteed
                     // that we would never actually encounter this during this context.
                     throw new \CompileError();
                 });

        $simulator = new Simulator(Simulator::PROTECTED_MODE);

        $mockCall->setSimulator($simulator);

        // If we have a two-byte code, add the two byte instruction.
        $codeBuffer = chr($operand & 0xFF);
        if ($operand & 0xF00) {
            $codeBuffer = chr(0xF) . $codeBuffer;
        }

        $simulator->setCodeBuffer($codeBuffer);

        try {
            $simulator->simulate();
        } catch (\CompileError $e) {
            // This is good
        }
    }

    /**
     * @depends testRegistrationForOperand
     */
    public function testAllRegistrationsWereConsumed(): void
    {
        $this->expectNotToPerformAssertions();

        $errorMessage = sprintf(
            'The following opcodes for %s remain untested: [%s]',
            static::getClassFqdn(),
            join(array_keys(self::$registrations)),
        );

        if (count(self::$registrations) > 0) {
            $this->fail($errorMessage);
        }
    }
}
