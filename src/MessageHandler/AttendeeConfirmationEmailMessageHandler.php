<?php

namespace App\MessageHandler;

use App\Message\AttendeeConfirmationEmailMessage;
use App\Repository\AttendeeRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AttendeeConfirmationEmailMessageHandler
{
    public function __construct(
        private AttendeeRepository $attendeeRepository,
        private MailerInterface $mailer,
        private ParameterBagInterface $parameterBag
    ) {
    }

    public function __invoke(AttendeeConfirmationEmailMessage $message): void 
    {
        $attendee = $this->attendeeRepository->find($message->getAttendeeId());

        $email = (new Email())
            ->from($this->parameterBag->get('email_from'))
            ->to($attendee->getEmail())
            ->subject('Event Confirmation: ' . $attendee->getEvent()->getName())
            ->text(sprintf(
                'You have been confirmed for %s which is due to start: %s',
                $attendee->getEvent()->getName(),
                $attendee->getEvent()->getStartDate()->format('l jS \of F Y H:i:s')
            ))
            ->html(sprintf(
                '<p>You have been confirmed for <b>%s</b> which is due to start: %s</p>',
                $attendee->getEvent()->getName(),
                $attendee->getEvent()->getStartDate()->format('l jS \of F Y H:i:s')
            ));

        $this->mailer->send($email);
    }
}
