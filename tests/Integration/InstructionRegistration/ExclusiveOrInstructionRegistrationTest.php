<?php

namespace shanept\AssemblySimulatorTests\Integration\InstructionRegistration;

use shanept\AssemblySimulator\Instruction\ExclusiveOr;

/**
 * @covers shanept\AssemblySimulator\Instruction\ExclusiveOr
 */
class ExclusiveOrInstructionRegistrationTest extends InstructionRegistrationTestBase
{
    /**
     * {@inheritDoc}
     */
    public static function getClassFqdn(): string
    {
        return ExclusiveOr::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function registrationForOperandDataProvider(): array
    {
        return [
            [ExclusiveOr::class, 0x30],
            [ExclusiveOr::class, 0x31],
            [ExclusiveOr::class, 0x32],
            [ExclusiveOr::class, 0x33],
        ];
    }
}
