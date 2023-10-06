<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Traits;

trait Tappable
{
    /**
     * Call the given Closure with this instance then return the instance.
     *
     * @param  callable|null  $callback
     * @return $this|\Illuminate\Support\HigherOrderTapProxy
     */
    public function tap($callback = null)
    {
        return \Barn2\Plugin\WC_Filters\Helpers::tap($this, $callback);
    }
}
