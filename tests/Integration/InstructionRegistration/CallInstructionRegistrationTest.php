<?php

namespace shanept\AssemblySimulatorTests\Integration\InstructionRegistration;

use shanept\AssemblySimulator\Instruction\Call;

/**
 * @covers shanept\AssemblySimulator\Instruction\Call
 */
class CallInstructionRegistrationTest extends InstructionRegistrationTestBase
{
    /**
     * {@inheritDoc}
     */
    public static function getClassFqdn(): string
    {
        return Call::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function registrationForOperandDataProvider(): array
    {
        return [
            [Call::class, 0xE8],
        ];
    }
}
