<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Service\Scheduling;

use Atoolo\EventsCalendar\Dto\Scheduling\Scheduling;
use Atoolo\EventsCalendar\Dto\Scheduling\SchedulingBuilder;
use RRule\RRule;

class SchedulingManager
{
    /**
     * Generates all occurences of a Scheduling by resolving the RRule and/or, optionally,
     * splitting multi-day-schedulings into several single-day-schedulings.
     * An occurence is itself a `Scheduling`-object, that, however,
     * is guaranteed to have no rrule.
     *
     * @param Scheduling $scheduling scheduling from which the occurences will be
     *  generated
     * @param bool $splitMultidayDates if true, schedulings that span over
     *  multiple days will be split into multiple Schedulings that span over a single
     *  day each
     * @param ?\DateTime $from return all occurences after $from (inclusive)
     * @param ?\DateTime $to return all occurences until $to (inclusive)
     * @param null|int<1,max> $limit limit number of returned occurences
     * @throws \InvalidArgumentException if $limit is negative
     * @return \Generator<int,Scheduling>
     */
    public function generateOccurrencesOfScheduling(
        Scheduling $scheduling,
        bool $splitMultidayDates = false,
        ?\DateTime $from = null,
        ?\DateTime $to = null,
        ?int $limit = null,
    ): \Generator {
        $numberOfDaysPerSchedule = ($this->getNumberOfMidnights($scheduling) ?? 0) + 1;
        $dateOccurenceIterable = $scheduling->rRule === null
            ? [clone $scheduling->start]
            : new RRule($scheduling->rRule, $scheduling->start);
        $n = 0;
        foreach ($dateOccurenceIterable as $dateOccurence) {
            if ($from !== null && $dateOccurence < $from) {
                continue;
            }
            if ($to !== null && $dateOccurence > $to) {
                return;
            }
            $currentScheduling = (new SchedulingBuilder())
                ->fromScheduling($scheduling)
                ->setStart($dateOccurence, true)
                ->setRRule(null)
                ->build();

            if ($splitMultidayDates && $numberOfDaysPerSchedule > 1) {
                for ($i = 1; $i <= $numberOfDaysPerSchedule; $i++) {
                    if ($i === 1) {
                        // first day: from start to 23:59
                        yield (new SchedulingBuilder())
                            ->fromScheduling($currentScheduling)
                            ->setEnd((clone $currentScheduling->start)->setTime(23, 59, 59, 999999))
                            ->build();
                    } elseif ($i === $numberOfDaysPerSchedule) {
                        // last day: from 00:00 to end
                        /** @var \DateTime $end */
                        $end = $currentScheduling->end;
                        $newStart = (clone $end)->setTime(0, 0);
                        if ($to !== null && $newStart > $to) {
                            return;
                        }
                        yield (new SchedulingBuilder())
                            ->fromScheduling($currentScheduling)
                            ->setStart($newStart)
                            ->build();
                    } else {
                        // inbetween day: from 00:00 to 23:59
                        $nextMorning = (clone $currentScheduling->start)
                            ->add(new \DateInterval('P' . ($i - 1) . 'D'))
                            ->setTime(0, 0);
                        if ($to !== null && $nextMorning > $to) {
                            return;
                        }
                        yield (new SchedulingBuilder())
                            ->fromScheduling($currentScheduling)
                            ->setStart($nextMorning)
                            ->setEnd((clone $nextMorning)->setTime(23, 59, 59, 999999))
                            ->setIsFullDay(true)
                            ->build();
                    }
                    $n++;
                    if ($limit !== null && $n >= $limit) {
                        return;
                    }
                }
            } else {
                yield $currentScheduling;
                $n++;
                if ($limit !== null && $n >= $limit) {
                    return;
                }
            }
        }
        return;
    }

