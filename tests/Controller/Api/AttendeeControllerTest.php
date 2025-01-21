<?php

namespace App\Tests\Controller\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Message\AttendeeConfirmationEmailMessage;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class AttendeeControllerTest extends ApiTestCase
{
    use InteractsWithMessenger;
    
    private static $eventId = 0;

    private const ATTENDEE_DETAILS_REPONSE_KEYS = [
        'id', 'name', 'email'
    ];
    
    public function testRegisterSuccessful(): void
    {
        // create new event for attendee to register
        // this event is used in the other methods below to save having to create and register every time
        // if it ever gets too confusing we can decouple each test so they have their own $eventId
        $eventResponse = static::createClient()->request(
            'POST', 
            '/api/events',
            [
                'json' => [
                    'name' => 'Attendee test event name',
                    'description' => 'test description',
                    'startDate' => '2025-01-18 09:00:00',
                    'endDate' => '2025-01-18 17:00:00',
                    'location' => 'London, UK',
                    'capacity' => 2,
                ]
            ]
        );
        $eventData = json_decode($eventResponse->getContent());
        $this->assertEquals(0, count($eventData->attendees));

        // set event ID to be used in other methods
        static::$eventId = $eventData->id;

        // add first attendee
        $attendeeResponse = static::createClient()->request(
            'POST', 
            sprintf('/api/events/%d/attendees', static::$eventId),
            [
                'json' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe1@example.com',
                ]
            ]
        );

        $attendeeData = json_decode($attendeeResponse->getContent());

        $attendeeResponseDiff = array_diff(
            self::ATTENDEE_DETAILS_REPONSE_KEYS, 
            array_keys(get_object_vars($attendeeData))
        );
        $this->assertCount(0, $attendeeResponseDiff, 'Response missing properties: ' . json_encode($attendeeResponseDiff));

        // verify attendee has been added to event
        $eventDetailsResponse = static::createClient()->request(
            'GET', 
            sprintf('/api/events/%d', static::$eventId)
        );
        $eventDetailsData = json_decode($eventDetailsResponse->getContent());
        $this->assertEquals(1, count($eventDetailsData->attendees));

        $eventResponseDiff = array_diff(
            self::ATTENDEE_DETAILS_REPONSE_KEYS, 
            array_keys(get_object_vars($eventDetailsData->attendees[0]))
        );
        $this->assertCount(0, $eventResponseDiff, 'Response missing properties: ' . json_encode($eventResponseDiff));

        // check email message was dispatched
        $this->transport('async')->queue()->assertContains(AttendeeConfirmationEmailMessage::class, 1);
    }
    
    public function testRegisterDuplicateEmail(): void
    {
        // register with same email to same event
        $attendeeResponse = static::createClient()->request(
            'POST', 
            sprintf('/api/events/%d/attendees', static::$eventId),
            [
                'json' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe1@example.com',
                ]
            ]
        );
        $this->assertEquals(409, $attendeeResponse->getStatusCode());

        // double check attendees count hasn't incremented
        $eventDetailsResponse = static::createClient()->request(
            'GET', 
            sprintf('/api/events/%d', static::$eventId)
        );
        $eventDetailsData = json_decode($eventDetailsResponse->getContent());
        $this->assertEquals(1, count($eventDetailsData->attendees));
    }

    public function testRegisterInvalidPayloads(): void
    {
        $jsonArr = [
            [
                'email' => 'john.doe1@example.com',
            ],
            [
                'name' => 'John Doe',
            ],
            [
                'name' => 'John Doe',
                'email' => 'john.doe1example.com', // invalid email
            ],
            [
                'name' => 'John Doe',
                'email' => 'john.doe1@example', // invalid email
            ],
        ];

        foreach ($jsonArr as $json) {
            $response = static::createClient()->request(
                'POST', 
                sprintf('/api/events/%d/attendees', static::$eventId),
                [
                    'json' => $json
                ]
            );

            $this->assertEquals(422, $response->getStatusCode());
        }
    }

    public function testRegisterCapcityReached(): void
    {
        // check total attendees
        $eventDetails1Response = static::createClient()->request(
            'GET', 
            sprintf('/api/events/%d', static::$eventId)
        );
        $eventDetails1Data = json_decode($eventDetails1Response->getContent());
        $this->assertEquals(1, count($eventDetails1Data->attendees));

        // add another attendee
        $attendeeResponse = static::createClient()->request(
            'POST', 
            sprintf('/api/events/%d/attendees', static::$eventId),
            [
                'json' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe2@example.com',
                ]
            ]
        );

        // check total attendees again, should be maxed out
        $eventDetails2Response = static::createClient()->request(
            'GET', 
            sprintf('/api/events/%d', static::$eventId)
        );
        $eventDetails2Data = json_decode($eventDetails2Response->getContent());
        $this->assertEquals(2, count($eventDetails2Data->attendees));

        // try to add another attendee over the liimit
        $attendeeLimitResponse = static::createClient()->request(
            'POST', 
            sprintf('/api/events/%d/attendees', static::$eventId),
            [
                'json' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe3@example.com',
                ]
            ]
        );
        $this->assertEquals(422, $attendeeLimitResponse->getStatusCode());
    }

    public function testRegisterDeleteSuccess(): void
    {
        // get list of attendees
        $eventDetails1Response = static::createClient()->request(
            'GET', 
            sprintf('/api/events/%d', static::$eventId)
        );
        $eventDetails1Data = json_decode($eventDetails1Response->getContent());
        $this->assertEquals(2, count($eventDetails1Data->attendees));

        // delete attendee
        $attendeeResponse = static::createClient()->request(
            'DELETE', 
            sprintf(
                '/api/events/%d/attendees/%d', 
                static::$eventId,
                end($eventDetails1Data->attendees)->id
            )
        );
        $this->assertEquals(204, $attendeeResponse->getStatusCode());

        // check attendee count has decreased
        $eventDetails2Response = static::createClient()->request(
            'GET', 
            sprintf('/api/events/%d', static::$eventId)
        );
        $eventDetails2Data = json_decode($eventDetails2Response->getContent());
        $this->assertEquals(1, count($eventDetails2Data->attendees));
    }

    public function testRegisterDeleteForbidden(): void
    {
        // create new event
        $eventResponse = static::createClient()->request(
            'POST', 
            '/api/events',
            [
                'json' => [
                    'name' => 'Attendee test event name the second',
                    'description' => 'test description',
                    'startDate' => '2025-01-18 09:00:00',
                    'endDate' => '2025-01-18 17:00:00',
                    'location' => 'London, UK',
                    'capacity' => 2,
                ]
            ]
        );
        $eventData = json_decode($eventResponse->getContent());
        $eventId = $eventData->id;

        // get attendee id from previous event
        $eventDetails1Response = static::createClient()->request(
            'GET', 
            sprintf('/api/events/%d', static::$eventId)
        );
        $eventDetails1Data = json_decode($eventDetails1Response->getContent());
        $attendeeId = $eventDetails1Data->attendees[0]->id;

        // try and delete attendee
        $attendeeResponse = static::createClient()->request(
            'DELETE', 
            sprintf(
                '/api/events/%d/attendees/%d', 
                $eventId,
                $attendeeId
            )
        );
        $this->assertEquals(403, $attendeeResponse->getStatusCode());
    }
}
