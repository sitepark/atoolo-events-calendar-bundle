<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Test\Service\GraphQL\Resolver\Resource;

use Atoolo\EventsCalendar\Scheduling;
use Atoolo\EventsCalendar\Service\GraphQL\Resolver\SchedulingResolver;
use Atoolo\EventsCalendar\Test\Constraint\EqualsRRule;
use Atoolo\EventsCalendar\Test\Constraint\IsRRule;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchedulingResolver::class)]
class SchedulingResolverTest extends TestCase
{
    private SchedulingResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new SchedulingResolver();
    }

    public function testGetRRule(): void
    {
        $expected = 'FREQ=YEARLY;INTERVAL=6;BYMONTH=2;BYMONTHDAY=18';
        $scheduling = (new Scheduling(
            new \DateTime(),
        ))->setRRuleFromString(
            $expected,
        );
        $actual = $this->resolver->getRRule(
            $scheduling,
        );
        $this->assertThat($actual, new IsRRule());
        $this->assertThat($actual, new EqualsRRule($expected));
    }

    public function testGetRRuleNull(): void
    {
        $scheduling = new Scheduling(
            new \DateTime(),
        );
        $this->assertNull(
            $this->resolver->getRRule(
                $scheduling,
            ),
        );
    }
}
