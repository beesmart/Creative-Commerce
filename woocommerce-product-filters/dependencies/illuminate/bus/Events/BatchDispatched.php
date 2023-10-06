<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Bus\Events;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Bus\Batch;
class BatchDispatched
{
    /**
     * The batch instance.
     *
     * @var \Illuminate\Bus\Batch
     */
    public $batch;
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Bus\Batch  $batch
     * @return void
     */
    public function __construct(Batch $batch)
    {
        $this->batch = $batch;
    }
}
