<?php

declare(strict_types=1);

namespace Atoolo\EventsCalendar\Dto\RceEvent;

/**
 * @codeCoverageIgnore
 */
class RceEventTheme
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {
    }

    public function getKey(): string
    {
        $name = strtolower($this->name);
        $name = str_replace(
            ['ä', 'ö', 'ü'],
            ['ae', 'oe', 'ue'],
            $name
        );
        $name = preg_replace('/[^a-z0-9]/', '-', $name) ?: $name;
        $name = preg_replace('/-+/', '-', $name) ?: $name;

        return $name;
    }
}
