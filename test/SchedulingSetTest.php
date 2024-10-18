<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test;

use Atoolo\EventsCalendar\Scheduling;
use Atoolo\EventsCalendar\SchedulingSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchedulingSet::class)]
class SchedulingSetTest extends TestCase
{
    public function testGetOccurrencesSingle(): void
    {
        $scheduling = (new Scheduling(
            new \DateTime('01.01.2024 12:00'),
            new \DateTime('02.01.2024 22:00'),
        ))->setRRuleFromString(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;COUNT=3',
        );
        $schedulingSet = new SchedulingSet([$scheduling]);
        $this->assertEquals(
            $scheduling->getOccurrences(),
            $schedulingSet->getOccurrences(),
        );
    }

    public function testGetOccurrencesMulti(): void
    {
        $schedulingA = (new Scheduling(
            new \DateTime('01.01.2024 12:00'),
            new \DateTime('02.01.2024 22:00'),
        ))->setRRuleFromString(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;COUNT=2',
        );
        $schedulingB = (new Scheduling(
            new \DateTime('03.01.2024 14:00'),
            new \DateTime('03.01.2024 20:00'),
        ))->setRRuleFromString(
            'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;COUNT=2',
        );
        $schedulingSet = new SchedulingSet();
        $this->assertEmpty($schedulingSet->getOccurrences());
        $schedulingSet->setSchedulings([$schedulingA, $schedulingB]);
        $this->assertEquals(
            $schedulingSet->getOccurrences(true),
            [
                (new Scheduling(
                    new \DateTime('01.01.2024 12:00'),
                    new \DateTime('01.01.2024 23:59:59.999999'),
                )),
                (new Scheduling(
                    new \DateTime('02.01.2024 00:00'),
                    new \DateTime('02.01.2024 22:00'),
                )),
                (new Scheduling(
                    new \DateTime('04.01.2024 14:00'),
                    new \DateTime('04.01.2024 20:00'),
                )),
                (new Scheduling(
                    new \DateTime('08.01.2024 12:00'),
                    new \DateTime('08.01.2024 23:59:59.999999'),
                )),
                (new Scheduling(
                    new \DateTime('09.01.2024 00:00'),
                    new \DateTime('09.01.2024 22:00'),
                )),
                (new Scheduling(
                    new \DateTime('11.01.2024 14:00'),
                    new \DateTime('11.01.2024 20:00'),
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
        $schedulingSet = new SchedulingSet([$scheduling]);
        $this->expectException(\LogicException::class);
        $schedulingSet->getOccurrences();
    }

    public function testIsInfinite(): void
    {
        $finiteSchedulingSet = new SchedulingSet(
            [
                (new Scheduling(
                    new \DateTime('01.01.2024 12:00'),
                    new \DateTime('02.01.2024 22:00'),
                ))->setRRuleFromString(
                    'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;COUNT=2',
                ),
                (new Scheduling(
                    new \DateTime('03.01.2024 14:00'),
                    new \DateTime('03.01.2024 20:00'),
                ))->setRRuleFromString(
                    'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;COUNT=2',
                ),
            ],
        );
        $infiniteSchedulingSet = new SchedulingSet(
            [
                (new Scheduling(
                    new \DateTime('01.01.2024 12:00'),
                    new \DateTime('02.01.2024 22:00'),
                ))->setRRuleFromString(
                    'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;COUNT=2',
                ),
                (new Scheduling(
                    new \DateTime('03.01.2024 14:00'),
                    new \DateTime('03.01.2024 20:00'),
                ))->setRRuleFromString(
                    'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH',
                ),
            ],
        );
        $this->assertFalse($finiteSchedulingSet->isInfinite());
        $this->assertTrue($infiniteSchedulingSet->isInfinite());
    }

    public function testGetterSetter(): void
    {
        $schedulingSet = new SchedulingSet();
        $schedulingSet->addScheduling(
            (new Scheduling(
                new \DateTime('03.01.2024 14:00'),
                new \DateTime('03.01.2024 20:00'),
            ))->setRRuleFromString(
                'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;COUNT=2',
            ),
        );
        $this->assertNotNull($schedulingSet->getRRuleSet());

        $schedulings = [
            (new Scheduling(
                new \DateTime('01.01.2024 12:00'),
                new \DateTime('02.01.2024 22:00'),
            ))->setRRuleFromString(
                'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;COUNT=2',
            ),
        ];
        $schedulingSet->setSchedulings($schedulings);
        $this->assertEquals(
            $schedulings,
            $schedulingSet->getSchedulings(),
        );
    }
}
