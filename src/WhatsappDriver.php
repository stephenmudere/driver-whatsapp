<?php

namespace BotMan\Drivers\Whatsapp;

use BotMan\BotMan\Drivers\Events\GenericEvent;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\DriverEventInterface;
use BotMan\BotMan\Interfaces\VerifiesService;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Whatsapp\Events\MessagingDeliveries;
use BotMan\Drivers\Whatsapp\Events\MessagingOptins;
use BotMan\Drivers\Whatsapp\Events\MessagingReads;
use BotMan\Drivers\Whatsapp\Events\MessagingReferrals;
use BotMan\Drivers\Whatsapp\Exceptions\WhatsappException;
use BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineBoardingPass;
use BotMan\Drivers\Whatsapp\Extensions\AirlineCheckInTemplate;
use BotMan\Drivers\Whatsapp\Extensions\AirlineItineraryTemplate;
use BotMan\Drivers\Whatsapp\Extensions\AirlineUpdateTemplate;
use BotMan\Drivers\Whatsapp\Extensions\ButtonTemplate;
use BotMan\Drivers\Whatsapp\Extensions\GenericTemplate;
use BotMan\Drivers\Whatsapp\Extensions\ListTemplate;
use BotMan\Drivers\Whatsapp\Extensions\MediaTemplate;
use BotMan\Drivers\Whatsapp\Extensions\OpenGraphTemplate;
use BotMan\Drivers\Whatsapp\Extensions\ReceiptTemplate;
use BotMan\Drivers\Whatsapp\Extensions\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Log;
use BotMan\Drivers\Whatsapp\Messages\Template;


class WhatsappDriver extends HttpDriver implements VerifiesService
{
    const HANDOVER_INBOX_PAGE_ID = '263902037430900';

    const TYPE_RESPONSE = 'whatsapp';
    const TYPE_UPDATE = 'UPDATE';
    const TYPE_MESSAGE_TAG = 'MESSAGE_TAG';

    /** @var string */
    protected $signature;

    /** @var string */
    protected $content;

    /** @var array */
    protected $messages = [];

    protected $endpoint = 'messages';

    /** @var array */
    protected $templates = [
        AirlineBoardingPass::class,
        AirlineCheckInTemplate::class,
        AirlineItineraryTemplate::class,
        AirlineUpdateTemplate::class,
        ButtonTemplate::class,
        GenericTemplate::class,
        ListTemplate::class,
        ReceiptTemplate::class,
        MediaTemplate::class,
        OpenGraphTemplate::class,
    ];

    private $supportedAttachments = [
        Video::class,
        Audio::class,
        Image::class,
        File::class,
    ];

    /** @var DriverEventInterface */
    protected $driverEvent;

    protected $whatsappProfileEndpoint = 'https://graph.whatsapp.com/v3.0/';

    /** @var bool If the incoming request is a FB postback */
    protected $isPostback = false;

    const DRIVER_NAME = 'Whatsapp';

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        Log::channel('tracker')->info( __CLASS__."  method  ".__METHOD__ );
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make((array) $this->payload->get('entry', [null])[0]);
        $this->signature = $request->headers->get('X_HUB_SIGNATURE', '');
        $this->content = $request->getContent();
        $this->config = Collection::make($this->config->get('whatsapp', []));

        Log::channel('tracker')->info("content ".print_r($this->content,1));
        Log::channel('tracker')->info("payload ".print_r($this->payload,1));
        Log::channel('tracker')->info("event ".print_r($this->event,1));
        //Log::channel('tracker')->info("config ".print_r($this->config,1));
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        Log::channel('tracker')->info( __CLASS__."  method  ".__METHOD__ );
        $validSignature = empty($this->config->get('app_secret')) || $this->validateSignature();
         Log::channel('tracker')->info( __CLASS__."  method  ".__METHOD__ . " result ".(!is_null($this->payload->get('object'))&&'whatsapp_business_account'==$this->payload->get('object'))); 
        return (!is_null($this->payload->get('object'))&&'whatsapp_business_account'==$this->payload->get('object'));
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

