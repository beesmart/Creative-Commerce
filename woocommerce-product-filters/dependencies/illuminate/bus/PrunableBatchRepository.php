<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Bus;

use DateTimeInterface;
interface PrunableBatchRepository extends BatchRepository
{
    /**
     * Prune all of the entries older than the given date.
     *
     * @param  \DateTimeInterface  $before
     * @return int
     */
    public function prune(DateTimeInterface $before);
}