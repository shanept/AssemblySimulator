<?php
/**
 * This defines a factory that will generate a simulator with the default
 * instruction set pre-loaded. Additional instructions may be added during
 * generation of the simulator.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator;

use shanept\AssemblySimulator\Instruction\ExclusiveOr;
use shanept\AssemblySimulator\Instruction\LoadEffectiveAddress;
use shanept\AssemblySimulator\Instruction\Move;
use shanept\AssemblySimulator\Instruction\Pop;
use shanept\AssemblySimulator\Instruction\Push;

/**
 * A factory to assist with the generation of the simulator.
 *
 * @author Shane Thompson
 */
class SimulatorFactory
{
    public static function createSimulator(
        int $simulatorMode = Simulator::REAL_MODE,
        array $additionalInstructions = [],
    ): Simulator {
        /**
         * We will register additional instructions from lowest to highest
         * priority. This is because the Simulator will reverse the order as
         * we are registering the instructions with it.
         */
        $additionalInstructions = array_reverse($additionalInstructions);

        $instructionSet = self::getDefaultInstructionSet();
        $instructionSet = array_merge($instructionSet, $additionalInstructions);

        $simulator = new Simulator($simulatorMode);

        foreach ($instructionSet as $instructionClass) {
            $instructionInstance = new $instructionClass();
            $instructionInstance->setSimulator($simulator);
        }

        return $simulator;
    }

    public static function getDefaultInstructionSet(): array
    {
        return [
            ExclusiveOr::class,
            LoadEffectiveAddress::class,
            Move::class,
            Pop::class,
            Push::class,
        ];
    }
}
