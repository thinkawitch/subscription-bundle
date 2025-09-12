<?php

namespace Thinkawitch\SubscriptionBundle\Command;

use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\ThinkawitchSubscriptionBundle;

class CancelCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName(ThinkawitchSubscriptionBundle::COMMAND_NAMESPACE.':cancel')
            ->setDescription('Cancel subscription');
    }

    protected function action(SubscriptionInterface $subscription): void
    {
        $this->subscriptionManager->cancel($subscription);
        $this->output->writeln('Subscription cancelled');
    }
}
