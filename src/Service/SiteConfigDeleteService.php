<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class SiteConfigDeleteService
{
    public function delete(Site $site): void
    {
        $virtualHostPath = '/etc/apache2/sites-available/';
        $certPath = '/etc/ssl/mkcert/';

        $virtualHostFileName = sprintf('%s.conf', $site->getDomain());

        $this->disableSite($virtualHostPath, $virtualHostFileName);
        $this->deleteFile($virtualHostPath, $virtualHostFileName);
        $this->deleteCertFile($site, $certPath);
        $this->restartApache();
    }

    private function disableSite(string $filePath, string $fileName): void
    {
        $process = new Process(['sudo', 'a2dissite', $fileName]);
        $process->setWorkingDirectory($filePath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function deleteFile(string $filePath, string $fileName): void
    {
        $process = new Process(['sudo', 'rm', '-f', $fileName]);
        $process->setWorkingDirectory($filePath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function deleteCertFile(Site $site, string $filePath): void
    {
        $keyFile = sprintf('%s%s-key.pem', $filePath, $site->getDomain());
        $certFile = sprintf('%s%s.pem', $filePath, $site->getDomain());

        $process = new Process(['sudo', 'rm', '-f', $keyFile]);
        $process->setWorkingDirectory($filePath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $process = new Process(['sudo', 'rm', '-f', $certFile]);
        $process->setWorkingDirectory($filePath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function restartApache(): void
    {
        $process = new Process(['sudo', 'service', 'apache2', 'restart']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}