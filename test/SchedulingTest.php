<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test;

use Atoolo\EventsCalendar\Scheduling;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Scheduling::class)]
class SchedulingTest extends TestCase
{
    public function testGetOccurrences(): void
    {
        // multiday, every monday, 3 times
        $scheduling = (new Scheduling(
            new \DateTime('01.01.2024 12:00'),
            new \DateTime('01.01.2024 22:00'),
        ))->setRRuleFromString(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;COUNT=3',
        );
        $this->assertEquals(
            $scheduling->getOccurrences(),
            [
                (new Scheduling(
                    new \DateTime('01.01.2024 12:00'),
                    new \DateTime('01.01.2024 22:00'),
                )),
                (new Scheduling(
                    new \DateTime('08.01.2024 12:00'),
                    new \DateTime('08.01.2024 22:00'),
                )),
                (new Scheduling(
                    new \DateTime('15.01.2024 12:00'),
                    new \DateTime('15.01.2024 22:00'),
                )),
            ],
        );
    }

    public function testGetOccurrencesWithoutEnd(): void
    {
        // multiday, every monday, 3 times
        $scheduling = (new Scheduling(
            new \DateTime('01.01.2024 12:00'),
        ))->setRRuleFromString(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;COUNT=3',
        );
        $this->assertEquals(
            $scheduling->getOccurrences(),
            [
                (new Scheduling(
                    new \DateTime('01.01.2024 12:00'),
                )),
                (new Scheduling(
                    new \DateTime('08.01.2024 12:00'),
                )),
                (new Scheduling(
                    new \DateTime('15.01.2024 12:00'),
                )),
            ],
        );
    }

    public function testGetOccurrencesWithoutRRule(): void
    {
        // multiday, every monday, 3 times
        $scheduling = (new Scheduling(
            new \DateTime('01.01.2024 12:00'),
        ));
        $this->assertEquals(
            $scheduling->getOccurrences(),
            [
                (new Scheduling(
                    new \DateTime('01.01.2024 12:00'),
                )),
            ],
        );
    }

    public function testGetOccurrencesSplitMultiday(): void
    {
        // multiday, every monday, 2 times
        $scheduling = (new Scheduling(
            new \DateTime('01.01.2024 12:00'),
            new \DateTime('03.01.2024 22:00'),
        ))->setRRuleFromString(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;COUNT=2',
        );
        $this->assertEquals(
            $scheduling->getOccurrences(true),
            [
                (new Scheduling(
                    new \DateTime('01.01.2024 12:00'),
                    new \DateTime('01.01.2024 23:59:59.999999'),
                )),
                (new Scheduling(
                    new \DateTime('02.01.2024 00:00'),
                    new \DateTime('02.01.2024 23:59:59.999999'),
                ))->setIsFullDay(true),
                (new Scheduling(
                    new \DateTime('03.01.2024 00:00'),
                    new \DateTime('03.01.2024 22:00'),
                )),
                (new Scheduling(
                    new \DateTime('08.01.2024 12:00'),
                    new \DateTime('08.01.2024 23:59:59.999999'),
                )),
                (new Scheduling(
                    new \DateTime('09.01.2024 00:00'),
                    new \DateTime('09.01.2024 23:59:59.999999'),
                ))->setIsFullDay(true),
                (new Scheduling(
                    new \DateTime('10.01.2024 00:00'),
                    new \DateTime('10.01.2024 22:00'),
                )),
            ],
        );
    }


    public function testGetOccurrencesFromTo(): void
    {
        // multiday, every monday
        $scheduling = (new Scheduling(
            new \DateTime('01.01.2024 12:00'),
            new \DateTime('03.01.2024 22:00'),
        ))->setRRuleFromString(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO',
        );
        $from = new \DateTime('02.01.2024');
        $to = new \DateTime('16.01.2024');
        $this->assertEquals(
            $scheduling->getOccurrences(false, $from, $to),
            [
                (new Scheduling(
                    new \DateTime('08.01.2024 12:00'),
                    new \DateTime('10.01.2024 22:00'),
                )),
                (new Scheduling(
                    new \DateTime('15.01.2024 12:00'),
                    new \DateTime('17.01.2024 22:00'),
                )),
            ],
        );
        $to = new \DateTime('08.01.2024 23:59:59.999999');
        $this->assertEquals(
            $scheduling->getOccurrences(true, $from, $to),
            [
                (new Scheduling(
                    new \DateTime('08.01.2024 12:00'),
                    new \DateTime('08.01.2024 23:59:59.999999'),
                )),
            ],
        );
        $to = new \DateTime('09.01.2024 23:59:59.999999');
        $this->assertEquals(
            $scheduling->getOccurrences(true, $from, $to),
            [
                (new Scheduling(
                    new \DateTime('08.01.2024 12:00'),
                    new \DateTime('08.01.2024 23:59:59.999999'),
                )),
                (new Scheduling(
                    new \DateTime('09.01.2024 00:00'),
                    new \DateTime('09.01.2024 23:59:59.999999'),
                ))->setIsFullDay(true),
            ],
        );
    }

