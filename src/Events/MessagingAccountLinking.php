<?php

namespace BotMan\Drivers\Whatsapp\Events;

class MessagingAccountLinking extends WhatsappEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'messaging_account_linking';
    }
}
