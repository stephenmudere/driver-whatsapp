<?php

namespace BotMan\Drivers\Whatsapp\Events;

class MessagingReferrals extends WhatsappEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'messaging_referrals';
    }
}
