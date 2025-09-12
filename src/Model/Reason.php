<?php
namespace Thinkawitch\SubscriptionBundle\Model;

enum Reason: string
{
    case renew = 'renew';
    case expire = 'expire';
    case stopAutoRenew = 'stop_auto_renew';
    case cancel = 'cancel';
}