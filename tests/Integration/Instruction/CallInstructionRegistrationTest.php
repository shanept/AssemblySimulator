<?php

namespace shanept\AssemblySimulatorTests\Integration\Instruction;

use shanept\AssemblySimulator\Instruction\Call;

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
