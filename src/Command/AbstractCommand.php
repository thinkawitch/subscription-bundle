<?php

namespace Thinkawitch\SubscriptionBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\Repository\SubscriptionRepositoryInterface;
use Thinkawitch\SubscriptionBundle\Subscription\SubscriptionManager;

abstract class AbstractCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected bool $saveChanges = false;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected SubscriptionManager $subscriptionManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'Subscription ID'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // save the input interfaces
        $this->input  = $input;
        $this->output = $output;

        $subscriptionId = $input->getArgument('id');
        $subscription   = $this->subscriptionRepository->findSubscriptionById($subscriptionId);

        if ($subscription === null) {
            $output->writeln(sprintf('<error>The subscription with ID "%s" was not found.</error>', $subscriptionId));
            return Command::INVALID;
        }

        // execute the action
        $this->action($subscription);

        // save, manager does not save to db, seems this should be made in app, added for test
        if ($this->saveChanges) $this->entityManager->flush();

        $output->writeln('<info>Finished.</info>');
        return Command::SUCCESS;
    }

    abstract protected function action(SubscriptionInterface $subscription): void;

}
