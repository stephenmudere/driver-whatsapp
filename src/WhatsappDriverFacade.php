<?php

namespace Botman\WhatsappDriver;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Botman\WhatsappDriver\Skeleton\SkeletonClass
 */
class WhatsappDriverFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'driver-whatsapp';
    }
}
