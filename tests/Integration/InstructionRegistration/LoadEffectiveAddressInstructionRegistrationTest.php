<?php

namespace shanept\AssemblySimulatorTests\Integration\InstructionRegistration;

use shanept\AssemblySimulator\Instruction\LoadEffectiveAddress;

/**
 * @covers shanept\AssemblySimulator\Instruction\LoadEffectiveAddress
 */
class LoadEffectiveAddressInstructionRegistrationTest extends InstructionRegistrationTestBase
{
    /**
     * {@inheritDoc}
     */
    public static function getClassFqdn(): string
    {
        return LoadEffectiveAddress::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function registrationForOperandDataProvider(): array
    {
        return [
            [LoadEffectiveAddress::class, 0x8D],
        ];
    }
}
