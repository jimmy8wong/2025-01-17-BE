<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Model\EventDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[Route('/api/events')]
final class EventController extends AbstractController {
    /**
     * Create new event
     */
    #[Route('', name: 'api_events_create', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Returns event details',
        content: new OA\JsonContent(
            ref: new Model(type: Event::class, groups: ['event:details', 'attendee:details'])
        )
    )]
    #[OA\Tag(name: 'events')]
    public function create(
        #[MapRequestPayload] EventDto $eventDto,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $event = new Event();
        $event->setName($eventDto->name);
        $event->setLocation($eventDto->location);
        $event->setStartDate(\DateTime::createFromFormat('Y-m-d H:i:s', $eventDto->startDate));
        $event->setEndDate(\DateTime::createFromFormat('Y-m-d H:i:s', $eventDto->endDate));
        $event->setCapacity($eventDto->capacity);

        $entityManager->persist($event);
        $entityManager->flush();
        
        return $this->json($event, $status = 201, [], [
            'groups' => ['event:details', 'attendee:details']
        ]);
    }

    /**
     * List all events
     */
    #[Route('', name: 'api_events_list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns list of events',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Event::class, groups: ['event:list']))
        )
    )]
    #[OA\Tag(name: 'events')]
    public function list(
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $events = $entityManager->getRepository(Event::class)->findAll();
        
        return $this->json($events, $status = 200, [], [
            'groups' => ['event:list']
        ]);
    }

    /**
     * Get details of an event
     */
    #[Route('/{eventId}', name: 'api_events_details', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns event details',
        content: new OA\JsonContent(
            ref: new Model(type: Event::class, groups: ['event:details', 'attendee:details'])
        )
    )]
    #[OA\Tag(name: 'events')]
    public function details(
        #[MapEntity(id: 'eventId')] Event $event
    ): JsonResponse {        
        return $this->json($event, $status = 200, [], [
            'groups' => ['event:details', 'attendee:details']
        ]);
    }
}
