<?php

namespace shanept\AssemblySimulatorTests\Integration\InstructionRegistration;

use shanept\AssemblySimulator\Instruction\Push;

/**
 * @covers shanept\AssemblySimulator\Instruction\Push
 */
class PushInstructionRegistrationTest extends InstructionRegistrationTestBase
{
    /**
     * {@inheritDoc}
     */
    public static function getClassFqdn(): string
    {
        return Push::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function registrationForOperandDataProvider(): array
    {
        return [
            [Push::class, 0x50],
            [Push::class, 0x51],
            [Push::class, 0x52],
            [Push::class, 0x53],
            [Push::class, 0x54],
            [Push::class, 0x55],
            [Push::class, 0x56],
            [Push::class, 0x57],
            [Push::class, 0x68],
            [Push::class, 0x6A],
        ];
    }
}
