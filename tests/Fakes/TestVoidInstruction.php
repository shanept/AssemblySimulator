<?php

namespace shanept\AssemblySimulatorTests\Fakes;

use shanept\AssemblySimulator\Instruction\AssemblyInstruction;

class TestVoidInstruction extends AssemblyInstruction
{
    public function register()
    {
        return [];
    }

    public function returnVoid() {}
    public static function returnVoidStatic() {}
}
