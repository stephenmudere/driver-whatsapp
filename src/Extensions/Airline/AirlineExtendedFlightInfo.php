<?php

namespace BotMan\Drivers\Whatsapp\Extensions\Airline;

use BotMan\Drivers\Whatsapp\Exceptions\WhatsappException;

class AirlineExtendedFlightInfo extends AbstractAirlineFlightInfo
{
    /**
     * @var string
     */
    protected $connectionId;

    /**
     * @var string
     */
    protected $segmentId;

    /**
     * @var string
     */
    protected $aircraftType;

    /**
     * @var string
     */
    protected $travelClass;

    /**
     * @param string                                                            $connectionId
     * @param string                                                            $segmentId
     * @param string                                                            $flightNumber
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport        $departureAirport
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport        $arrivalAirport
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineFlightSchedule $flightSchedule
     * @param string                                                            $travelClass
     *
     * @throws \BotMan\Drivers\Whatsapp\Exceptions\WhatsappException
     *
     * @return \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineExtendedFlightInfo
     */
    public static function create(
        string $connectionId,
        string $segmentId,
        string $flightNumber,
        AirlineAirport $departureAirport,
        AirlineAirport $arrivalAirport,
        AirlineFlightSchedule $flightSchedule,
        string $travelClass
    ): self {
        return new static(
            $connectionId,
            $segmentId,
            $flightNumber,
            $departureAirport,
            $arrivalAirport,
            $flightSchedule,
            $travelClass
        );
    }

    /**
     * AirlineExtendedFlightInfo constructor.
     *
     * @param string                                                            $connectionId
     * @param string                                                            $segmentId
     * @param string                                                            $flightNumber
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport        $departureAirport
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineAirport        $arrivalAirport
     * @param \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineFlightSchedule $flightSchedule
     * @param string                                                            $travelClass
     *
     * @throws \BotMan\Drivers\Whatsapp\Exceptions\WhatsappException
     */
    public function __construct(
        string $connectionId,
        string $segmentId,
        string $flightNumber,
        AirlineAirport $departureAirport,
        AirlineAirport $arrivalAirport,
        AirlineFlightSchedule $flightSchedule,
        string $travelClass
    ) {
        if (! \in_array($travelClass, self::TRAVEL_TYPES, true)) {
            throw new WhatsappException(
                sprintf('travel_class must be either "%s"', implode(', ', self::TRAVEL_TYPES))
            );
        }

        parent::__construct($flightNumber, $departureAirport, $arrivalAirport, $flightSchedule);

        $this->connectionId = $connectionId;
        $this->segmentId = $segmentId;
        $this->travelClass = $travelClass;
    }

    /**
     * @param string $aircraftType
     *
     * @return \BotMan\Drivers\Whatsapp\Extensions\Airline\AirlineExtendedFlightInfo
     */
    public function aircraftType(string $aircraftType): self
    {
        $this->aircraftType = $aircraftType;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array += [
            'connection_id' => $this->connectionId,
            'segment_id' => $this->segmentId,
            'travel_class' => $this->travelClass,
            'aircraft_type' => $this->aircraftType,
        ];

        return array_filter($array);
    }
}
