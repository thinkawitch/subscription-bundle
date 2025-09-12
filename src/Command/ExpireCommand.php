<?php

namespace Thinkawitch\SubscriptionBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Thinkawitch\SubscriptionBundle\Model\Reason;
use Thinkawitch\SubscriptionBundle\Model\SubscriptionInterface;
use Thinkawitch\SubscriptionBundle\ThinkawitchSubscriptionBundle;

class ExpireCommand extends AbstractCommand
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName(ThinkawitchSubscriptionBundle::COMMAND_NAMESPACE.':expire')
            ->setDescription('Expire a subscription')
            ->addArgument(
                'reason',
                InputArgument::OPTIONAL,
                'Reason of expiration, key of config thinkawitch_subscription.reasons',
                Reason::expire->value
            );
    }

    protected function action(SubscriptionInterface $subscription): void
    {
        $reason = $this->input->getArgument('reason');
        $this->subscriptionManager->expire($subscription, Reason::from($reason));
        $this->output->writeln('Subscription set expired');
    }
}
