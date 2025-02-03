<?php

namespace App\Command;

use App\Entity\Site;
use App\Service\ApacheVirtualHostFileService;
use App\Service\MkcertService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TesteCommand extends Command
{
    protected static $defaultName = 'app:teste';

    protected static $defaultDescription = 'Add a short description for your command';

    protected MkcertService $mkcertService;

    protected ApacheVirtualHostFileService $apacheVirtualHostFileService;

    public function __construct(MkcertService $mkcertService, ApacheVirtualHostFileService $apacheVirtualHostFileService)
    {
        parent::__construct($name = null);
        $this->mkcertService = $mkcertService;
        $this->apacheVirtualHostFileService = $apacheVirtualHostFileService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        $this->mkcertService->generate('testevaller.local');

        $site = (new Site())
            ->setDomain('testevaller.local')
            ->setDocumentRoot('/var/www/projetos/vpanel/public')
            ->setPhpVersion('7.4');
        
        $this->apacheVirtualHostFileService->create($site);

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
