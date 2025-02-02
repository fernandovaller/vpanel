<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Site;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class SiteConfigService
{
    public function create(Site $site): void
    {
        $virtualHostPath = '/etc/apache2/sites-available/';
        $certPath = '/etc/ssl/mkcert/';

        $virtualHostFileName = sprintf('%s.conf', $site->getDomain());

        $virtualHostFileContent = $this->getVirtualHostConfig($site, $certPath);

        $this->createFile($virtualHostPath, $virtualHostFileName);
        $this->createCertFile($site, $certPath);
        $this->appendFileContent($virtualHostPath, $virtualHostFileName, $virtualHostFileContent);
        $this->enableFile($virtualHostPath, $virtualHostFileName);
        $this->restartApache();
    }

    private function createFile(string $filePath, string $fileName): void
    {
        $process = new Process(['sudo', 'touch', $fileName]);
        $process->setWorkingDirectory($filePath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function appendFileContent(string $filePath, string $fileName, string $content): void
    {
        $content = escapeshellarg($content);
        $file = escapeshellarg($fileName);

        $process = new Process(["sudo", "bash", "-c", "echo " . $content . " > " . $file]);
        $process->setWorkingDirectory($filePath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function enableFile(string $filePath, string $fileName): void
    {
        $process = new Process(['sudo', 'a2ensite', $fileName]);
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

    private function createCertFile(Site $site, string $filePath): void
    {
        $keyFile = sprintf('%s%s-key.pem', $filePath, $site->getDomain());
        $certFile = sprintf('%s%s.pem', $filePath, $site->getDomain());

        $process = new Process([
            'sudo',
            'mkcert',
            '-key-file',
            $keyFile,
            '-cert-file',
            $certFile,
            $site->getDomain(),
            '*.' . $site->getDomain(),
        ]);
        //$process = new Process(["sudo", "bash", "-c", "mkcert " . $site->getDomain() . " " . $site->setDomain()]);
        $process->setWorkingDirectory($filePath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function getVirtualHostConfig(Site $site, string $certPath): string
    {
        return <<<CONF
<VirtualHost *:80>
    ServerName {$site->getDomain()}
    DocumentRoot {$site->getDocumentRoot()}

    <Directory {$site->getDocumentRoot()}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        # 2.4.10+ pode fazer proxy para socket unix
        SetHandler "proxy:unix:/var/run/php/php{$site->getPhpVersion()}-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName {$site->getDomain()}
    DocumentRoot {$site->getDocumentRoot()}

    <Directory {$site->getDocumentRoot()}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        # 2.4.10+ pode fazer proxy para socket unix
        SetHandler "proxy:unix:/var/run/php/php{$site->getPhpVersion()}-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined

    SSLEngine on
    SSLCertificateFile {$certPath}{$site->getDomain()}.pem
    SSLCertificateKeyFile {$certPath}{$site->getDomain()}-key.pem

    <FilesMatch "\.(cgi|shtml|phtml|php)$">
        SSLOptions +StdEnvVars
    </FilesMatch>
</VirtualHost>
CONF;
    }
}