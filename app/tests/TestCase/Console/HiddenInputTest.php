<?php
declare(strict_types=1);

namespace App\Test\TestCase\Console;

use App\Console\HiddenInput;
use App\Console\HiddenInputUnavailable;
use Cake\TestSuite\TestCase;
use RuntimeException;

class HiddenInputTest extends TestCase
{
    public function testReturnsNullWhenStdinIsNotATerminal(): void
    {
        $calls = [];
        $input = new HiddenInput(
            windows: true,
            isTerminal: false,
            run: function (string $command) use (&$calls): array {
                $calls[] = $command;

                return [0, 'never'];
            },
        );

        $this->assertNull($input->read());
        $this->assertSame([], $calls);
    }

    public function testOnWindowsReadsThroughPowerShellSecureString(): void
    {
        $commands = [];
        $input = new HiddenInput(
            windows: true,
            isTerminal: true,
            run: function (string $command) use (&$commands): array {
                $commands[] = $command;

                return [0, 'S3cret pass ñ '];
            },
        );

        $this->assertSame('S3cret pass ñ ', $input->read());
        $this->assertCount(1, $commands);
        $this->assertStringContainsString('powershell', strtolower($commands[0]));
        $this->assertStringContainsString('Read-Host -AsSecureString', $commands[0]);
    }

    public function testOnWindowsFailsClosedWhenPowerShellFails(): void
    {
        $input = new HiddenInput(
            windows: true,
            isTerminal: true,
            run: fn(string $command): array => [1, ''],
        );

        $this->expectException(HiddenInputUnavailable::class);
        $input->read();
    }

    public function testOnUnixDisablesAndRestoresEcho(): void
    {
        $commands = [];
        $input = new HiddenInput(
            windows: false,
            isTerminal: true,
            run: function (string $command) use (&$commands): array {
                $commands[] = $command;

                return [0, ''];
            },
            readLine: fn(): string => "secret value\n",
        );

        $this->assertSame('secret value', $input->read());
        $this->assertSame(['stty -echo', 'stty echo'], $commands);
    }

    public function testOnUnixRestoresEchoEvenWhenReadingFails(): void
    {
        $commands = [];
        $input = new HiddenInput(
            windows: false,
            isTerminal: true,
            run: function (string $command) use (&$commands): array {
                $commands[] = $command;

                return [0, ''];
            },
            readLine: function (): string {
                throw new RuntimeException('read failed');
            },
        );

        try {
            $input->read();
            $this->fail('Expected the read failure to propagate.');
        } catch (RuntimeException) {
        }
        $this->assertSame(['stty -echo', 'stty echo'], $commands);
    }

    public function testOnUnixFailsClosedWhenEchoCannotBeDisabled(): void
    {
        $input = new HiddenInput(
            windows: false,
            isTerminal: true,
            run: fn(string $command): array => [1, ''],
            readLine: fn(): string => "never\n",
        );

        $this->expectException(HiddenInputUnavailable::class);
        $input->read();
    }
}
