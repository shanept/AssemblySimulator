<?php
/**
 * Defines our list of eFlags
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator;

/**
 * This class simply acts as a container for defining all our flag masks.
 *
 * @author Shane Thompson
 */
class Flags
{
    const CARRY = 0x1;
    const CF = 0x1;

    const PARITY = 0x4;
    const PF = 0x4;

    const ADJUST = 0x10;
    const AF = 0x10;

    const ZERO = 0x40;
    const ZF = 0x40;

    const SIGN = 0x80;
    const SF = 0x80;

    const TRAP = 0x100;
    const TF = 0x100;

    const INTERRUPTION = 0x200;
    const IF = 0x200;

    const DIRECTION = 0x400;
    const DF = 0x400;

    const OVERFLOW = 0x800;
    const OF = 0x800;

    // I/O Privillege Level
    const IOPL = 0x3000;

    const NESTED = 0x4000;
    const NT = 0x4000;

    // eFlags
    const RESUME = 0x10000;
    const RF = 0x10000;

    const VIRTUAL = 0x20000;
    const VM = 0x20000;

    const ALIGNMENT = 0x40000;
    const AC = 0x40000;

    // Virtual Interrupt Flag
    const VIF = 0x80000;

    // Virtual Interrupt Pending
    const VIP = 0x100000;

    // Able to use CPUID instruction
    const ID = 0x200000;
}
