<?php

namespace shanept\AssemblySimulatorTests\Fakes;

use shanept\AssemblySimulator\Instruction\AssemblyInstruction;

class TestVoidInstruction extends AssemblyInstruction
{
    /**
     * @return callable[]
     */
    public function register(): array
    {
        return [];
    }

    public function returnVoid(): void {}
    public static function returnVoidStatic(): void {}
}
