<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar;

use DateTime;
use RRule\RRule;

class Scheduling
{
    private bool $isFullDay = false;

    private bool $hasStartTime = true;

    private bool $hasEndTime = true;

    /**
     * @var null|RRule<\DateTime>
     */
    private ?RRule $rRule = null;

    private ?bool $isMultiDay = null;

    public function __construct(
        private \DateTime $start,
        private ?\DateTime $end = null,
    ) {}

    public function __clone(): void
    {
        $this->start = clone $this->start;
        $this->end = $this->end !== null
            ? clone $this->end
            : null;
        if ($this->rRule !== null) {
            $this->rRule = new RRule(
                array_merge(
                    $this->rRule->getRule(),
                    ['DTSTART' => $this->start],
                ),
            );
        }
    }

    /**
     * Generates all occurences by resolving the RRule and/or, optionally,
     * splitting multi-day-schedulings into several single-day-schedulings.
     * An occurence is itself a `Scheduling`-object, that, however,
     * is guaranteed to have no rrule.
     *
     * @param bool $splitMultidayDates if true, schedulings that span over
     *  multiple days will be split into multiple Schedulings that span over a single
     *  day each
     * @param ?\DateTime $from return all occurences after $from (inclusive)
     * @param ?\DateTime $to return all occurences until $to (inclusive)
     * @param null|int<1,max> $limit limit number of returned occurences
     * @throws \InvalidArgumentException if $limit is negative
     * @return \Generator<int,Scheduling>
     */
    public function generateOccurrences(
        bool $splitMultidayDates = false,
        ?\DateTime $from = null,
        ?\DateTime $to = null,
        ?int $limit = null,
    ): \Generator {
        $numberOfDaysPerSchedule = ($this->getNumberOfMidnights() ?? 0) + 1;
        $dateOccurenceIterable = $this->rRule === null
            ? [clone $this->getStart()]
            : $this->rRule;
        $n = 0;
        foreach ($dateOccurenceIterable as $dateOccurence) {
            if ($from !== null && $dateOccurence < $from) {
                continue;
            }
            if ($to !== null && $dateOccurence > $to) {
                return;
            }
            $scheduling = (clone $this)
                ->setStart($dateOccurence)
                ->removeRRule();

            if ($splitMultidayDates && $numberOfDaysPerSchedule > 1) {
                for ($i = 1; $i <= $numberOfDaysPerSchedule; $i++) {
                    if ($i === 1) {
                        // first day: from start to 23:59
                        yield (clone $scheduling)->setEnd(
                            (clone $scheduling->getStart())->setTime(23, 59, 59, 999999),
                        );
                    } elseif ($i === $numberOfDaysPerSchedule) {
                        // last day: from 00:00 to end
                        /** @var \DateTime $end */
                        $end = $scheduling->getEnd();
                        $newStart = (clone $end)->setTime(0, 0);
                        if ($to !== null && $newStart > $to) {
                            return;
                        }
                        yield (clone $scheduling)->setStart(
                            $newStart,
                            false,
                        );
                    } else {
                        // inbetween day: from 00:00 to 23:59
                        $nextMorning = (clone $scheduling->getStart())
                            ->add(new \DateInterval('P' . ($i - 1) . 'D'))
                            ->setTime(0, 0);
                        if ($to !== null && $nextMorning > $to) {
                            return;
                        }
                        yield (clone $scheduling)
                            ->setStart($nextMorning)->setEnd(
                                (clone $nextMorning)->setTime(23, 59, 59, 999999),
                            )->setIsFullDay(true);
                    }
                    $n++;
                    if ($limit !== null && $n >= $limit) {
                        return;
                    }
                }
            } else {
                yield $scheduling;
                $n++;
                if ($limit !== null && $n >= $limit) {
                    return;
                }
            }
        }
        return;
    }

