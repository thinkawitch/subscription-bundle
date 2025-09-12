<?php

namespace Thinkawitch\SubscriptionBundle\Event;

final class SubscriptionEvents
{
    /**
     * Activate subscription.
     *
     * Triggered when a subscription is activated.
     */
    const ACTIVATE_SUBSCRIPTION = 'thinkawitch.subscription.activate';

    /**
     * Renew a subscription.
     *
     * Triggered when subscription is renewed.
     */
    const RENEW_SUBSCRIPTION = 'thinkawitch.subscription.renew';

    /**
     * Expire subscription.
     *
     * Triggered when subscription is expired.
     */
    const EXPIRE_SUBSCRIPTION = 'thinkawitch.subscription.expire';


    /**
     * Stop auto-renew.
     *
     * Triggered when subscription should no longer be auto re-newed.
     */
    const STOP_AUTO_RENEW_SUBSCRIPTION = 'thinkawitch.subscription.stop_auto_renew';

    /**
     * Cancel subscription.
     *
     * Triggered when subscription is canceled, deactivated.
     */
    const CANCEL_SUBSCRIPTION = 'thinkawitch.subscription.cancel';
}