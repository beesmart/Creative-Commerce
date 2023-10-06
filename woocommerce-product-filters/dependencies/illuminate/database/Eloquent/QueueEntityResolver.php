<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Contracts\Queue\EntityNotFoundException;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Contracts\Queue\EntityResolver as EntityResolverContract;
class QueueEntityResolver implements EntityResolverContract
{
    /**
     * Resolve the entity for the given ID.
     *
     * @param  string  $type
     * @param  mixed  $id
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Queue\EntityNotFoundException
     */
    public function resolve($type, $id)
    {
        $instance = (new $type())->find($id);
        if ($instance) {
            return $instance;
        }
        throw new EntityNotFoundException($type, $id);
    }
}
