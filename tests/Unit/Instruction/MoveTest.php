<?php

namespace shanept\AssemblySimulatorTests\Unit\Instruction;

use shanept\AssemblySimulator\Register;
use shanept\AssemblySimulator\Simulator;
use shanept\AssemblySimulator\Instruction\Move;

/**
 * @covers shanept\AssemblySimulator\Instruction\Move
 */
class MoveTest extends \PHPUnit\Framework\TestCase
{
    use MockSimulatorTrait;

    /**
     * @return array<int, array{int, int, int, string, string, RegisterObj, int}>
     */
    public static function movLoadsImmediate32bitValueDataProvider(): array
    {
        return [
            [Simulator::LONG_MODE, 0, 0, "\xB8", "\x01\x20\x20\x00", Register::EAX, 0x202001],
            [Simulator::LONG_MODE, 0, 0, "\xB9", "\x01\x00\x20\x00", Register::ECX, 0x200001],
            [Simulator::LONG_MODE, 0, 0, "\xBA", "\x01\x04\x20\x00", Register::EDX, 0x200401],
            [Simulator::LONG_MODE, 0, 0, "\xBB", "\x01\x00\x20\x00", Register::EBX, 0x200001],
            [Simulator::LONG_MODE, 0, 0, "\xBC", "\x01\x03\x20\x00", Register::ESP, 0x200301],
            [Simulator::LONG_MODE, 0, 0, "\xBD", "\x71\x00\x20\x00", Register::EBP, 0x200071],
            [Simulator::LONG_MODE, 0, 0, "\xBE", "\x01\x00\x20\x00", Register::ESI, 0x200001],
            [Simulator::LONG_MODE, 0, 0, "\xBF", "\x01\x00\x20\x10", Register::EDI, 0x10200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xB8", "\x01\x20\x20\x00\x00\x00\x00\x00", Register::RAX, 0x202001],
            [Simulator::LONG_MODE, 0x48, 0, "\xB9", "\x01\x00\x20\x00\x00\x00\x00\x00", Register::RCX, 0x200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xBA", "\x01\x04\x20\x00\x00\x00\x00\x00", Register::RDX, 0x200401],
            [Simulator::LONG_MODE, 0x48, 0, "\xBB", "\x01\x00\x20\x00\x00\x00\x00\x00", Register::RBX, 0x200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xBC", "\x01\x03\x20\x00\x00\x00\x00\x00", Register::RSP, 0x200301],
            [Simulator::LONG_MODE, 0x48, 0, "\xBD", "\x71\x00\x20\x00\x00\x00\x00\x00", Register::RBP, 0x200071],
            [Simulator::LONG_MODE, 0x48, 0, "\xBE", "\x01\x00\x20\x00\x00\x00\x00\x00", Register::RSI, 0x200001],
            [Simulator::LONG_MODE, 0x48, 0, "\xBF", "\x01\x00\x20\x10\x00\x00\x00\x00", Register::RDI, 0x10200001],
        ];
    }

    /**
     * @dataProvider movLoadsImmediate32bitValueDataProvider
     * @small
     *
     * @param RegisterObj $register
     */
    public function testMovBxLoadsImmediate32bitValue(
        int $simulatorMode,
        int $rexValue,
        int $prefixValue,
        string $opcode,
        string $address,
        array $register,
        int $expected,
    ): void {
        $simulator = $this->getMockSimulator($simulatorMode);

        // mov ecx,0x200001
        // 0xB9 0x01 0x00 0x20 0x00
        $simulator->method('getRex')
                  ->willReturn($rexValue);

        $simulator->method('getPrefixes')
                  ->willReturn([$prefixValue]);

        $values = [$address, $opcode];

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) use (&$values) {
                      $value = array_pop($values);

                      if (is_null($value)) {
                          $this->fail('Out of values.');
                      }

                      if ($length !== strlen($value)) {
                          $message = sprintf(
                              'Expected string of length %d, received "%s".',
                              $length,
                              $value,
                          );

                          $this->fail($message);
                      }

                      return $value;
                  });

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with($register, $expected);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperandBx());
    }

