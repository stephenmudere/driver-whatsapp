<?php

namespace BotMan\Drivers\Whatsapp\Commands;

use BotMan\BotMan\Http\Curl;
use Illuminate\Console\Command;

class AddStartButtonPayload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'botman:whatsapp:AddStartButton';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a Whatsapp Get Started button with a payload';

    /**
     * @var Curl
     */
    private $http;

    /**
     * Create a new command instance.
     *
     * @param Curl $http
     */
    public function __construct(Curl $http)
    {
        parent::__construct();
        $this->http = $http;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $payload = config('botman.whatsapp.start_button_payload');

        if (! $payload) {
            $this->error('You need to add a Whatsapp payload data to your BotMan Whatsapp config in whatsapp.php.');
            exit;
        }

        $response = $this->http->post(
            'https://graph.facebook.com/v3.0/me/messenger_profile?access_token='.config('botman.whatsapp.token'),
            [],
            [
                'get_started' => [
                    'payload' => $payload,
                ],
            ]
        );

        $responseObject = json_decode($response->getContent());

        if ($response->getStatusCode() == 200) {
            $this->info('Get Started payload was set to: '.$payload);
        } else {
            $this->error('Something went wrong: '.$responseObject->error->message);
        }
    }
}
