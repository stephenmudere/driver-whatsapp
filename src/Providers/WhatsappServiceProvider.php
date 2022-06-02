<?php

namespace BotMan\Drivers\Whatsapp\Providers;

use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Whatsapp\Commands\AddGreetingText;
use BotMan\Drivers\Whatsapp\Commands\AddPersistentMenu;
use BotMan\Drivers\Whatsapp\Commands\AddStartButtonPayload;
use BotMan\Drivers\Whatsapp\Commands\Nlp;
use BotMan\Drivers\Whatsapp\Commands\WhitelistDomains;
use BotMan\Drivers\Whatsapp\WhatsappAudioDriver;
use BotMan\Drivers\Whatsapp\WhatsappDriver;
use BotMan\Drivers\Whatsapp\WhatsappFileDriver;
use BotMan\Drivers\Whatsapp\WhatsappImageDriver;
use BotMan\Drivers\Whatsapp\WhatsappLocationDriver;
use BotMan\Drivers\Whatsapp\WhatsappVideoDriver;
use BotMan\Studio\Providers\StudioServiceProvider;
use Illuminate\Support\ServiceProvider;

class WhatsappServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
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
                $this->commands([
                    Nlp::class,
                    AddGreetingText::class,
                    AddPersistentMenu::class,
                    AddStartButtonPayload::class,
                    WhitelistDomains::class,
                ]);
            }
        }
    }

    /**
     * Load BotMan drivers.
     */
    protected function loadDrivers()
    {
        DriverManager::loadDriver(WhatsappDriver::class);
        DriverManager::loadDriver(WhatsappAudioDriver::class);
        DriverManager::loadDriver(WhatsappFileDriver::class);
        DriverManager::loadDriver(WhatsappImageDriver::class);
        DriverManager::loadDriver(WhatsappLocationDriver::class);
        DriverManager::loadDriver(WhatsappVideoDriver::class);
    }

    /**
     * @return bool
     */
    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }
}