    /**
     * @small
     */
    public function testMovBxLoadsImmediate8bitValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov cl,0x01
        // 0xB1 0x01
        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $values = ["\x01", "\xB1"];

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) use (&$values) {
                      $value = array_pop($values);

                      if (is_null($value)) {
                          $this->fail('Out of values.');
                      }

                      if ($length !== strlen($value)) {
                          $message = sprintf(
                              'Expected string of length %d, received "%s".',
                              $length,
                              $value,
                          );

                          $this->fail($message);
                      }

                      return $value;
                  });

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::CL, 1);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperandBx8());
    }

    /**
     * @small
     */
    public function testMov8bLoadsMemoryAddress(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov rdx,QWORD PTR [rip+0x2bbd62]
        // 0x48 0x8B 0x15 0x62 0xBD 0x2B 0x00
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) {
                      $values = [
                          1 => "\x15",
                          4 => "\x62\x8D\x2B\x00",
                      ];

                      return $values[$length];
                  });

        $simulator->method('getInstructionPointer')
                  ->willReturn(3);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::RDX, 0x2B8D69);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand8b());
    }

    /**
     * @small
     */
    public function testMov88OnRegisterValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov cl,dl
        // 0x88 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x40);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::DL)
                  ->willReturn(690);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::CL, 690);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand88());
    }

    /**
     * We aren't looking for much here as we don't actually handle the value.
     * We just want to ensure we move past the SIB value without error and
     * return true.
     *
     * @small
     */
    public function testMov88OnSibValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov [esp+0x10],rsi
        // 0x88 0x74 0x24 0x10
        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $values = [
            "\x24",
            "\x74",
        ];

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) use (&$values) {
                      $value = array_pop($values);

                      if (is_null($value)) {
                          $this->fail('Out of values.');
                      }

                      if ($length !== strlen($value)) {
                          $message = sprintf(
                              'Expected string of length %d, received "%s".',
                              $length,
                              $value,
                          );

                          $this->fail($message);
                      }

                      return $value;
                  });

        $simulator->method('getCodeBuffer')
                  ->with(2, 1)
                  ->willReturn("\x10");

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register) {
                      switch ($register) {
                          case Register::DH:
                              return 690;
                          case Register::RSP:
                              return 0;
                      }

                      $this->fail(sprintf('Unexpected register %s.', $register['name']));
                  });

        $simulator->expects($this->exactly(3))
                  ->method('advanceInstructionPointer');

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand88());
    }

    /**
     * @small
     */
    public function testMov88OnNonSibValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0x41);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xFC")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::DIL)
                  ->willReturn(133);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R12B, 133);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand88());
    }

    /**
     * @small
     */
    public function testMov88OnRexRegisterValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov r9b,r10b
        // 0x45 0x88 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::R10B)
                  ->willReturn(691);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R9B, 691);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand88());
    }

    /**
     * @small
     */
    public function testMov89OnRegisterValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov rcx,rdx
        // 0x48 0x89 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::RDX)
                  ->willReturn(690);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::RCX, 690);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand89());
    }

    /**
     * We aren't looking for much here as we don't actually handle the value.
     * We just want to ensure we move past the SIB value without error and
     * return true.
     *
     * @small
     */
    public function testMov89OnSibValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov [rsp+0x10],rsi
        // 0x48 0x89 0x74 0x24 0x10
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $values = [
            "\x24",
            "\x74",
        ];

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) use (&$values) {
                      $value = array_pop($values);

                      if (is_null($value)) {
                          $this->fail('Out of values.');
                      }

                      if ($length !== strlen($value)) {
                          $message = sprintf(
                              'Expected string of length %d, received "%s".',
                              $length,
                              $value,
                          );

                          $this->fail($message);
                      }

                      return $value;
                  });

        $simulator->method('getCodeBuffer')
                  ->with(2, 1)
                  ->willReturn("\x10");

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register) {
                      switch ($register) {
                          case Register::RSI:
                              return 690;
                          case Register::RSP:
                              return 0;
                      }

                      $this->fail(sprintf('Unexpected register %s.', $register['name']));
                  });

        $simulator->expects($this->exactly(3))
                  ->method('advanceInstructionPointer');

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand89());
    }

    /**
     * @small
     */
    public function testMov89OnNonSibValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0x49);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xFC")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::RDI)
                  ->willReturn(133);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R12, 133);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand89());
    }

    /**
     * @small
     */
    public function testMov89OnRexRegisterValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov r9,r10
        // 0x4D 0x89 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x4D);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::R10)
                  ->willReturn(691);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R9, 691);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand89());
    }

    /**
     * @small
     */
    public function testMov8aOnRegisterValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov dl,cl
        // 0x8A 0xD1
        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::CL)
                  ->willReturn(693);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::DL, 693);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand8a());
    }

    /**
     * @small
     */
    public function testMov8aOnRexRegisterValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov r10b,r9b
        // 0x45 0x88 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x45);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::R9B)
                  ->willReturn(694);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R10B, 694);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand8a());
    }

    /**
     * @small
     */
    public function testMov8bOnRegisterValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov rdx,rcx
        // 0x48 0x8B 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x48);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::RCX)
                  ->willReturn(693);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::RDX, 693);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand8b());
    }

    /**
     * @small
     */
    public function testMov8bOnRexRegisterValue(): void
    {
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        // mov r10,r9
        // 0x4D 0x8B 0xD1
        $simulator->method('getRex')
                  ->willReturn(0x4D);

        $simulator->method('getPrefixes')
                  ->willReturn([]);

        $simulator->method('getCodeAtInstruction')
                  ->willReturn("\xD1")
                  ->with(1);

        $simulator->method('readRegister')
                  ->with(Register::R9)
                  ->willReturn(694);

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::R10, 694);

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperand8b());
    }

    /**
     * @small
     */
    public function testMovC6OnAddress(): void
    {
        // 0xC6 0x05 0x84 0x57 0x32 0x00 0x04
        // mov [rip+0x325784] 0x04
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getInstructionPointer')
                  ->willReturn(0);

        $values = [
            "\x01",
            "\x05",
        ];

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) use (&$values) {
                      switch ($length) {
                          case 4:
                              return "\x84\x57\x32\x00";
                          case 1:
                              return array_pop($values);
                      }

                      $this->fail('Unknown length ' . $length);
                  });

        $iPointer = 0;

        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $mov = new Move();
        $mov->setSimulator($simulator);

        $this->assertTrue($mov->executeOperandC6());

        $this->assertEquals(7, $iPointer);
    }

    /**
     * @small
     */
    public function testMovC6OnRegister(): void
    {
        // 0xC6 0xC0 0x04
        // mov al 0x40
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getInstructionPointer')
                  ->willReturn(0);

        $values = [
            "\x40",
            "\xC0",
        ];

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) use (&$values) {
                      switch ($length) {
                          case 1:
                              return array_pop($values);
                      }

                      $this->fail('Unknown length ' . $length);
                  });

        $iPointer = 0;

        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::AL, 0x40);

        $mov = new Move();
        $mov->setSimulator($simulator);

        $this->assertTrue($mov->executeOperandC6());

        $this->assertEquals(3, $iPointer);
    }

    /**
     * @small
     */
    public function testMovC7OnAddress(): void
    {
        // 0xC7 0x05 0x84 0x57 0x32 0x00 0x01 0x02 0x03 0x04
        // mov [rip+0x325784] 0x4030201
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getInstructionPointer')
                  ->willReturn(0);

        $values = [
            "\x01\x02\x03\x04",
            "\x84\x57\x32\x00",
        ];

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) use (&$values) {
                      switch ($length) {
                          case 4:
                              return array_pop($values);
                          case 1:
                              return "\x05";
                      }

                      $this->fail('Unknown length ' . $length);
                  });

        $iPointer = 0;

        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $mov = new Move();
        $mov->setSimulator($simulator);

        $this->assertTrue($mov->executeOperandC7());

        $this->assertEquals(10, $iPointer);
    }

    /**
     * @small
     */
    public function testMovC7OnRegister(): void
    {
        // 0xC7 0xC0 0x01 0x02 0x03 0x04
        // mov eax 0x4030201
        $simulator = $this->getMockSimulator(Simulator::LONG_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturn(false);

        $simulator->method('getInstructionPointer')
                  ->willReturn(0);

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) {
                      switch ($length) {
                          case 4:
                              return "\x01\x02\x03\x04";
                          case 1:
                              return "\xC0";
                      }

                      $this->fail('Unknown length ' . $length);
                  });

        $iPointer = 0;

        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $simulator->expects($this->once())
                  ->method('writeRegister')
                  ->with(Register::EAX, 0x4030201);

        $mov = new Move();
        $mov->setSimulator($simulator);

        $this->assertTrue($mov->executeOperandC7());

        $this->assertEquals(6, $iPointer);
    }

    /**
     * @small
     */
    public function testMovC7WithOp66InProtectedMode(): void
    {
        // mov [esi+0x25], 0x0
        // 0x66 0xc7 0x46 0x25 0x00 0x00
        $simulator = $this->getMockSimulator(Simulator::PROTECTED_MODE);

        $simulator->method('getRex')
                  ->willReturn(0);

        $simulator->method('hasPrefix')
                  ->willReturnCallback(function ($prefix) {
                      return 0x66 === $prefix;
                  });

        $simulator->method('readRegister')
                  ->willReturnCallback(function ($register) {
                      if ($register === Register::SI) {
                          return 63;
                      }

                      $this->fail('Unexpected register ' . $register['name']);
                  });

        $simulator->method('getCodeAtInstruction')
                  ->willReturnCallback(function ($length) {
                      if (1 === $length) {
                          return "\x46";
                      } elseif (2 === $length) {
                          return "\x00\x00";
                      }

                      $this->fail('Unknown length ' . $length);
                  });

        $simulator->method('getCodeBuffer')
                  ->willReturn("\x25");

        $iPointer = 1;

        $simulator->method('getInstructionPointer')
                  ->willReturn(1);

        $simulator->method('advanceInstructionPointer')
                  ->willReturnCallback(function ($amount) use (&$iPointer) {
                      $iPointer += $amount;
                  });

        $move = new Move();
        $move->setSimulator($simulator);

        $this->assertTrue($move->executeOperandC7());
        $this->assertEquals(6, $iPointer);
    }
}
