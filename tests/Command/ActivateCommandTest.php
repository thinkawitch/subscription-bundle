<?php

namespace Thinkawitch\SubscriptionBundle\Tests\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Thinkawitch\SubscriptionBundle\Command\AbstractCommand;
use Thinkawitch\SubscriptionBundle\Command\ActivateCommand;
use Thinkawitch\SubscriptionBundle\ThinkawitchSubscriptionBundle;

class ActivateCommandTest extends CommandTestCase
{
    public function testExecute()
    {
        $container = $this->getMockContainer();
        $application = new Application();
        $application->add(new ActivateCommand(
            $container->get('entity_manager'),
            $container->get('thinkawitch.subscription.repository.subscription'),
            $container->get('thinkawitch.subscription.manager'),
        ));

        /** @var AbstractCommand $command */
        $command = $application->find(ThinkawitchSubscriptionBundle::COMMAND_NAMESPACE.':activate');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'id'       => 1
        ));

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Subscription activated', $output);
    }
}
