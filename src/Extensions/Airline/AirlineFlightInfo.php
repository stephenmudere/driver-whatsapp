<?php

namespace BotMan\Drivers\Whatsapp\Extensions\Airline;

class AirlineFlightInfo extends AbstractAirlineFlightInfo
{
    /**
     * @param string                                                            $flightNumber
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport        $departureAirport
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport        $arrivalAirport
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineFlightSchedule $flightSchedule
     *
     * @return \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineFlightInfo
     */
    public static function create(
        string $flightNumber,
        AirlineAirport $departureAirport,
        AirlineAirport $arrivalAirport,
        AirlineFlightSchedule $flightSchedule
    ): self {
        return new self($flightNumber, $departureAirport, $arrivalAirport, $flightSchedule);
    }
}
