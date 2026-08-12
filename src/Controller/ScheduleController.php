<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ScheduleController extends AbstractController
{
    private const MONTH_KEYS = [
        '01' => 'jan', '02' => 'feb', '03' => 'mar', '04' => 'apr',
        '05' => 'may', '06' => 'jun', '07' => 'jul', '08' => 'aug',
        '09' => 'sep', '10' => 'oct', '11' => 'nov', '12' => 'dec',
    ];

    #[Route('/schedule', name: 'schedule')]
    public function index(EventRepository $eventRepository): Response
    {
        $eventsByMonth = [];
        $eventsByDate = [];
        foreach ($eventRepository->findVisibleOrderedByDate(null !== $this->getUser()) as $event) {
            $monthNum = $event->getDate()->format('m');
            $eventsByMonth[$monthNum]['label'] = self::MONTH_KEYS[$monthNum];
            $eventsByMonth[$monthNum]['events'][] = [
                'event' => $event,
                'calendarLinks' => $this->buildCalendarLinks($event),
            ];

            // Pour le mini-calendrier JS (assets/schedule/schedule.js) : un
            // jour peut avoir plusieurs événements, d'où le tableau de
            // titres plutôt qu'un simple booléen (utilisé comme title="").
            $eventsByDate[$event->getDate()->format('Y-m-d')][] = $event->getTitle();
        }

        return $this->render('schedule/index.html.twig', [
            'eventsByMonth' => $eventsByMonth,
            'eventsByDate' => $eventsByDate,
        ]);
    }

    /**
     * Fichier .ics standard (pas d'API externe type Google Calendar : un
     * fichier iCalendar téléchargeable s'importe partout, Google/Outlook/
     * Apple Calendar compris). Pas de champ "durée" sur Event, 2h par
     * défaut (répétitions/concerts durent rarement moins).
     */
    #[Route('/schedule/event/{id}.ics', name: 'event_ics', methods: ['GET'])]
    public function ics(Event $event): Response
    {
        $this->denyAccessUnlessVisible($event);

        $start = $event->getDate();
        // 00:00 = aucune heure connue pour cet événement (pas une vraie
        // heure de minuit) : événement "journée entière" en iCalendar
        // (DTSTART/DTEND en VALUE=DATE) plutôt qu'un horaire 00h-02h inventé.
        $isAllDay = '00:00' === $start->format('H:i');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Binioufous//Planning//FR',
            'BEGIN:VEVENT',
            'UID:event-'.$event->getId().'@binioufous',
            'DTSTAMP:'.(new \DateTimeImmutable())->format('Ymd\THis\Z'),
        ];
        if ($isAllDay) {
            $lines[] = 'DTSTART;VALUE=DATE:'.$start->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$start->modify('+1 day')->format('Ymd');
        } else {
            $lines[] = 'DTSTART:'.$start->format('Ymd\THis');
            $lines[] = 'DTEND:'.$this->resolveEnd($event)->format('Ymd\THis');
        }
        $lines[] = 'SUMMARY:'.$this->escapeIcsText($event->getTitle());
        $lines[] = 'LOCATION:'.$this->escapeIcsText($event->getLocation());
        if ($event->getDescription()) {
            $lines[] = 'DESCRIPTION:'.$this->escapeIcsText($event->getDescription());
        }
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return new Response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$event->getId().'.ics"',
        ]);
    }

    /**
     * Liens "ajouter à l'agenda" pour Google et Outlook (en plus du .ics
     * générique, cf. ics() ci-dessus, qui couvre Apple Calendar/Thunderbird/
     * Outlook desktop) : deep links documentés publiquement par chacun, pas
     * d'API/OAuth nécessaire, ouvrent directement le formulaire d'ajout
     * pré-rempli dans un nouvel onglet.
     */
    private function buildCalendarLinks(Event $event): array
    {
        $start = $event->getDate();
        $isAllDay = '00:00' === $start->format('H:i');

        if ($isAllDay) {
            $googleDates = $start->format('Ymd').'/'.$start->modify('+1 day')->format('Ymd');
        } else {
            $googleDates = $start->format('Ymd\THis').'/'.$this->resolveEnd($event)->format('Ymd\THis');
        }
        $google = 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $event->getTitle(),
            'dates' => $googleDates,
            'details' => (string) $event->getDescription(),
            'location' => $event->getLocation(),
        ]);

        $outlookParams = [
            'subject' => $event->getTitle(),
            'body' => (string) $event->getDescription(),
            'location' => $event->getLocation(),
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
        ];
        if ($isAllDay) {
            $outlookParams['startdt'] = $start->format('Y-m-d');
            $outlookParams['enddt'] = $start->modify('+1 day')->format('Y-m-d');
            $outlookParams['allday'] = 'true';
        } else {
            $outlookParams['startdt'] = $start->format('Y-m-d\TH:i:s');
            $outlookParams['enddt'] = $this->resolveEnd($event)->format('Y-m-d\TH:i:s');
        }
        $outlook = 'https://outlook.live.com/calendar/0/deeplink/compose?'.http_build_query($outlookParams);

        return ['google' => $google, 'outlook' => $outlook];
    }

    /**
     * Même règle de visibilité que EventRepository::findVisibleOrderedByDate()
     * pour la page /schedule, mais appliquée ici à un accès direct par id
     * (route event_ics) : sans ça, un événement caché sur la page publique
     * (répétition/autre, hors connexion) restait quand même récupérable en
     * devinant/connaissant son id. 404 plutôt que 403 pour ne pas confirmer
     * qu'un événement existe à cet id.
     */
    private function denyAccessUnlessVisible(Event $event): void
    {
        if ('concert' !== $event->getType() && null === $this->getUser()) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * Heure de fin réelle si elle a été renseignée (Event::$endDate),
     * sinon repli sur +2h par défaut plutôt que de laisser un champ vide
     * dans l'export.
     */
    private function resolveEnd(Event $event): \DateTimeImmutable
    {
        return $event->getEndDate() ?? $event->getDate()->modify('+2 hours');
    }

    /**
     * Échappement texte iCalendar (RFC 5545) : virgule/point-virgule/
     * antislash/saut de ligne ont un sens spécial dans le format.
     */
    private function escapeIcsText(string $text): string
    {
        return str_replace(
            ['\\', ',', ';', "\n"],
            ['\\\\', '\\,', '\\;', '\\n'],
            $text
        );
    }
}
