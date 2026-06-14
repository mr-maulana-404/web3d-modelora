<?php

namespace App\Services\GlbTextureEnhancement;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class PythonRunner
{
    public function assertAvailable(string $workingDirectory, string $logPath): void
    {
        $this->runCommand(
            [$this->binary(), '--version'],
            $workingDirectory,
            $logPath,
            'Python binary was not found or is not executable.'
        );
    }

    public function assertPillowAvailable(string $workingDirectory, string $logPath): void
    {
        $this->runCommand(
            [$this->binary(), '-c', 'from PIL import Image; print("Pillow OK")'],
            $workingDirectory,
            $logPath,
            'Pillow is not available for the configured Python binary.'
        );
    }

    public function runScript(string $scriptPath, string $workingDirectory, string $logPath): array
    {
        return $this->runCommand(
            [$this->binary(), $scriptPath],
            $workingDirectory,
            $logPath,
            'Python enhancement step failed.'
        );
    }

    private function runCommand(array $command, string $workingDirectory, string $logPath, string $failureMessage): array
    {
        File::ensureDirectoryExists(dirname($logPath));

        $commandLine = implode(' ', array_map([$this, 'escapeCommandPart'], $command));

        try {
            $process = new Process($command, $workingDirectory, null, null, $this->timeout());
            $process->run();
        } catch (\Throwable $exception) {
            $fallbackResult = [
                'command' => $commandLine,
                'exit_code' => null,
                'stdout' => '',
                'stderr' => $exception->getMessage(),
            ];

            $this->writeLog($logPath, $fallbackResult);

            throw new \RuntimeException($failureMessage.' '.$exception->getMessage(), 0, $exception);
        }

        $result = [
            'command' => $process->getCommandLine() ?: $commandLine,
            'exit_code' => $process->getExitCode(),
            'stdout' => trim($process->getOutput()),
            'stderr' => trim($process->getErrorOutput()),
        ];

        $this->writeLog($logPath, $result);

        if (! $process->isSuccessful()) {
            throw new \RuntimeException($failureMessage.' '.$this->buildProcessSummary($result));
        }

        return $result;
    }

    private function binary(): string
    {
        return (string) config('glb_texture_enhancement.python_binary');
    }

    private function timeout(): int
    {
        return (int) config('glb_texture_enhancement.process_timeout', 3600);
    }

    private function buildProcessSummary(array $result): string
    {
        $stderr = trim((string) ($result['stderr'] ?? ''));
        $stdout = trim((string) ($result['stdout'] ?? ''));

        if ($stderr !== '') {
            return 'stderr: '.$this->truncate($stderr);
        }

        if ($stdout !== '') {
            return 'stdout: '.$this->truncate($stdout);
        }

        return 'No additional stderr output was captured.';
    }

    private function writeLog(string $logPath, array $result): void
    {
        $log = implode(PHP_EOL, [
            'timestamp: '.now()->toDateTimeString(),
            'command: '.($result['command'] ?? ''),
            'exit_code: '.var_export($result['exit_code'] ?? null, true),
            'stdout:',
            (string) ($result['stdout'] ?? ''),
            'stderr:',
            (string) ($result['stderr'] ?? ''),
            str_repeat('-', 80),
            '',
        ]);

        File::append($logPath, $log);
    }

    private function truncate(string $value, int $limit = 700): string
    {
        return mb_strlen($value) > $limit
            ? mb_substr($value, 0, $limit).'...'
            : $value;
    }

    private function escapeCommandPart(string $part): string
    {
        return str_contains($part, ' ') ? '"'.$part.'"' : $part;
    }
}