    public function testGetOccurrencesLimit(): void
    {
        // multiday, every monday
        $scheduling = (new Scheduling(
            new \DateTime('01.01.2024 12:00'),
            new \DateTime('02.01.2024 22:00'),
        ))->setRRuleFromString(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO',
        );
        $limit = 2;
        $this->assertEquals(
            $scheduling->getOccurrences(false, null, null, $limit),
            [
                (new Scheduling(
                    new \DateTime('01.01.2024 12:00'),
                    new \DateTime('02.01.2024 22:00'),
                )),
                (new Scheduling(
                    new \DateTime('08.01.2024 12:00'),
                    new \DateTime('09.01.2024 22:00'),
                )),
            ],
        );
        $this->assertEquals(
            $scheduling->getOccurrences(true, null, null, $limit),
            [
                (new Scheduling(
                    new \DateTime('01.01.2024 12:00'),
                    new \DateTime('01.01.2024 23:59:59.999999'),
                )),
                (new Scheduling(
                    new \DateTime('02.01.2024 00:00'),
                    new \DateTime('02.01.2024 22:00'),
                )),
            ],
        );
    }

    public function testGetOccurrencesInfinite(): void
    {
        $scheduling = (new Scheduling(
            new \DateTime('01.01.2024 12:00'),
            new \DateTime('01.01.2024 22:00'),
        ))->setRRuleFromString(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO',
        );
        $this->expectException(\LogicException::class);
        $scheduling->getOccurrences();
    }


    public function testIsMultiDay(): void
    {
        $singleDayScheduling = new Scheduling(
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-01'),
        );
        $multiDayScheduling = new Scheduling(
            new \DateTime('2024-01-01'),
            new \DateTime('2024-01-03'),
        );
        $withoutEndScheduling = new Scheduling(
            new \DateTime('2024-01-01'),
        );
        $this->assertFalse($singleDayScheduling->isMultiDay());
        $this->assertTrue($multiDayScheduling->isMultiDay());
        $this->assertNull($withoutEndScheduling->isMultiDay());
    }

    public function testIsInfinite(): void
    {
        $finiteScheduling = (new Scheduling(
            new \DateTime('2024-01-01'),
        ))->setRRuleFromString(
            'FREQ=DAILY;INTERVAL=1;COUNT=1',
        );
        $infiniteScheduling = (new Scheduling(
            new \DateTime('2024-01-01'),
        ))->setRRuleFromString(
            'FREQ=DAILY;INTERVAL=1',
        );
        $this->assertFalse($finiteScheduling->isInfinite());
        $this->assertTrue($infiniteScheduling->isInfinite());
    }

    public function testGetterSetter(): void
    {
        $rrule = 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO';
        $isFullDay = true;
        $hasStartTime = false;
        $hasEndTime = false;
        $scheduling = (new Scheduling(
            new \DateTime('01.01.2024 00:00'),
            new \DateTime('01.01.2024 00:00'),
        ))
            ->setEnd(new \DateTime('02.01.2024 00:00'))
            ->setStart(new \DateTime('01.02.2024 00:00')) //also effects end
            ->setIsFullDay($isFullDay)
            ->setHasStartTime($hasStartTime)
            ->setHasEndTime($hasEndTime)
            ->setRRuleFromString($rrule);
        $this->assertEquals(
            $isFullDay,
            $scheduling->isFullDay(),
        );
        $this->assertEquals(
            $hasStartTime,
            $scheduling->hasStartTime(),
        );
        $this->assertEquals(
            $hasEndTime,
            $scheduling->hasEndTime(),
        );
        $this->assertEquals(
            new \DateTime('01.02.2024 00:00'),
            $scheduling->getStart(),
        );
        $this->assertEquals(
            new \DateTime('02.02.2024 00:00'),
            $scheduling->getEnd(),
        );
        $this->assertTrue($scheduling->hasEnd());
        $this->assertTrue($scheduling->hasRRule());
        $this->assertEquals(
            $scheduling->getRRule()->rfcString(false),
            "DTSTART:20240201T000000\nRRULE:FREQ=WEEKLY;BYDAY=MO",
        );
    }

    public function testGetInterval(): void
    {
        $a = new \DateTime('01.01.2024 14:30:30');
        $b = new \DateTime('04.06.2025 22:00');
        $schedulingA = new Scheduling($a);
        $this->assertNull($schedulingA->getInterval());
        $schedulingB = new Scheduling($a, $b);
        $this->assertEquals(
            $schedulingB->getInterval(),
            $a->diff($b),
        );
    }
}
