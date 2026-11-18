<?php
declare(strict_types=1);

namespace App\Console;

use Closure;

/**
 * Reads one secret line from an interactive terminal without echoing it (REBUILD-003-F1).
 *
 * - Not a terminal (piped input, tests): returns null; the caller reads normally, nothing is shown on a screen.
 * - Windows console: PowerShell `Read-Host -AsSecureString` reads the value (typed characters show as `*`)
 *   and writes it to a pipe that only this process reads.
 * - Unix terminal: echo is switched off with `stty` and always switched back on.
 *
 * If hiding is not possible on a terminal, it fails closed with HiddenInputUnavailable instead of echoing.
 */
class HiddenInput
{
    private const POWERSHELL = 'powershell.exe -NoProfile -Command "'
        . '[Console]::OutputEncoding = [Text.Encoding]::UTF8; '
        . '$s = Read-Host -AsSecureString; '
        . '$b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); '
        . 'try { [Console]::Out.Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)) } '
        . 'finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($b) }"';

    private bool $windows;
    private bool $isTerminal;
    private Closure $run;
    private Closure $readLine;

    /**
     * @param bool|null $windows Whether the host is Windows; detected when null.
     * @param bool|null $isTerminal Whether STDIN is an interactive terminal; detected when null.
     * @param callable|null $run Runs a command with the console attached, returns [exit code, stdout].
     * @param callable|null $readLine Reads one line from STDIN.
     */
    public function __construct(
        ?bool $windows = null,
        ?bool $isTerminal = null,
        ?callable $run = null,
        ?callable $readLine = null,
    ) {
        $this->windows = $windows ?? DIRECTORY_SEPARATOR === '\\';
        $this->isTerminal = $isTerminal ?? (function_exists('stream_isatty') && stream_isatty(STDIN));
        $this->run = Closure::fromCallable($run ?? [self::class, 'runAttached']);
        $this->readLine = Closure::fromCallable($readLine ?? fn(): string => (string)fgets(STDIN));
    }

    /**
     * @return bool Whether STDIN is an interactive terminal, so read() will prompt.
     */
    public function isTerminal(): bool
    {
        return $this->isTerminal;
    }

    /**
     * @return string|null The secret, or null when STDIN is not a terminal.
     * @throws \App\Console\HiddenInputUnavailable When the terminal cannot hide the input.
     */
    public function read(): ?string
    {
        if (!$this->isTerminal) {
            return null;
        }

        return $this->windows ? $this->readOnWindows() : $this->readOnUnix();
    }

    /**
     * @return string
     */
    private function readOnWindows(): string
    {
        [$code, $output] = ($this->run)(self::POWERSHELL);
        if ($code !== 0) {
            throw new HiddenInputUnavailable('PowerShell could not read the hidden input.');
        }

        return $output;
    }

    /**
     * @return string
     */
    private function readOnUnix(): string
    {
        [$code] = ($this->run)('stty -echo');
        if ($code !== 0) {
            throw new HiddenInputUnavailable('The terminal echo could not be switched off.');
        }

        try {
            return rtrim(($this->readLine)(), "\r\n");
        } finally {
            ($this->run)('stty echo');
        }
    }

    /**
     * Run a command that shares this process's console for input and errors; stdout is captured.
     *
     * @param string $command Command line.
     * @return array{0: int, 1: string}
     */
    private static function runAttached(string $command): array
    {
        $process = proc_open($command, [0 => STDIN, 1 => ['pipe', 'w'], 2 => STDERR], $pipes);
        if (!is_resource($process)) {
            return [1, ''];
        }
        $output = (string)stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        return [proc_close($process), $output];
    }
}
