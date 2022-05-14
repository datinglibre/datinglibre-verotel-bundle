<?php

declare(strict_types=1);

namespace DatingLibre\VerotelBundle\Command;

use DatingLibre\VerotelBundle\Service\FlexpayClientService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CancelSubscription extends Command
{
    private const SALE_ID = 'saleId';
    protected static $defaultName = 'app:verotel:cancel';
    private FlexpayClientService $flexpayClientService;

    public function __construct(FlexpayClientService $flexpayClientService)
    {
        parent::__construct();
        $this->flexpayClientService = $flexpayClientService;
    }

    protected function configure(): void
    {
        $this->setDescription('Get verotel subscription cancellation URL');

        $this->addArgument(self::SALE_ID, InputArgument::REQUIRED, self::SALE_ID);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->flexpayClientService->getCancellationUrl($input->getArgument(self::SALE_ID)));

        return 0;
    }
}
