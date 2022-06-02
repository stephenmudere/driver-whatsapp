<?php

namespace BotMan\Drivers\Whatsapp\Extensions;

use BotMan\Drivers\Whatsapp\Exceptions\WhatsappException;
use BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineFlightInfo;

class AirlineUpdateTemplate extends AbstractAirlineTemplate
{
    /**
     * @var string
     */
    protected $introMessage;

    /**
     * @var string
     */
    protected $updateType;

    /**
     * @var string
     */
    protected $pnrNumber;

    /**
     * @var \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineFlightInfo
     */
    protected $updateFlightInfo;

    /**
     * AirlineUpdateTemplate constructor.
     *
     * @param string                                                        $updateType
     * @param string                                                        $locale
     * @param string                                                        $pnrNumber
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineFlightInfo $updateFlightInfo
     *
     * @throws \BotMan\Drivers\Whatsapp\Exceptions\WhatsappException
     */
    public function __construct(
        string $updateType,
        string $locale,
        string $pnrNumber,
        AirlineFlightInfo $updateFlightInfo
    ) {
        if (! \in_array($updateType, self::UPDATE_TYPES, true)) {
            throw new WhatsappException(
                sprintf('update_type must be either "%s"', implode(', ', self::UPDATE_TYPES))
            );
        }

        parent::__construct($locale);

        $this->updateType = $updateType;
        $this->locale = $locale;
        $this->pnrNumber = $pnrNumber;
        $this->updateFlightInfo = $updateFlightInfo;
    }

    /**
     * @param string $introMessage
     *
     * @return \BotMan\Drivers\Whatsapp\Extensions\AirlineUpdateTemplate
     */
    public function introMessage(string $introMessage): self
    {
        $this->introMessage = $introMessage;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        return array_merge_recursive($array, [
            'attachment' => [
                'payload' => [
                    'template_type' => 'airline_update',
                    'intro_message' => $this->introMessage,
                    'update_type' => $this->updateType,
                    'pnr_number' => $this->pnrNumber,
                    'update_flight_info' => $this->updateFlightInfo,
                ],
            ],
        ]);
    }

    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver(): array
    {
        $webDriver = parent::toWebDriver();
        $webDriver += [
            'template_type' => 'airline_update',
            'intro_message' => $this->introMessage,
            'update_type' => $this->updateType,
            'pnr_number' => $this->pnrNumber,
            'update_flight_info' => $this->updateFlightInfo,
        ];

        return $webDriver;
    }
}
