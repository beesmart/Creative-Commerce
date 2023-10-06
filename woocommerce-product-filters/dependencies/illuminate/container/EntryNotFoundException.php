<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Container;

use Exception;
use Barn2\Plugin\WC_Filters\Dependencies\Psr\Container\NotFoundExceptionInterface;
class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    //
}
