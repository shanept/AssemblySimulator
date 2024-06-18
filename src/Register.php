<?php
/**
 * Defines our list of register constants.
 *
 * @author Shane Thompson
 */

declare(strict_types=1);

namespace shanept\AssemblySimulator;

/**
 * This class simply acts as a container for defining all our registers.
 *
 * @author Shane Thompson
 */
class Register
{
    const MASK_WIDTH = [
        8 => 255,
        16 => 65535,
        32 => 4294967295,
        // Technically, this is 63-bit
        64 => PHP_INT_MAX,
    ];

    // General Purpose Registers
    // Return Value (accumulator)
    const RAX = [       // r64(/r)
        'offset' => 0,
        'code' => 0,
        'name' => '%rax',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const EAX = [       // r32(/r)
        'offset' => 0,
        'code' => 0,
        'name' => '%eax',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const AX  = [       // r16(/r)
        'offset' => 0,
        'code' => 0,
        'name' => '%ax',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const AL  = [       //  r8(/r) with/out REX prefix
        'offset' => 0,
        'code' => 0,
        'name' => '%al',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];

    // Fourth function argument
    const RCX = [       // r64(/r)
        'offset' => 1,
        'code' => 1,
        'name' => '%rcx',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const ECX = [       // r32(/r)
        'offset' => 1,
        'code' => 1,
        'name' => '%ecx',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const CX  = [       // r16(/r)
        'offset' => 1,
        'code' => 1,
        'name' => '%cx',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const CL  = [       //  r8(/r) with/out REX prefix
        'offset' => 1,
        'code' => 1,
        'name' => '%cl',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    // Third function argument
    const RDX = [       // r64(/r)
        'offset' => 2,
        'code' => 2,
        'name' => '%rdx',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const EDX = [       // r32(/r)
        'offset' => 2,
        'code' => 2,
        'name' => '%edx',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const  DX = [       // r16(/r)
        'offset' => 2,
        'code' => 2,
        'name' => '%dx',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const  DL = [       //  r8(/r) with/out REX prefix
        'offset' => 2,
        'code' => 2,
        'name' => '%dl',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    const RBX = [       // r64(/r)
        'offset' => 3,
        'code' => 3,
        'name' => '%rbx',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const EBX = [       // r32(/r)
        'offset' => 3,
        'code' => 3,
        'name' => '%ebx',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const  BX = [       // r16(/r)
        'offset' => 3,
        'code' => 3,
        'name' => '%bx',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const  BL = [       //  r8(/r) with/out REX prefix
        'offset' => 3,
        'code' => 3,
        'name' => '%bl',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];

    // Second function argument
    const RSI = [       // r64(/r)
        'offset' => 6,
        'code' => 6,
        'name' => '%rsi',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const ESI = [       // r32(/r)
        'offset' => 6,
        'code' => 6,
        'name' => '%esi',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const  SI = [       // r16(/r)
        'offset' => 6,
        'code' => 6,
        'name' => '%si',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const SIL = [       //  r8(/r) with REX prefix
        'offset' => 6,
        'code' => 6,
        'name' => '%sil',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];

    const  DH = [       //  r8(/r) without REX prefix
        'offset' => 6,
        'code' => 6,
        'name' => '%dh',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    // First function argument
    const RDI = [       // r64(/r)
        'offset' => 7,
        'code' => 7,
        'name' => '%rdi',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const EDI = [       // r32(/r)
        'offset' => 7,
        'code' => 7,
        'name' => '%edi',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const  DI = [       // r16(/r)
        'offset' => 7,
        'code' => 7,
        'name' => '%di',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const DIL = [       //  r8(/r) with REX prefix
        'offset' => 7,
        'code' => 7,
        'name' => '%dil',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];

    const  BH = [       //  r8(/r) without REX prefix
        'offset' => 7,
        'code' => 7,
        'name' => '%bh',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    // General Purpose Registers with REX.R=1
    // Fifth function argument
    const R8  = [       // r64(/r)
        'offset' => 8,
        'code' => 0,
        'name' => '%r8',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const R8D = [       // r32(/r)
        'offset' => 8,
        'code' => 0,
        'name' => '%r8d',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const R8W = [       // r16(/r)
        'offset' => 8,
        'code' => 0,
        'name' => '%r8w',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const R8B = [       //  r8(/r)
        'offset' => 8,
        'code' => 0,
        'name' => '%r8b',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    // Sixth function argument
    const R9  = [       // r64(/r)
        'offset' => 9,
        'code' => 1,
        'name' => '%r9',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const R9D = [       // r32(/r)
        'offset' => 9,
        'code' => 1,
        'name' => '%r9d',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const R9W = [       // r16(/r)
        'offset' => 9,
        'code' => 1,
        'name' => '%r9w',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const R9B = [       //  r8(/r)
        'offset' => 9,
        'code' => 1,
        'name' => '%r9b',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    const R10  = [      // r64(/r)
        'offset' => 10,
        'code' => 2,
        'name' => '%r10',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const R10D = [      // r32(/r)
        'offset' => 10,
        'code' => 2,
        'name' => '%r10d',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const R10W = [      // r16(/r)
        'offset' => 10,
        'code' => 2,
        'name' => '%r10w',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const R10B = [      //  r8(/r)
        'offset' => 10,
        'code' => 2,
        'name' => '%r10b',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    const R11  = [      // r64(/r)
        'offset' => 11,
        'code' => 3,
        'name' => '%r11',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const R11D = [      // r32(/r)
        'offset' => 11,
        'code' => 3,
        'name' => '%r11d',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const R11W = [      // r16(/r)
        'offset' => 11,
        'code' => 3,
        'name' => '%r11w',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const R11B = [      //  r8(/r)
        'offset' => 11,
        'code' => 3,
        'name' => '%r11b',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    const R12  = [      // r64(/r)
        'offset' => 12,
        'code' => 4,
        'name' => '%r12',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const R12D = [      // r32(/r)
        'offset' => 12,
        'code' => 4,
        'name' => '%r12d',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const R12W = [      // r16(/r)
        'offset' => 12,
        'code' => 4,
        'name' => '%r12w',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const R12B = [      //  r8(/r)
        'offset' => 12,
        'code' => 4,
        'name' => '%r12b',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    const R13  = [      // r64(/r)
        'offset' => 13,
        'code' => 5,
        'name' => '%r13',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const R13D = [      // r32(/r)
        'offset' => 13,
        'code' => 5,
        'name' => '%r13d',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const R13W = [      // r16(/r)
        'offset' => 13,
        'code' => 5,
        'name' => '%r13w',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const R13B = [      //  r8(/r)
        'offset' => 13,
        'code' => 5,
        'name' => '%r13b',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    const R14  = [      // r64(/r)
        'offset' => 14,
        'code' => 6,
        'name' => '%r14',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const R14D = [      // r32(/r)
        'offset' => 14,
        'code' => 6,
        'name' => '%r14d',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const R14W = [      // r16(/r)
        'offset' => 14,
        'code' => 6,
        'name' => '%r14w',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const R14B = [      //  r8(/r)
        'offset' => 14,
        'code' => 6,
        'name' => '%r14b',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    const R15  = [      // r64(/r)
        'offset' => 15,
        'code' => 7,
        'name' => '%r15',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const R15D = [      // r32(/r)
        'offset' => 15,
        'code' => 7,
        'name' => '%r15d',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const R15W = [      // r16(/r)
        'offset' => 15,
        'code' => 7,
        'name' => '%r15w',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const R15B = [      //  r8(/r)
        'offset' => 15,
        'code' => 7,
        'name' => '%r15b',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];


    // Special Purpose Registers
    // Stack Pointer
    const RSP = [       // r64(/r)
        'offset' => 4,
        'code' => 4,
        'name' => '%rsp',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const ESP = [       // r32(/r)
        'offset' => 4,
        'code' => 4,
        'name' => '%esp',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const  SP = [       // r16(/r)
        'offset' => 4,
        'code' => 4,
        'name' => '%sp',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const SPL = [       //  r8(/r) with REX prefix
        'offset' => 4,
        'code' => 4,
        'name' => '%spl',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];

    const  AH = [       //  r8(/r) without REX prefix
        'offset' => 4,
        'code' => 4,
        'name' => '%ah',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];

    // Base Pointer (General-purpose in some compiler modes)
    const RBP = [       // r64(/r)
        'offset' => 5,
        'code' => 5,
        'name' => '%rbp',
        'width' => 64,
        'mask' => self::MASK_WIDTH[64],
    ];

    const EBP = [       // r32(/r)
        'offset' => 5,
        'code' => 5,
        'name' => '%ebp',
        'width' => 32,
        'mask' => self::MASK_WIDTH[32],
    ];

    const  BP = [       // r16(/r)
        'offset' => 5,
        'code' => 5,
        'name' => '%bp',
        'width' => 16,
        'mask' => self::MASK_WIDTH[16],
    ];

    const BPL = [       //  r8(/r) with REX prefix
        'offset' => 5,
        'code' => 5,
        'name' => '%bpl',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];

    const  CH = [       //  r8(/r) without REX prefix
        'offset' => 5,
        'code' => 5,
        'name' => '%ch',
        'width' => 8,
        'mask' => self::MASK_WIDTH[8],
    ];

    const ID_MAP = [
        0 => [
            8 => self::AL,   // r8 without REX prefix
            16 => self::AX,
            32 => self::EAX,
            64 => self::RAX,
            80 => self::AL, // r8 with REX prefix
        ],
        1 => [
            8 => self::CL,   // r8 without REX prefix
            16 => self::CX,
            32 => self::ECX,
            64 => self::RCX,
            80 => self::CL, // r8 with REX prefix
        ],
        2 => [
            8 => self::DL,   // r8 without REX prefix
            16 => self::DX,
            32 => self::EDX,
            64 => self::RDX,
            80 => self::DL, // r8 with REX prefix
        ],
        3 => [
            8 => self::BL,   // r8 without REX prefix
            16 => self::BX,
            32 => self::EBX,
            64 => self::RBX,
            80 => self::BL, // r8 with REX prefix
        ],
        4 => [
            8 => self::AH,   // r8 without REX prefix
            16 => self::SP,
            32 => self::ESP,
            64 => self::RSP,
            80 => self::SPL, // r8 with REX prefix
        ],
        5 => [
            8 => self::CH,   // r8 without REX prefix
            16 => self::BP,
            32 => self::EBP,
            64 => self::RBP,
            80 => self::BPL, // r8 with REX prefix
        ],
        6 => [
            8 => self::DH,   // r8 without REX prefix
            16 => self::SI,
            32 => self::ESI,
            64 => self::RSI,
            80 => self::SIL, // r8 with REX prefix
        ],
        7 => [
            8 => self::BH,   // r8 without REX prefix
            16 => self::DI,
            32 => self::EDI,
            64 => self::RDI,
            80 => self::DIL, // r8 with REX prefix
        ],
    ];

    const REX_ID_MAP = [
        0 => [
            8 => self::R8B,
            16 => self::R8W,
            32 => self::R8D,
            64 => self::R8,
        ],
        1 => [
            8 => self::R9B,
            16 => self::R9W,
            32 => self::R9D,
            64 => self::R9,
        ],
        2 => [
            8 => self::R10B,
            16 => self::R10W,
            32 => self::R10D,
            64 => self::R10,
        ],
        3 => [
            8 => self::R11B,
            16 => self::R11W,
            32 => self::R11D,
            64 => self::R11,
        ],
        4 => [
            8 => self::R12B,
            16 => self::R12W,
            32 => self::R12D,
            64 => self::R12,
        ],
        5 => [
            8 => self::R13B,
            16 => self::R13W,
            32 => self::R13D,
            64 => self::R13,
        ],
        6 => [
            8 => self::R14B,
            16 => self::R14W,
            32 => self::R14D,
            64 => self::R14,
        ],
        7 => [
            8 => self::R15B,
            16 => self::R15W,
            32 => self::R15D,
            64 => self::R15,
        ],
    ];

    /**
     * Translates a request for an opcode into a register object.
     *
     * @param int  $id      The opcode for this register.
     * @param int  $size    The width of the register to return.
     * @param bool $rexSet  Is REX set for this operation? Default: false
     * @param bool $rExtend Is this opcode field being extended? Default: false
     *
     * @return RegisterObj An array representing the requested register.
     */
    public static function getByCode(
        int $id,
        int $size,
        bool $rexSet = false,
        bool $rExtend = false,
    ): array {
        if ($rexSet & $rExtend) {
            return self::REX_ID_MAP[$id][$size];
        }

        // If we have a rex value, but REX_R is not set...
        if ($rexSet && ! $rExtend && $size === 8) {
            $size = 80;
        }

        return self::ID_MAP[$id][$size];
    }
}
