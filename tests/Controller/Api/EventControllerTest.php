<?php

namespace App\Tests\Controller\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\Process\Process;

class EventControllerTest extends ApiTestCase
{
    private static $eventId = 0;

    private const EVENT_DETAILS_REPONSE_KEYS = [
        'id', 'name', 'description', 'startDate', 'endDate', 'location', 'capacity', 'attendees'
    ];

    private const EVENT_LIST_REPONSE_KEYS = [
        'id', 'name', 'description', 'startDate', 'endDate', 'location', 'capacity', 'totalAttendees'
    ];

    public function testCreateSuccessful(): void
    {
        $response = static::createClient()->request(
            'POST', 
            '/api/events',
            [
                'json' => [
                    'name' => 'test event name',
                    'description' => 'test description',
                    'startDate' => '2025-01-17 09:00:00',
                    'endDate' => '2025-01-17 17:00:00',
                    'location' => 'London, UK',
                    'capacity' => 5,
                ]
            ]
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($response->getContent());
        
        // set event to be used in other methods
        static::$eventId = $data->id;

        $this->assertEquals('test event name', $data->name);
        $this->assertEquals(5, $data->capacity);

        $arrResponseDiff = array_diff(
            self::EVENT_DETAILS_REPONSE_KEYS, 
            array_keys(get_object_vars($data))
        );
        $this->assertCount(0, $arrResponseDiff, 'Response missing properties: ' . json_encode($arrResponseDiff));
    }

    public function testDetailsSuccessful(): void
    {
        $response = static::createClient()->request(
            'GET', 
            sprintf('/api/events/%d', static::$eventId)
        );
        
        $data = json_decode($response->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertEquals('test event name', $data->name);

        $arrResponseDiff = array_diff(
            self::EVENT_DETAILS_REPONSE_KEYS, 
            array_keys(get_object_vars($data))
        );
        $this->assertCount(0, $arrResponseDiff, 'Response missing properties: ' . json_encode($arrResponseDiff));
    }

    public function testListSuccessful(): void
    {
        $response = static::createClient()->request(
            'GET', 
            '/api/events'
        );

        $data = json_decode($response->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, count(json_decode($response->getContent())));

        $arrResponseDiff = array_diff(
            self::EVENT_LIST_REPONSE_KEYS, 
            array_keys(get_object_vars(end($data)))
        );
        $this->assertCount(0, $arrResponseDiff, 'Response missing properties: ' . json_encode($arrResponseDiff));
    }

    public function testCreateInvalidPayloads(): void
    {
        $jsonArr = [
            [
                'description' => 'test description',
                'startDate' => '2025-01-17 09:00:00',
                'endDate' => '2025-01-17 17:00:00',
                'location' => 'London, UK',
                'capacity' => 5,
            ],
            [
                'name' => 'test event name',
                'description' => 'test description',
                'endDate' => '2025-01-17 17:00:00',
                'location' => 'London, UK',
                'capacity' => 5,
            ],
            [
                'name' => 'test event name',
                'description' => 'test description',
                'startDate' => '2025-01-17 09:00:00',
                'location' => 'London, UK',
                'capacity' => 5,
            ],
            [
                'name' => 'test event name',
                'description' => 'test description',
                'startDate' => '2025-01-17 09:00:00',
                'endDate' => '2025-01-17 17:00:00',
                'capacity' => 5,
            ],
            [
                'name' => 'test event name',
                'description' => 'test description',
                'startDate' => '2025-01-17 09:00:00',
                'endDate' => '2025-01-17 17:00:00',
                'location' => 'London, UK',
            ],
        ];

        foreach ($jsonArr as $json) {
            $response = static::createClient()->request(
                'POST', 
                '/api/events',
                [
                    'json' => $json
                ]
            );

            $this->assertEquals(422, $response->getStatusCode());
        }
    }
}
