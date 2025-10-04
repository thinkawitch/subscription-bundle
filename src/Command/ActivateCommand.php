<?php

namespace Thinkawitch\SubscriptionBundle\Command;

use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\ThinkawitchSubscriptionBundle;


class ActivateCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName(ThinkawitchSubscriptionBundle::COMMAND_NAMESPACE.':activate')
            ->setDescription('Activate a subscription that was not-active/disabled');
    }

    protected function action(SubscriptionInterface $subscription): void
    {
        $this->subscriptionManager->activate($subscription, false);
        $this->output->writeln('Subscription activated');
    }
}
