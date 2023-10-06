<?php

declare (strict_types=1);
namespace Barn2\Plugin\WC_Filters\Dependencies\Doctrine\Inflector;

class NoopWordInflector implements WordInflector
{
    public function inflect(string $word) : string
    {
        return $word;
    }
}
