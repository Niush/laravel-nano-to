<?php

namespace Niush\NanoTo;

use Illuminate\Support\Facades\Facade;

/**
 * @see Niush\NanoTo\NanoTo
 */
class NanoToFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'nano-to';
    }
}
