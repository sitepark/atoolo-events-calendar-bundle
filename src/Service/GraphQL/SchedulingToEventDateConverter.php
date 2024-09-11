<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\GraphQL;

use Atoolo\EventsCalendar\Service\GraphQL\Types\EventDate;

/**
 * @phpstan-type RawScheduling array{
 *     type?: string,
 *     isFullDay?: boolean,
 *     beginDate?: int,
 *     endDate?: int,
 *     beginTime?: string,
 *     endTime? : string,
 *     repetition?: RawSchedulingRepitition
 * }
 * @phpstan-type RawSchedulingRepitition array{
 *     count?: int,
 *     dow?: string,
 *     oom?: int,
 *     moy?: int,
 *     dom?: int,
 *     date?: int,
 *     interval?: int,
 * }
 */
class SchedulingToEventDateConverter
{
    /**
     * @param RawScheduling $rawScheduling
     */
    public function rawSchedulingToEventDate(
        array $rawScheduling,
    ): ?EventDate {
        $startDateTime = $this->getStartDateTimeFromRawScheduling($rawScheduling);
        if ($startDateTime === null) {
            return null;
        }
        return new EventDate(
            $startDateTime,
            $this->getEndDateTimeFromRawScheduling($rawScheduling),
            $this->getRecurrenceRuleTimeFromRawScheduling($rawScheduling),
            ($rawScheduling['isFullDay'] ?? false) === true,
            isset($rawScheduling['beginTime']),
            isset($rawScheduling['endTime']),
        );
    }

    /**
     * @param RawScheduling $rawScheduling
     */
    public function getStartDateTimeFromRawScheduling(
        array $rawScheduling,
    ): ?\DateTime {
        $beginDateTimestamp = $rawScheduling['beginDate'] ?? null;
        if (!is_int($beginDateTimestamp)) {
            return null;
        }
        $beginTimeSeconds = isset($rawScheduling['beginTime'])
            ? $this->timeStringToSeconds($rawScheduling['beginTime'])
            : 0;
        return (new \DateTime())
            ->setTimestamp($beginDateTimestamp + $beginTimeSeconds);
    }

    /**
     * @param RawScheduling $rawScheduling
     */
    public function getEndDateTimeFromRawScheduling(
        array $rawScheduling,
    ): ?\DateTime {
        $endDateTimestamp = $rawScheduling['endDate'] ?? $rawScheduling['beginDate'] ?? null;
        if (!is_int($endDateTimestamp)) {
            return null;
        }
        $endTimeSeconds = isset($rawScheduling['endTime'])
            ? $this->timeStringToSeconds($rawScheduling['endTime'])
            : 0;
        return (new \DateTime())
            ->setTimestamp($endDateTimestamp + $endTimeSeconds);
    }

    /**
     * @param RawScheduling $rawScheduling
     */
    public function getRecurrenceRuleTimeFromRawScheduling(
        array $rawScheduling,
    ): ?string {
        $type = $rawScheduling['type'] ?? null;
        $rrule = [];
        switch ($type) {
            case 'daily':
                $rrule['FREQ'] = 'DAILY';
                break;
            case 'weekly':
                $rrule['FREQ'] = 'WEEKLY';
                break;
            case 'monthlyByDay':
            case 'monthlyByOccurrence':
                $rrule['FREQ'] = 'MONTHLY';
                break;
            case 'yearlyByMonth':
            case 'yearlyByOccurrence':
                $rrule['FREQ'] = 'YEARLY';
                break;
            default:
                return null;
        }
        $rrule['INTERVAL'] = $rawScheduling['repetition']['interval'] ?? 1;
        if (is_int($rawScheduling['repetition']['date'] ?? null)) {
            $rrule['UNTIL'] = date('Ymd\THis\Z', $rawScheduling['repetition']['date']);
        }
        if (is_int($rawScheduling['repetition']['count'] ?? null)) {
            $rrule['COUNT'] = $rawScheduling['repetition']['count'];
        }
        if (is_string($rawScheduling['repetition']['dow'] ?? null)) {
            $rrule['BYDAY'] = str_replace(
                ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
                ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'],
                $rawScheduling['repetition']['dow'],
            );
        }
        if (is_int($rawScheduling['repetition']['oom'] ?? null) && isset($rrule['BYDAY'])) {
            $rrule['BYDAY'] = $rawScheduling['repetition']['oom'] . $rrule['BYDAY'];
        }
        if (is_int($rawScheduling['repetition']['dom'] ?? null)) {
            $rrule['BYMONTHDAY'] = $rawScheduling['repetition']['dom'];
        }
        if (is_int($rawScheduling['repetition']['moy'] ?? null)) {
            $rrule['BYMONTH'] = $rawScheduling['repetition']['moy'] + 1;
        }
        return join(';', array_map(fn($k, $v) => $k . '=' . $v, array_keys($rrule), $rrule));
    }

    protected function timeStringToSeconds(
        string $time,
    ): int {
        $pattern = '/^([0-2]{0,1}[0-9]):([0-5][0-9])$/';
        if (preg_match($pattern, $time, $matches)) {
            return intval($matches[1]) * 60 * 60
                + intval($matches[2]) * 60;
        }
        return 0;
    }
}
