<?php

namespace shanept\AssemblySimulatorTests\Integration\Instruction;

use shanept\AssemblySimulator\Instruction\CallInstruction;

class CallInstructionRegistrationTest extends InstructionRegistrationTestBase
{
    /**
     * {@inheritDoc}
     */
    public static function getClassFqdn(): string
    {
        return CallInstruction::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function registrationForOperandDataProvider(): array
    {
        return [
            [CallInstruction::class, 0xE8],
        ];
    }
}
