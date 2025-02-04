<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class MkcertService
{
    protected ParameterBagInterface $parameterBag;

    protected string $mkcertPath;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
        $this->mkcertPath = $this->parameterBag->get('mkcertPath');
    }

    public function generate(string $domain): void
    {
        $keyFile = $this->getKeyFileName($domain);
        $certFile = $this->getCertFileName($domain);

        $process = new Process([
            'sudo',
            'mkcert',
            '-key-file',
            $keyFile,
            '-cert-file',
            $certFile,
            $domain,
        ]);
        $process->setWorkingDirectory($this->mkcertPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function delete(string $domain): void
    {
        $keyFile = $this->getKeyFileName($domain);
        $certFile = $this->getCertFileName($domain);

        $process = new Process(['sudo', 'rm', '-f', $keyFile, $certFile]);
        $process->setWorkingDirectory($this->mkcertPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function getKeyFileName(string $domain): string
    {
        return $domain . '-key.pem';
    }

    private function getCertFileName(string $domain): string
    {
        return $domain . '.pem';
    }
}