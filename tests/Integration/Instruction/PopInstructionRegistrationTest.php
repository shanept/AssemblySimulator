<?php

namespace shanept\AssemblySimulatorTests\Integration\Instruction;

use shanept\AssemblySimulator\Instruction\Pop;

class PopInstructionRegistrationTest extends InstructionRegistrationTestBase
{
    /**
     * {@inheritDoc}
     */
    public static function getClassFqdn(): string
    {
        return Pop::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function registrationForOperandDataProvider(): array
    {
        return [
            [Pop::class, 0x58],
            [Pop::class, 0x59],
            [Pop::class, 0x5A],
            [Pop::class, 0x5B],
            [Pop::class, 0x5C],
            [Pop::class, 0x5D],
            [Pop::class, 0x5E],
            [Pop::class, 0x5F],
        ];
    }
}
