<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar;

use RRule\RSet;

class SchedulingSet
{
    /**
     * @var null|RSet<\DateTime>
     */
    private ?RSet $rRuleSet = null;

    /**
     * @param Scheduling[] $schedulings
     */
    public function __construct(
        protected array $schedulings = [],
    ) {
        $this->recalcRRuleSet();
    }

    private function recalcRRuleSet(): void
    {
        foreach ($this->schedulings as $scheduling) {
            if ($scheduling->hasRRule()) {
                if ($this->rRuleSet === null) {
                    $this->rRuleSet = new RSet();
                }
                $this->rRuleSet->addRRule($scheduling->getRRule());
            }
        }
    }

    /**
     * @return Scheduling[]
     */
    public function getSchedulings(): array
    {
        return $this->schedulings;
    }

    /**
     * @param Scheduling[] $schedulings
     */
    public function setSchedulings(array $schedulings): self
    {
        $this->schedulings = $schedulings;
        $this->recalcRRuleSet();
        return $this;
    }

    public function addScheduling(Scheduling $scheduling): self
    {
        $this->schedulings[] = $scheduling;
        if ($scheduling->hasRRule()) {
            if ($this->rRuleSet === null) {
                $this->rRuleSet = new RSet();
            }
            $this->rRuleSet->addRRule($scheduling->getRRule());
        }
        return $this;
    }

    /**
     * @return null|RSet<\DateTime>
     */
    public function getRRuleSet(): ?RSet
    {
        return $this->rRuleSet;
    }

    /**
     * Generates the occurences of all schedulings in order. An occurence
     * is itself a `Scheduling`-object, that, however, is guaranteed to have no rrule.
     * @see Scheduling::generateOccurrences()
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
        if (count($this->schedulings) === 0) {
            return;
        } elseif (count($this->schedulings) === 1) {
            yield from $this->schedulings[0]->generateOccurrences(
                $splitMultidayDates,
                $from,
                $to,
                $limit,
            );
        } else {
            $priorityQueue = new \SplPriorityQueue();
            foreach ($this->schedulings as $scheduling) {
                $generator = $scheduling->generateOccurrences(
                    $splitMultidayDates,
                    $from,
                    $to,
                    $limit,
                );
                $priorityQueue->insert(
                    ['generator' => $generator, 'value' => $generator->current()],
                    -$generator->current()->getStart()->getTimestamp(),
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
                        -$generator->current()->getStart()->getTimestamp(),
                    );
                }
            }
        }
        return;
    }

    /**
     * Copies all occurences of `generateOccurences` into an array
     * @see SchedulingSet::generateOccurrences()
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
        if (($to === null || $limit === null) && $this->isInfinite()) {
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
        foreach ($this->schedulings as $scheduling) {
            if ($scheduling->isInfinite()) {
                return true;
            }
        }
        return false;
    }
}
