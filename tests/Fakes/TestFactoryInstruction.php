<?php

namespace shanept\AssemblySimulatorTests\Fakes;

use shanept\AssemblySimulator\Instruction\AssemblyInstruction;

class TestFactoryInstruction extends AssemblyInstruction
{
    /**
     * @var int
     */
    static $opcode;

    /**
     * @var callable
     */
    static $callback;

    /**
     * @return callable[]
     */
    public function register(): array
    {
        return [
            static::$opcode => [&$this, 'staticCallback'],
        ];
    }

    public function staticCallback(): bool
    {
        return call_user_func(self::$callback);
    }
}
