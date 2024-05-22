<?php

namespace shanept\AssemblySimulatorTests\Fakes;

use shanept\AssemblySimulator\Instruction\AssemblyInstruction;

class TestFactoryCustomInstruction extends AssemblyInstruction
{
    public function register()
    {
        return [];
    }
}
