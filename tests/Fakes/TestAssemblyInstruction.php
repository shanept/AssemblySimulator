<?php

namespace shanept\AssemblySimulatorTests\Fakes;

use shanept\AssemblySimulator\Instruction\AssemblyInstruction;

class TestAssemblyInstruction extends AssemblyInstruction
{
    /**
     * @return callable[]
     */
    public function register(): array
    {
        return [];
    }

    public function mockableCallback(): bool
    {
        return false;
    }
}
