<?php

namespace BotMan\Drivers\Whatsapp\Extensions\Airline;

use BotMan\Drivers\Whatsapp\Interfaces\Airline;
use JsonSerializable;

abstract class AbstractAirlineFlightInfo implements JsonSerializable, Airline
{
    /**
     * @var string
     */
    protected $flightNumber;

    /**
     * @var \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport
     */
    protected $departureAirport;

    /**
     * @var \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport
     */
    protected $arrivalAirport;

    /**
     * @var \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineFlightSchedule
     */
    protected $flightSchedule;

    /**
     * AbstractAirlineFlightInfo constructor.
     *
     * @param string                                                            $flightNumber
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport        $departureAirport
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport        $arrivalAirport
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineFlightSchedule $flightSchedule
     */
    public function __construct(
        string $flightNumber,
        AirlineAirport $departureAirport,
        AirlineAirport $arrivalAirport,
        AirlineFlightSchedule $flightSchedule
    ) {
        $this->flightNumber = $flightNumber;
        $this->departureAirport = $departureAirport;
        $this->arrivalAirport = $arrivalAirport;
        $this->flightSchedule = $flightSchedule;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'flight_number' => $this->flightNumber,
            'departure_airport' => $this->departureAirport,
            'arrival_airport' => $this->arrivalAirport,
            'flight_schedule' => $this->flightSchedule,
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
