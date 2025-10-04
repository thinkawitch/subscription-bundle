<?php

namespace Thinkawitch\SubscriptionBundle\Command;

use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\ThinkawitchSubscriptionBundle;

class ExpireCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName(ThinkawitchSubscriptionBundle::COMMAND_NAMESPACE.':expire')
            ->setDescription('Expire subscription');
    }

    protected function action(SubscriptionInterface $subscription): void
    {
        $this->subscriptionManager->expire($subscription);
        $this->output->writeln('Subscription set expired');
    }
}
