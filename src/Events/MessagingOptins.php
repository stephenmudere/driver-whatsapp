<?php

namespace BotMan\Drivers\Whatsapp\Events;

class MessagingOptins extends WhatsappEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'messaging_optins';
    }
}
