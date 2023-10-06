<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Contracts\Queue;

interface QueueableEntity
{
    /**
     * Get the queueable identity for the entity.
     *
     * @return mixed
     */
    public function getQueueableId();
    /**
     * Get the relationships for the entity.
     *
     * @return array
     */
    public function getQueueableRelations();
    /**
     * Get the connection of the entity.
     *
     * @return string|null
     */
    public function getQueueableConnection();
}
