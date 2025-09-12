<?php

namespace Thinkawitch\SubscriptionBundle\Tests\Mock;

use Symfony\Component\Security\Core\User\UserInterface;

class UserMock implements UserInterface
{
    public function getRoles(): array
    {
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
    }
}
