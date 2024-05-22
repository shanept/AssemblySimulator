<?php

namespace shanept\AssemblySimulatorTests\Fakes;

use shanept\AssemblySimulator\Instruction\AssemblyInstruction;

class TestAssemblyInstruction extends AssemblyInstruction
{
    public function register()
    {
        return [];
    }
}
