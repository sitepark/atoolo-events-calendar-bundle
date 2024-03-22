<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\RceEvent;

use Atoolo\EventsCalendar\Dto\RceEvent\RceEventAddress;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventAddresses;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventCategory;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventDate;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventListItem;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventSource;
use Atoolo\EventsCalendar\Dto\RceEvent\RceEventUpload;
use DateTime;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Soundasleep\Html2Text;

class RceEventListItemFactory
{
    private const SCHEDULE_STATUS_SOLDOUT  = 'soldout';
    private const SCHEDULE_STATUS_CANCELED = 'canceled';
    private const EVENT_TYPE_KEY = 'digitalevent';
    private const EVENT_TYPE_VALUE_ONSITE = 'onsite';
    private const EVENT_TYPE_VALUE_ONLINE = 'online';
    private const EVENT_TYPE_VALUE_HYBRID = 'hybrid'; // onsite and online event
    private const YES = 'yes';

    /**
     * Event type subcategories (SUBTHEME in the xml) on the same
     * level as the event type categories (THEME in the xml)
     */
    private const CATEGORIES_WITHOUT_HIERARCHY = true;

    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function create(SimpleXMLElement $event): RceEventListItem
    {
        return new RceEventListItem(
            $this->createName($event),
            $this->isActiveEvent($event),
            $this->createDateList($event),
            $this->createDescription($event),
            $this->isOnline($event),
            $this->isOnsite($event),
            $this->createTicketLink($event),
            $this->createTheme($event),
            $this->createSubTheme($event),
            $this->isHighlight($event),
            $this->createSource($event),
            $this->createAddresses($event),
            $this->createKeywords($event),
            $this->createUploads($event)
        );
    }

    private function createName(SimpleXMLElement $event): string
    {
        return (string)($event->NAME ?? '');
    }

    private function isActiveEvent(SimpleXMLElement $event): bool
    {
        $isActive = (string)$event['active'];
        $isActive = strtolower($isActive);
        return ($isActive === self::YES);
    }

    private function createDateList(SimpleXMLElement $event): array
    {
        if (!isset($event->DATELIST->DATE)) {
            return [];
        }

        $dateList = [];
        foreach ($event->DATELIST->DATE as $date) {
            $dateList[] = new RceEventDate(
                (string)$date->attributes()['hashid'],
                $this->createDateTime($date, 'STARTTIME', '00:00:00'),
                $this->createDateTime($date, 'ENDTIME', '23:59:59'),
                $this->isBlacklistedEventDate($date),
                $this->isSoldOut($date),
                $this->isCanceled($date)
            );
        }
        return $dateList;
    }

    private function createDescription(SimpleXMLElement $event)
    {
        $desc = (string)$event->DESCRIPTION;
        $description = '';
        try {
            $eventDescription = $this->htmlToText($desc, true);
            $eventDescription = $this->replaceLineFeeds($eventDescription);
            $eventDescription = str_replace('<br>', ' ', $eventDescription);
            $eventDescription = explode(' ', $eventDescription);
            $eventDescription = array_map('trim', $eventDescription);
            $description = '';
            foreach ($eventDescription as $str) {
                if (!empty($str)) {
                    if (strlen($description) > 0) {
                        $description .= ' ';
                    }
                    $description .= $str;
                }
            }
        // no test scenario found that triggers this exception.
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            $this->logger->error(
                'sanitize description failed',
                [
                    'exception' => $e
                ]
            );
        }
        //@codeCoverageIgnoreEnd
        return $description;
    }

    private function isOnline(SimpleXMLElement $event): bool
    {
        if (!empty((string)$event->TICKETLINK)) {
            return false;
        }

        $eventType = (string)$event[self::EVENT_TYPE_KEY];
        return
            $eventType === self::EVENT_TYPE_VALUE_ONLINE ||
            $eventType === self::EVENT_TYPE_VALUE_HYBRID;
    }

    private function isOnsite(SimpleXMLElement $event): bool
    {
        $eventType = (string)$event[self::EVENT_TYPE_KEY];
        return $eventType === self::EVENT_TYPE_VALUE_ONSITE;
    }

    private function isSoldOut(SimpleXMLElement $date): bool
    {
        if (!isset($date->STATUS)) {
            return false;
        }

        return ((string)$date->STATUS) === self::SCHEDULE_STATUS_SOLDOUT;
    }

    private function isCanceled(SimpleXMLElement $date): bool
    {
        if (!isset($date->STATUS)) {
            return false;
        }

        return ((string)$date->STATUS) === self::SCHEDULE_STATUS_CANCELED;
    }