    /**
     * @return bool|DriverEventInterface
     */
    public function hasMatchingEvent()
    {
        $event = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            return Collection::make($msg)->except([
                'sender',
                'recipient',
                'timestamp',
                'message',
                'postback',
            ])->isEmpty() === false;
        })->transform(function ($msg) {
            return Collection::make($msg)->toArray();
        })->first();

        if (! is_null($event)) {
            $this->driverEvent = $this->getEventFromEventData($event);

            return $this->driverEvent;
        }

        return false;
    }

    /**
     * @param array $eventData
     * @return DriverEventInterface
     */
    protected function getEventFromEventData(array $eventData)
    {
        $name = Collection::make($eventData)->except([
            'sender',
            'recipient',
            'timestamp',
            'message',
            'postback',
        ])->keys()->first();
        switch ($name) {
            case 'referral':
                return new MessagingReferrals($eventData);
                break;
            case 'optin':
                return new MessagingOptins($eventData);
                break;
            case 'delivery':
                return new MessagingDeliveries($eventData);
                break;
            case 'read':
                return new MessagingReads($eventData);
                break;
            case 'account_linking':
                return new Events\MessagingAccountLinking($eventData);
                break;
            case 'checkout_update':
                return new Events\MessagingCheckoutUpdates($eventData);
                break;
            default:
                $event = new GenericEvent($eventData);
                $event->setName($name);

                return $event;
                break;
        }
    }

    /**
     * @return bool
     */
    protected function validateSignature()
    {
        return hash_equals($this->signature,
            'sha1='.hash_hmac('sha1', $this->content, $this->config->get('app_secret')));
    }

    /**
     * @param IncomingMessage $matchingMessage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function markSeen(IncomingMessage $matchingMessage)
    {
        $parameters = [
            'recipient' => [
                'id' => $matchingMessage->getSender(),
            ],
            //'access_token' => $this->config->get('token'),
            'sender_action' => 'mark_seen',
        ];

        return $this->http->post($this->whatsappProfileEndpoint.'me/messages', [], $parameters);
    }

    /**
     * @param IncomingMessage $matchingMessage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function types(IncomingMessage $matchingMessage)
    {
        $parameters = [
            'recipient' => [
                'id' => $matchingMessage->getSender(),
            ],
            //'access_token' => $this->config->get('token'),
            'sender_action' => 'typing_on',
        ];

        return $this->http->post($this->whatsappProfileEndpoint.'me/messages', [], $parameters);
    }

    /**
     * @param  IncomingMessage $message
     * @return Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        $payload = $message->getPayload();
        if (isset($payload['message']['quick_reply'])) {
            return Answer::create($payload['message']['text'])->setMessage($message)->setInteractiveReply(true)->setValue($payload['message']['quick_reply']['payload']);
        } elseif (isset($payload['postback']['payload'])) {
            return Answer::create($payload['postback']['title'])->setMessage($message)->setInteractiveReply(true)->setValue($payload['postback']['payload']);
        }

        return Answer::create($message->getText())->setMessage($message);
    }

   /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        Log::channel('tracker')->info( __CLASS__."  method  ".__METHOD__ );
        //dd($this->event->get('changes'));
        $this->loadMessages();
        // $changes=$this->event->get('changes');
        // if (empty($this->messages)) {
        //     if (isset($changes[0]['value']['body']['messages'][0]['type'])&&"text"==$changes[0]['value']['body']['messages'][0]['type']) {
           
        //     $this->messages = [
        //         new IncomingMessage(
        //             $changes[0]['value']['body']['messages'][0]['text']['body'],
        //             $changes[0]['value']['body']['messages'][0]['from'],
        //             $changes[0]['value']['body']['messages'][0]['from'],
        //             $this->payload
        //         )
        //     ];

        //   }
        // }

       // dd($this->messages);

        return $this->messages;
    }


    /**
     * Load Facebook messages.
     */
    protected function loadMessages()
    {
        $col= Collection::make($this->event->get('changes'));
        $messages =[];
        foreach ($col as $mkey => $mssg) {
            //dd($msg["value"]["messages"]);
            if (isset($mssg["value"]["messages"])) {
                foreach ($mssg["value"]["messages"] as $mvkey => $msg) {
                    //dd($msg);
                    if (isset($msg['text']['body']) && isset($msg['type'])&&"text"==$msg['type']) {
                   $message = new IncomingMessage('', $this->getMessageSender($msg), $this->getMessageRecipient($msg), $mssg["value"]);
                   //dd($msg);
                    
                        $message->setText($msg['text']['body']);
                        $messages[]= $message;
                        // if (isset($msg['message']['nlp'])) {
                        //     $message->addExtras('nlp', $msg['message']['nlp']);
                        // }
                    }
                    // elseif (isset($msg['postback']['payload'])) {
                    //     $this->isPostback = true;

                    //     $message->setText($msg['postback']['payload']);
                    // } elseif (isset($msg['message']['quick_reply']['payload'])) {
                    //     $this->isPostback = true;

                    //     $message->setText($msg['message']['quick_reply']['payload']);
                    // }

                }
            }
            
            
        }

        if (count($messages) === 0) {
            $messages = [new IncomingMessage('', '', '')];
        }
        
        $this->messages = $messages;
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        // Whatsapp bot replies don't get returned
        return false;
    }

    /**
     * Tells if the current request is a callback.
     *
     * @return bool
     */
    public function isPostback()
    {
        return $this->isPostback;
    }

    /**
     * Convert a Question object into a valid Whatsapp
     * quick reply response object.
     *
     * @param Question $question
     * @return array
     */
    private function convertQuestion(Question $question)
    {
        $questionData = $question->toArray();

        $replies = Collection::make($question->getButtons())
            ->map(function ($button) {
                if (isset($button['content_type']) && $button['content_type'] !== 'text') {
                    return ['content_type' => $button['content_type']];
                }

                return array_merge([
                    'type' => 'reply',
                    'reply'=>['title' => $button['text'] ?? $button['title'],
                    'id' => $button['value'] ?? $button['payload']]
                ], $button['additional'] ?? []);
            });
        if (count($replies->toArray())>0) {
             return [
                "type"=> "button",
                'body'=>['text' => $questionData['text']],
                "action"=>['buttons' => $replies->toArray()],
            ];
        }else{
             return [
                "type"=> "text",
                'body'=>['text' => $questionData['text']],
            ];
        }
       
    }

     /**
     * Convert a Question object into a valid Whatsapp
     * quick reply response object.
     *
     * @param Question $question
     * @return array
     */
    private function convertTemplate(Template $question)
    {
        $questionData = $question->toArray();
        //dd($questionData);
        $replies = Collection::make($question->getButtons())
            ->map(function ($button) {
                if (isset($button['content_type']) && $button['content_type'] !== 'text') {
                    return ['content_type' => $button['content_type']];
                }

                return array_merge([
                    'content_type' => 'text',
                    'title' => $button['text'] ?? $button['title'],
                    'payload' => $button['value'] ?? $button['payload'],
                    'image_url' => $button['image_url'] ?? $button['image_url'],
                ], $button['additional'] ?? []);
            });

        return [
            'template' => $questionData['template'],
            'quick_replies' => $replies->toArray(),
            'components'=>$questionData['components']
        ];
    }


    /**
     * @param string|Question|IncomingMessage $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        if ($this->driverEvent) {
            $payload = $this->driverEvent->getPayload();
            if (isset($payload['optin']) && isset($payload['optin']['user_ref'])) {
                $recipient = ['user_ref' => $payload['optin']['user_ref']];
            } else {
                $recipient = ['id' => $payload['sender']['id']];
            }
        } else {
            $recipient = ['id' => $matchingMessage->getSender()];
        }
        //{ \"messaging_product\": \"whatsapp\", \"to\": \"263771943215\", \"type\": \"template\", \"template\": { \"name\": \"hello_world\", \"language\": { \"code\": \"en_US\" } } }
        // $parameters = array_merge_recursive([
        //     'messaging_product' => self::TYPE_RESPONSE,
        //     'preview_url' => true,
        //     'to' => $matchingMessage->getSender(),
        //     'type'=>"text",
        //     'text' => [
        //         'body' => $message,
        //     ],
        // ], $additionalParameters);
        $parameters = array_merge_recursive([
            'messaging_product' => self::TYPE_RESPONSE,
            // 'preview_url' => true,
            'to' => $matchingMessage->getSender(),
            'type'=>"template",
            'template' => [
                //'name' => $message,
                'name' => 'hello_world',
                'language'=>[
                   'code'=>"en_US"
                ]
            ],
        ], $additionalParameters);



        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Template) {
            $template = $this->convertTemplate($message);
            //dd($template);
            $parameters['type'] ="template";
            $parameters['template']['name'] = $template['template'];
            $parameters['template']['language']=['code'=>"en_US"];
            $parameters['template']['components']=$template['components'];
            //dd($parameters);
            //dd($parameters['message']);
        } elseif ($message instanceof Question) {
            unset($parameters['template']);
            $question=$this->convertQuestion($message);
            if ($question['type']=='button') {
               $parameters['type'] ="interactive";
               $parameters['interactive'] = $question;
            }elseif ($question['type']=='text') {
               $parameters['type'] ="text";
               $parameters['text']['body'] = $question['body']['text' ];
            }
            
        } elseif (is_object($message) && in_array(get_class($message), $this->templates)) {
            $parameters['message'] = $message->toArray();
        } elseif ($message instanceof OutgoingMessage) {
            $attachment = $message->getAttachment();
            if (! is_null($attachment) && in_array(get_class($attachment), $this->supportedAttachments)) {
                $attachmentType = strtolower(basename(str_replace('\\', '/', get_class($attachment))));
                //unset($parameters['message']['text']);
                unset($parameters['template']);
               
                if ("file"==$attachmentType) {
                   //unset($parameters[$attachmentType]['caption']);
                   $parameters['type']="document";
                   $parameters["document"]=['link'=>$attachment->getUrl(),'caption'=>$message->getText()];
                }else{
                    $parameters['type']=$attachmentType;
                    $parameters[$attachmentType]=['link'=>$attachment->getUrl(),'caption'=>$message->getText()];
                }
                // $parameters['message']['attachment'] = [
                //     'type' => $attachmentType,
                //     'payload' => [
                //         'is_reusable' => $attachment->getExtras('is_reusable') ?? false,
                //         'url' => $attachment->getUrl(),
                //     ],
                // ];
            } else {
               unset($parameters['template']);
               $parameters['type'] ="text";
               $parameters['text']['body'] = $message->getText();
            }
        }
        //dd($parameters);

        //$parameters['access_token'] = $this->config->get('token');

        return $parameters;
    }



    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! empty($this->config->get('bearer_token'));
    }


    /**
     * Retrieve User information.
     *
     * @param IncomingMessage $matchingMessage
     * @return User
     * @throws WhatsappException
     */
     public function getUser(IncomingMessage $matchingMessage)
    {
        //dd($matchingMessage);
        Log::channel('tracker')->info( __CLASS__."  method  ".__METHOD__ );
        Log::channel('tracker')->info( "  method  " );
        //dd($matchingMessage->getPayload());
        $contact = $matchingMessage->getPayload()['contacts'][0];
        return new User(
            $contact['wa_id'],
            $contact['profile']['name'],
            null,
            $contact['wa_id'],
            $contact
        );
    }



    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param Response $whatsappResponse
     * @return mixed
     * @throws WhatsappException
     */
    protected function throwExceptionIfResponseNotOk(Response $whatsappResponse)
    {
        if ($whatsappResponse->getStatusCode() !== 200) {
            $responseData = json_decode($whatsappResponse->getContent(), true);
            throw new WhatsappException('Error sending payload: '.$responseData['error']['message']);
        }
    }

    /**
     * @param $msg
     * @return string|null
     */
    protected function getMessageSender($msg)
    {
        if (isset($msg['from'])) {
            return $msg['from'];
        }
    }

    /**
     * @param $msg
     * @return string|null
     */
    protected function getMessageRecipient($msg)
    {
        if (isset($msg['from'])) {
            return $msg['from'];
        }
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
        return "https://graph.facebook.com/v13.0/".$this->config->get('phone_no_id') . '/' . $endpoint;
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
        Log::channel('tracker')->info( __CLASS__."  method  ".__METHOD__ );
        Log::channel('tracker')->info(  "  url  ".$url );
        Log::channel('tracker')->info(  "  urlParameters  ".print_r( $urlParameters ,1 ));
        Log::channel('tracker')->info(  "  postParameters  ".print_r( $postParameters ,1 ) );
        Log::channel('tracker')->info(  "  headers  ".print_r( $headers ,1 ) );
        Log::channel('tracker')->info(  "  postParametersjson  ".print_r( json_encode($postParameters) ,1 ) );
        $response = $this->http->post($url, $urlParameters, $postParameters, $headers, $asJSON);
        $responseData = json_decode($response->getContent(), true);
        //dd($responseData);
        Log::channel('tracker')->info(  "  responseData  ".print_r( $responseData ,1 ) );
        if ($response->isSuccessful()) {
            return $responseData;
        }

        $responseData['error']['code'] = $responseData['errors']['code'] ?? 'No description from Vendor';
        $responseData['error']['message'] = $responseData['errors']['title'] ?? 'No error code from Vendor';

        $message = "Status Code: {$response->getStatusCode()}\n".
            "Description: ".print_r($responseData['error']['message'], true)."\n".
            "Error Code: ".print_r($responseData['error']['code'], true)."\n".
            "URL: $url\n".
            "URL Parameters: ".print_r($urlParameters, true)."\n".
            "Post Parameters: ".print_r($postParameters, true)."\n".
            "Headers: ". print_r($headers, true)."\n";

        throw new WhatsappException($message);
    }


    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        //dd($payload);
        if ($this->config->get('throw_http_exceptions')) {
            return $this->postWithExceptionHandling($this->buildApiUrl($this->endpoint), [], $payload, $this->buildAuthHeader(), true);
    }

        return $this->http->post($this->buildApiUrl($this->endpoint), [], $payload, $this->buildAuthHeader(), true);
    }


}