    /**
     * Copies all occurences of `generateOccurences` into an array
     * @see Scheduling::generateOccurrences()
     *
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
    public function getOccurrences(
        bool $splitMultidayDates = false,
        ?\DateTime $from = null,
        ?\DateTime $to = null,
        ?int $limit = null,
    ): array {
        if ($to === null && $limit === null && $this->isInfinite()) {
            throw new \LogicException('Cannot get all occurrences of an infinite recurrence rule.');
        }
        return iterator_to_array(
            $this->generateOccurrences(
                $splitMultidayDates,
                $from,
                $to,
                $limit,
            ),
        );
    }

    public function isInfinite(): bool
    {
        return $this->rRule?->isInfinite() ?? false;
    }

    private function getNumberOfMidnights(): ?int
    {
        if ($this->end === null) {
            return null;
        }
        $mindnights = (clone $this->start)->setTime(0, 0, 0, 0)
            ->diff(
                (clone $this->end)->setTime(23, 59, 59, 999999),
            )->days;
        $mindnights = $mindnights === false ? 0 : $mindnights;
        return $mindnights;
    }

    /**
     * Checks whether the start and end datetime span over
     * multiple days. Returns `null` if end datetime is not set.
     * @phpstan-assert-if-true !null $this->getEnd()
     */
    public function isMultiDay(): ?bool
    {
        if ($this->end === null) {
            return null;
        }
        if ($this->isMultiDay === null) {
            $this->isMultiDay = $this->start->format('Y-m-d') !== $this->end->format('Y-m-d');
        }
        return $this->isMultiDay;
    }

    /**
     * Returns the Interval between start end end datetime. Returns
     * `null` if end datetime is not set.
     */
    public function getInterval(): ?\DateInterval
    {
        return $this->end !== null
            ? $this->start->diff($this->end)
            : null;
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start, bool $keepRelativeEnd = true): self
    {
        $startPrev = $this->start;
        $this->start = $start;
        $this->isMultiDay = null;
        if ($this->rRule !== null && $this->start !== $startPrev) {
            $this->rRule = new RRule(
                array_merge(
                    $this->rRule->getRule(),
                    ['DTSTART' => $this->start],
                ),
            );
        }
        if ($keepRelativeEnd && $this->end !== null) {
            $prevStartEndDiff =  $this->end->getTimestamp() - $startPrev->getTimestamp();
            $this->end = (new \DateTime())->setTimestamp(
                $this->start->getTimestamp() + $prevStartEndDiff,
            );
        }
        return $this;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    public function setEnd(?\DateTime $end): self
    {
        $this->end = $end;
        $this->isMultiDay = null;
        return $this;
    }

    /**
     * @phpstan-assert-if-true !null $this->getEnd()
     */
    public function hasEnd(): bool
    {
        return $this->end !== null;
    }

    public function isFullDay(): bool
    {
        return $this->isFullDay;
    }

    public function setIsFullDay(bool $isFullDay): self
    {
        $this->isFullDay = $isFullDay;
        return $this;
    }

    public function hasStartTime(): bool
    {
        return $this->hasStartTime;
    }

    public function setHasStartTime(bool $hasStartTime): self
    {
        $this->hasStartTime = $hasStartTime;
        return $this;
    }

    public function hasEndTime(): bool
    {
        return $this->hasEndTime;
    }

    public function setHasEndTime(bool $hasEndTime): self
    {
        $this->hasEndTime = $hasEndTime;
        return $this;
    }

    /**
     * @return null|RRule<\DateTime>
     */
    public function getRRule(): ?RRule
    {
        return $this->rRule;
    }

    public function removeRRule(): self
    {
        $this->rRule = null;
        return $this;
    }

    /**
     * @phpstan-assert-if-true !null $this->getRRule()
     */
    public function hasRRule(): bool
    {
        return $this->rRule !== null;
    }

    /**
     * Set an rrule  according to RFC 5545 (ignoring
     * the field `DTSTART` since this is always the `$start`
     * datetime of this Scheduling)
     */
    public function setRRuleFromString(?string $rRule): self
    {
        if ($rRule !== null) {
            $this->rRule = new RRule(
                $rRule,
                $this->start,
            );
        }
        return $this;
    }
}
