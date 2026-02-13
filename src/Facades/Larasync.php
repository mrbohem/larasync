<?php

namespace MrBohem\Larasync\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MrBohem\Larasync\Larasync
 */
class Larasync extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MrBohem\Larasync\Larasync::class;
    }
}
