<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support;

use Barn2\Plugin\WC_Filters\Dependencies\Carbon\Carbon as BaseCarbon;
use Barn2\Plugin\WC_Filters\Dependencies\Carbon\CarbonImmutable as BaseCarbonImmutable;
class Carbon extends BaseCarbon
{
    /**
     * {@inheritdoc}
     */
    public static function setTestNow($testNow = null)
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }
}
