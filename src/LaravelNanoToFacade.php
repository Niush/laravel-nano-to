<?php

namespace Niush\LaravelNanoTo;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Niush\LaravelNanoTo\Skeleton\SkeletonClass
 */
class LaravelNanoToFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-nano-to';
    }
}
