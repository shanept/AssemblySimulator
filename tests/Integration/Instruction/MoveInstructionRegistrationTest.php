<?php

namespace shanept\AssemblySimulatorTests\Integration\Instruction;

use shanept\AssemblySimulator\Instruction\Move;

class MoveInstructionRegistrationTest extends InstructionRegistrationTestBase
{
    /**
     * {@inheritDoc}
     */
    public static function getClassFqdn(): string
    {
        return Move::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function registrationForOperandDataProvider(): array
    {
        return [
            [Move::class, 0x88],
            [Move::class, 0x89],
            [Move::class, 0x8A],
            [Move::class, 0x8B],
            [Move::class, 0xB0],
            [Move::class, 0xB1],
            [Move::class, 0xB2],
            [Move::class, 0xB3],
            [Move::class, 0xB4],
            [Move::class, 0xB5],
            [Move::class, 0xB6],
            [Move::class, 0xB7],
            [Move::class, 0xB8],
            [Move::class, 0xB9],
            [Move::class, 0xBA],
            [Move::class, 0xBB],
            [Move::class, 0xBC],
            [Move::class, 0xBD],
            [Move::class, 0xBE],
            [Move::class, 0xBF],
            [Move::class, 0xC6],
            [Move::class, 0xC7],
        ];
    }
}
