<?php

namespace Ocw\AgGrid\Facades;

use Illuminate\Support\Facades\Facade;

class AgGrid extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'aggrid';
    }
}
