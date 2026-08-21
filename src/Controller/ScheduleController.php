<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

// Public /schedule page, plus .ics/Google/Outlook calendar export for each event.
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

            // For the mini-calendar JS: a day can have several events, hence an array of titles rather than a plain boolean, used as a title="" tooltip.
            $eventsByDate[$event->getDate()->format('Y-m-d')][] = $event->getTitle();
        }

        return $this->render('schedule/index.html.twig', [
            'eventsByMonth' => $eventsByMonth,
            'eventsByDate' => $eventsByDate,
        ]);
    }

    // Standard downloadable .ics file rather than a Google Calendar-specific API: imports everywhere (Google/Outlook/Apple Calendar).
    #[Route('/schedule/event/{id}.ics', name: 'event_ics', methods: ['GET'])]
    public function ics(Event $event): Response
    {
        $this->denyAccessUnlessVisible($event);

        $start = $event->getDate();
        // 00:00 means no time was set for this event, not a real midnight: exported as an all-day iCalendar event (DTSTART/DTEND VALUE=DATE) rather than inventing a 00h-02h slot.
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

    // "Add to calendar" deep links for Google and Outlook, publicly documented by each provider, no API/OAuth needed.
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

    // Same visibility rule as findVisibleOrderedByDate(), applied here to direct access by id so a hidden event can't be fetched by guessing its URL. 404 rather than 403 to avoid confirming the event exists.
    private function denyAccessUnlessVisible(Event $event): void
    {
        if ('concert' !== $event->getType() && null === $this->getUser()) {
            throw new NotFoundHttpException();
        }
    }

    // Real end time when set, otherwise a +2h default rather than leaving the export field empty.
    private function resolveEnd(Event $event): \DateTimeImmutable
    {
        return $event->getEndDate() ?? $event->getDate()->modify('+2 hours');
    }

    // iCalendar text escaping (RFC 5545): comma/semicolon/backslash/newline are special characters in the format.
    private function escapeIcsText(string $text): string
    {
        return str_replace(
            ['\\', ',', ';', "\n"],
            ['\\\\', '\\,', '\\;', '\\n'],
            $text
        );
    }
}