    /**
     * Generates the occurences of all given schedulings in  chronological order. An occurence
     * is itself a `Scheduling`-object, that, however, is guaranteed to have no rrule.
     * @see self::generateOccurrencesOfScheduling()
     *
     * @param Scheduling[] $schedulings schedulings from which the occurences will be
     *  generated
     * @param bool $splitMultidayDates if true, schedulings that span over
     *  multiple days will be split into multiple Schedulings that span over a single
     *  day each
     * @param ?\DateTime $from return all occurences after $from (inclusive)
     * @param ?\DateTime $to return all occurences until $to (inclusive)
     * @param null|int<1,max> $limit limit number of returned occurences
     * @throws \InvalidArgumentException if $limit is negative
     * @return \Generator<int,Scheduling>
     */
    public function generateOccurrencesOfSchedulings(
        array $schedulings,
        bool $splitMultidayDates = false,
        ?\DateTime $from = null,
        ?\DateTime $to = null,
        ?int $limit = null,
    ): \Generator {
        if (count($schedulings) === 0) {
            return;
        } elseif (count($schedulings) === 1) {
            yield from $this->generateOccurrencesOfScheduling(
                $schedulings[0],
                $splitMultidayDates,
                $from,
                $to,
                $limit,
            );
        } else {
            $priorityQueue = new \SplPriorityQueue();
            foreach ($schedulings as $scheduling) {
                $generator = $this->generateOccurrencesOfScheduling(
                    $scheduling,
                    $splitMultidayDates,
                    $from,
                    $to,
                    $limit,
                );
                $priorityQueue->insert(
                    ['generator' => $generator, 'value' => $generator->current()],
                    -$generator->current()->start->getTimestamp(),
                );
            }
            while (!$priorityQueue->isEmpty()) {
                /** @var array{generator: \Generator<int,Scheduling>, value: Scheduling} $item */
                $item = $priorityQueue->extract();
                $value = $item['value'];
                yield $value;
                $generator = $item['generator'];
                $generator->next();
                if ($generator->valid()) {
                    $priorityQueue->insert(
                        ['generator' => $generator, 'value' => $generator->current()],
                        -$generator->current()->start->getTimestamp(),
                    );
                }
            }
        }
        return;
    }

    /**
     * Copies all occurences of `generateOccurrencesOfScheduling` into an array
     * @see self::generateOccurrencesOfScheduling()
     *
     * @param Scheduling $scheduling
     * @param bool $splitMultidayDates if true, schedulings that span over
     *  multiple days will be split into multiple Schedulings that span over a single
     *  day each
     * @param ?\DateTime $from return all occurences after $from (inclusive)
     * @param ?\DateTime $to return all occurences until $to (inclusive)
     * @param null|int<1,max> $limit limit number of returned occurences
     * @throws \LogicException if $limit/$to is not set and there are infinite occurences
     * @throws \InvalidArgumentException if $limit is negative
     * @return Scheduling[]
     */
    public function getAllOccurrencesOfScheduling(
        Scheduling $scheduling,
        bool $splitMultidayDates = false,
        ?\DateTime $from = null,
        ?\DateTime $to = null,
        ?int $limit = null,
    ): array {
        if ($to === null && $limit === null && $this->isInfinite($scheduling)) {
            throw new \LogicException('Cannot get all occurrences of an infinite recurrence rule.');
        }
        return iterator_to_array(
            $this->generateOccurrencesOfScheduling(
                $scheduling,
                $splitMultidayDates,
                $from,
                $to,
                $limit,
            ),
        );
    }

    /**
     * Copies all occurences of `generateOccurrencesOfSchedulings` into an array
     * @see self::generateOccurrencesOfSchedulings()
     *
     * @param Scheduling[] $schedulings
     * @param bool $splitMultidayDates if true, schedulings that span over
     *  multiple days will be split into multiple Schedulings that span over a single
     *  day each
     * @param ?\DateTime $from return all occurences after $from (inclusive)
     * @param ?\DateTime $to return all occurences until $to (inclusive)
     * @param null|int<1,max> $limit limit number of returned occurences
     * @throws \LogicException if $limit/$to is not set and there are infinite occurences
     * @throws \InvalidArgumentException if $limit is negative
     * @return Scheduling[]
     */
    public function getAllOccurrencesOfSchedulings(
        array $schedulings,
        bool $splitMultidayDates = false,
        ?\DateTime $from = null,
        ?\DateTime $to = null,
        ?int $limit = null,
    ): array {
        if ($to === null && $limit === null) {
            foreach ($schedulings as $scheduling) {
                if ($this->isInfinite($scheduling)) {
                    throw new \LogicException('Cannot get all occurrences of an infinite recurrence rule.');
                }
            }
        }
        return iterator_to_array(
            $this->generateOccurrencesOfSchedulings(
                $schedulings,
                $splitMultidayDates,
                $from,
                $to,
                $limit,
            ),
        );
    }

    public function isInfinite(Scheduling $scheduling): bool
    {
        return $scheduling->rRule !== null &&
            (new RRule($scheduling->rRule, $scheduling->start))->isInfinite();
    }

    /**
     * @phpstan-assert-if-true !null $this->end
     */
    public function isMultiDay(Scheduling $scheduling): ?bool
    {
        if ($scheduling->end === null) {
            return null;
        }
        return $scheduling->start->format('Y-m-d') !== $scheduling->end->format('Y-m-d');
    }

    private function getNumberOfMidnights(
        Scheduling $scheduling,
    ): ?int {
        if ($scheduling->end === null) {
            return null;
        }
        $mindnights = (clone $scheduling->start)->setTime(0, 0, 0, 0)
            ->diff(
                (clone $scheduling->end)->setTime(23, 59, 59, 999999),
            )->days;
        $mindnights = $mindnights === false ? 0 : $mindnights;
        return $mindnights;
    }
}
