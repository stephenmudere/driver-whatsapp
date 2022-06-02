<?php

namespace Botman\WhatsappDriver;

use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Whatsapp\WhatsappDriver;
use BotMan\Studio\Providers\StudioServiceProvider;
use Illuminate\Support\ServiceProvider;


class WhatsappDriverServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
       if (! $this->isRunningInBotManStudio()) {
            $this->loadDrivers();

            $this->publishes([
                __DIR__.'/../../stubs/whatsapp.php' => config_path('botman/whatsapp.php'),
            ]);

            $this->mergeConfigFrom(__DIR__.'/../../stubs/whatsapp.php', 'botman.whatsapp');

            if ($this->app->runningInConsole()) {
            }
        }
    }

    /**
     * Load BotMan drivers.
     */
    protected function loadDrivers()
    {
        DriverManager::loadDriver(WhatsappDriver::class);
    }

    /**
     * @return bool
     */
    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }
}
