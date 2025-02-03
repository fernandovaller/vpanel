<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class MkcertService
{
    protected ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function generate(string $domain): void
    {
        $certPath = $this->parameterBag->get('mkcertPath');

        $keyFile = $domain . '-key.pem';
        $certFile = $domain . '.pem';

        $process = new Process([
            'sudo',
            'mkcert',
            '-key-file',
            $keyFile,
            '-cert-file',
            $certFile,
            $domain,
        ]);
        $process->setWorkingDirectory($certPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function delete(string $domain): void
    {
        $certPath = $this->parameterBag->get('mkcertPath');

        $keyFile = $domain . '-key.pem';
        $certFile = $domain . '.pem';

        $process = new Process(['sudo', 'rm', '-f', $keyFile, $certFile]);
        $process->setWorkingDirectory($certPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}