    private function isHighlight(SimpleXMLElement $event): bool
    {
        $highlight = strtolower((string)$event->DESCRIPTION['highlight']);
        return $highlight === self::YES;
    }

    private function createTicketLink(SimpleXMLElement $event): string
    {
        return (string)($event->TICKETLINK ?? '');
    }

    private function createTheme(SimpleXMLElement $event): ?RceEventCategory
    {
        if (empty($event->THEME)) {
            return null;
        }
        return new RceEventCategory(
            (string)($event->THEME['id'] ?? ''),
            (string)($event->THEME ?? '')
        );
    }

    private function createSubTheme(SimpleXMLElement $event): ?RceEventCategory
    {
        if (empty($event->SUBTHEME)) {
            return null;
        }
        return new RceEventCategory(
            (string)($event->SUBTHEME['id'] ?? ''),
            (string)($event->SUBTHEME ?? '')
        );
    }

    private function createKeywords(SimpleXMLElement $event): string
    {
        return (string)($event->KEYWORD ?? '');
    }

    private function createSource(SimpleXMLElement $event): ?RceEventSource
    {
        $userId = (string)$event['userid'];
        $supply = (string)$event['supply'];
        if (empty($userId) || empty($supply)) {
            return null;
        }
        return new RceEventSource($userId, $supply);
    }

    private function createAddresses(SimpleXMLElement $event): RceEventAddresses
    {
        if (empty($event->ADDRESSLIST) || empty($event->ADDRESSLIST->ADDRESS)) {
            return new RceEventAddresses();
        }

        $location = null;
        $organizer = null;
        foreach ($event->ADDRESSLIST->ADDRESS as $address) {
            $type = strtolower((string)$address['type']);

            $address = new RceEventAddress(
                (string)$address->NAME,
                (string)$address->GEMKEY,
                (string) $address->STREET,
                (string) $address->ZIP,
                (string) $address->CITY,
            );

            if (empty($type)) {
                $location = $address;
                continue;
            }
            if ($type === 'presenter') {
                $organizer = $address;
            }
        }
        return new RceEventAddresses($location, $organizer);
    }

    /**
     * @return RceEventUpload[]
     */
    private function createUploads(SimpleXMLElement $event): array
    {
        if (empty($event->UPLOADLIST) || empty($event->UPLOADLIST->UPLOAD)) {
            return [];
        }

        $uploads = [];

        foreach ($event->UPLOADLIST->UPLOAD as $upload) {
            if (empty((string)$upload->URL)) {
                continue;
            }

            $url = $this->rewriteRceEventUrlsToHttps((string)$upload->URL);

            $uploads[] = new RceEventUpload(
                (string)($upload->NAME ?? ''),
                $url,
                (string)($upload->COPYRIGHT ?? '')
            );
        }

        return $uploads;
    }

    private function rewriteRceEventUrlsToHttps(string $url): string
    {
        $isRceEventUrl = strpos($url, 'www.rce-event.de') !== false;
        if (!$isRceEventUrl) {
            return $url;
        }

        $isHttpUrl = strpos($url, 'http://') !== false;
        if (!$isHttpUrl) {
            return $url;
        }

        return str_replace('http://', 'https://', $url);
    }

    private function replaceLineFeeds(string $text): string
    {
        $text = (string)str_replace(["\r\n", "\n\r", "\r"], "\n", $text);
        $text = preg_replace('/\n{2,}/', '<br><br>', $text);
        return preg_replace('/\n{1,}/', '<br>', $text);
    }

    private function htmlToText(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        return Html2Text::convert($html, [
            'ignore_errors' => true
        ]);
    }

    private function isBlacklistedEventDate(SimpleXMLElement $date): bool
    {
        return !empty($date->BLACKLISTLIST[0]->BLACKLIST) &&
            (string)$date->BLACKLISTLIST[0]->BLACKLIST === self::YES;
    }

    private function createDateTime(
        SimpleXMLElement $xml,
        string $field,
        string $defaultTime
    ): DateTime {
        $date = (string)$xml->STARTDATE;
        if (empty($date)) {
            $empty = new DateTime();
            $empty->setTimestamp(0);
            return $empty;
        }

        $time = (string)$xml->$field;
        if (empty($time)) {
            $time = $defaultTime;
        }

        $datetime = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $date . ' ' . $time
        );
        if ($datetime === false) {
            $this->logger->error(
                'failed to create date from format',
                [
                    'date' => $date,
                    'field' => $field
                ]
            );
            $empty = new DateTime();
            $empty->setTimestamp(0);
            return $empty;
        }

        return $datetime;
    }
}
