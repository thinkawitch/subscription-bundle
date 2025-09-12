<?php

namespace Thinkawitch\SubscriptionBundle\Tests\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Thinkawitch\SubscriptionBundle\Command\AbstractCommand;
use Thinkawitch\SubscriptionBundle\Command\ExpireCommand;
use Thinkawitch\SubscriptionBundle\Model\Reason;
use Thinkawitch\SubscriptionBundle\ThinkawitchSubscriptionBundle;

class ExpireCommandTest extends CommandTestCase
{
    public function testExecute()
    {
        $container = $this->getMockContainer();
        $application = new Application();
        $application->add(new ExpireCommand(
            $container->get('entity_manager'),
            $container->get('thinkawitch.subscription.repository.subscription'),
            $container->get('thinkawitch.subscription.manager'),
        ));

        /** @var AbstractCommand $command */
        $command = $application->find(ThinkawitchSubscriptionBundle::COMMAND_NAMESPACE.':expire');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'id'       => 1,
            'reason'   => Reason::expire->value
        ));

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Subscription set expired', $output);
    }
}
