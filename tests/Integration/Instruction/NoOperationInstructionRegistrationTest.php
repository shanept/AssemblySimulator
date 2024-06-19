<?php

namespace shanept\AssemblySimulatorTests\Integration\Instruction;

use shanept\AssemblySimulator\Instruction\NoOperation;

class NoOperationInstructionRegistrationTest extends InstructionRegistrationTestBase
{
    /**
     * {@inheritDoc}
     */
    public static function getClassFqdn(): string
    {
        return NoOperation::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function registrationForOperandDataProvider(): array
    {
        return [
            [NoOperation::class, 0x90],
            [NoOperation::class, 0xF1F],
        ];
    }
}
