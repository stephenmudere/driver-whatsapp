<?php

namespace BotMan\Drivers\Whatsapp\Events;

class MessagingCheckoutUpdates extends WhatsappEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'messaging_checkout_updates';
    }
}
