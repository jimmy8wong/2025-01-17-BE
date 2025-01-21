<?php

namespace App\Controller\Api;

use App\Entity\Attendee;
use App\Entity\Event;
use App\Message\AttendeeConfirmationEmailMessage;
use App\Model\AttendeeDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[Route('/api/events/{eventId}/attendees')]
final class AttendeeController extends AbstractController {
    /**
     * Register an attendee to an event
     */
    #[Route('', name: 'api_attendees_register', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Returns attendee details',
        content: new OA\JsonContent(
            ref: new Model(type: Attendee::class, groups: ['attendee:details'])
        )
    )]
    #[OA\Response(
        response: 409,
        description: 'Returned when an email has already been registered for this event'
    )]
    #[OA\Response(
        response: 422,
        description: 'Returned when capacity for this event is full'
    )]
    #[OA\Tag(name: 'attendees')]
    public function register(
        #[MapEntity(id: 'eventId')] Event $event,
        #[MapRequestPayload] AttendeeDto $attendeeDto,
        MessageBusInterface $bus,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // TODO move to service
        if ($event->getTotalAttendees() >= $event->getCapacity()) {
            throw new UnprocessableEntityHttpException('Capacity for this event is full');
        }

        $emailExists = $entityManager->getRepository(Attendee::class)->countTotalAttendeesByEventAndEmail(
            $event->getId(),
            $attendeeDto->email
        );

        if ($emailExists > 0) {
            throw new ConflictHttpException('Email has already been registered for this event');
        }
        
        $attendee = new Attendee();
        $attendee->setEvent($event);
        $attendee->setName($attendeeDto->name);
        $attendee->setEmail($attendeeDto->email);

        $entityManager->persist($attendee);
        $entityManager->flush();

        $bus->dispatch(new AttendeeConfirmationEmailMessage($attendee->getId()));
        
        return $this->json($attendee, $status = 201, [], [
            'groups' => ['attendee:details']
        ]);
    }

    /**
     * Deletes an attendee from an event
     */
    #[Route('/{attendeeId}', name: 'api_attendees_delete', methods: ['DELETE'])]
    #[OA\Response(
        response: 204,
        description: 'Returned when an attendee has been deleted from an event'
    )]
    #[OA\Response(
        response: 403,
        description: 'Returned when an attempt to delete an attendee from an event they don\'t belong to'
    )]
    #[OA\Tag(name: 'attendees')]
    public function delete(
        #[MapEntity(id: 'eventId')] Event $event,
        #[MapEntity(id: 'attendeeId')] Attendee $attendee,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if ($event->getId() === $attendee->getEvent()->getId()) {
            $entityManager->remove($attendee);
            $entityManager->flush();
            return $this->json([], $status = 204);
        }

        throw new AccessDeniedHttpException("attendee doesnt belong to this event");
    }
}
