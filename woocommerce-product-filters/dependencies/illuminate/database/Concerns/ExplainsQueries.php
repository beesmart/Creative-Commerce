<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Concerns;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\Collection;
trait ExplainsQueries
{
    /**
     * Explains the query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function explain()
    {
        $sql = $this->toSql();
        $bindings = $this->getBindings();
        $explanation = $this->getConnection()->select('EXPLAIN ' . $sql, $bindings);
        return new Collection($explanation);
    }
}
