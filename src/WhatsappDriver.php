<?php

namespace Botman\WhatsappDriver;

use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WhatsappDriver extends HttpDriver
{
    const DRIVER_NAME = 'Whatsapp';

    protected $endpoint = 'messages';

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param Request $request
     * @return void
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make((array) $this->payload->get('messages') ? (array) $this->payload->get('messages')[0] : '');
        $this->content = $request->getContent();
        $this->config = Collection::make($this->config->get('whatsapp', []));
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return !is_null($this->payload->get('contacts')) || !is_null($this->event->get('from'));
    }

    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        if (empty($this->messages)) {
            $this->messages = [
                new IncomingMessage(
                    $this->event->get('text')['body'],
                    $this->event->get('from'),
                    $this->event->get('from'),
                    $this->payload
                )
            ];
        }

        return $this->messages;
    }

    /**
     * Retrieve User information.
     * @param IncomingMessage $matchingMessage
     * @return UserInterface
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        $contact = Collection::make($matchingMessage->getPayload()->get('contacts')[0]);
        return new User(
            $contact->get('wa_id'),
            $contact->get('profile')['name'],
            null,
            $contact->get('wa_id'),
            $contact
        );
    }

    /**
     * @param IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * @param string|\BotMan\BotMan\Messages\Outgoing\Question $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return $this
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        return [
            'preview_url' => true,
            'recipient_type' => 'individual',
            'to' => $matchingMessage->getSender(),
            'type' => 'text',
            'text' => [
                'body' => $message->getText()
            ]
        ];
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        if ($this->config->get('throw_http_exceptions')) {
            return $this->postWithExceptionHandling($this->buildApiUrl($this->endpoint), [], $payload, $this->buildAuthHeader(), true);
        }

        return $this->http->post($this->buildApiUrl($this->endpoint), [], $payload, $this->buildAuthHeader(), true);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        // TODO: Check token existence from DB?
        return !empty($this->config->get('url'));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return void
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        $parameters = array_replace_recursive([
            'to' => $matchingMessage->getRecipient(),
        ], $parameters);

        if ($this->config->get('throw_http_exceptions')) {
            return $this->postWithExceptionHandling($this->buildApiUrl($endpoint), [], $parameters, $this->buildAuthHeader());
        }

        return $this->http->post($this->buildApiUrl($endpoint), [], $parameters, $this->buildAuthHeader());
    }

    protected function buildApiUrl($endpoint)
    {
        return "https://graph.facebook.com/v13.0/".$this->config->get('buss_acc_id') . '/' . $endpoint;
    }

    protected function buildAuthHeader()
    {
        // TODO: Token should from DB & Re-Fetch before expired
        // TODO: Should create Artisan command + Scheduler
        $token = $this->config->get('bearer_token');

        return [
            "Authorization: Bearer $token",
            'Content-Type: application/json',
            'Accept: application/json'
        ];
    }

    /**
     * @param $url
     * @param array $urlParameters
     * @param array $postParameters
     * @param array $headers
     * @param bool $asJSON
     * @param int $retryCount
     * @return Response
     * @throws \Modules\ChatBot\Drivers\Whatsapp\WhatsappConnectionException
     */
    private function postWithExceptionHandling(
        $url,
        array $urlParameters = [],
        array $postParameters = [],
        array $headers = [],
        $asJSON = false,
        int $retryCount = 0
    ) {
        $response = $this->http->post($url, $urlParameters, $postParameters, $headers, $asJSON);
        $responseData = json_decode($response->getContent(), true);

        if ($response->isSuccessful()) {
            return $responseData;
        }

        $responseData['errors']['code'] = $responseData['errors']['code'] ?? 'No description from Vendor';
        $responseData['errors']['title'] = $responseData['errors']['title'] ?? 'No error code from Vendor';

        $message = "Status Code: {$response->getStatusCode()}\n".
            "Description: ".print_r($responseData['errors']['title'], true)."\n".
            "Error Code: ".print_r($responseData['errors']['code'], true)."\n".
            "URL: $url\n".
            "URL Parameters: ".print_r($urlParameters, true)."\n".
            "Post Parameters: ".print_r($postParameters, true)."\n".
            "Headers: ". print_r($headers, true)."\n";

        throw new WhatsappConnectionException($message);
    }


      /**
     * @param Request $request
     * @return null|Response
     */
    public function verifyRequest(Request $request)
    { 
        if ($request->get('hub_mode') === 'subscribe' && $request->get('hub_verify_token') === $this->config->get('verification')) {
            return Response::create($request->get('hub_challenge'))->send();
        }
    }
}
