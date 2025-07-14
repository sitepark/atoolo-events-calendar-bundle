<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Dto\RceEvent;

enum RceEventSpecialFeature: string
{
    case HIGHLIGHT = 'highlight';
    case CHILDREN = 'children';
    case TEENAGER = 'teenager';
    case ADULTS = 'adults';
    case SENIOR = 'senior';
    case FAMILY = 'family';
    case BARRIER_FREE = 'barrier-free';
    case BAD_WEATHER = 'bad-weather';
    case TIGHT_BUDGET = 'tight-budget';
    case ONLINE = 'online';
    case ONSITE = 'onsite';
}
