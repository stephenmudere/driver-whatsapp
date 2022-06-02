<?php

namespace BotMan\Drivers\Whatsapp\Events;

class MessagingDeliveries extends WhatsappEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'messaging_deliveries';
    }
}
