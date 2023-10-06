<?php

namespace Barn2\Plugin\WC_Filters\Dependencies\Parental\Providers;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Support\ServiceProvider;
use Barn2\Plugin\WC_Filters\Dependencies\Laravel\Nova\Nova;
use Barn2\Plugin\WC_Filters\Dependencies\Parental\HasChildren;
use Barn2\Plugin\WC_Filters\Dependencies\Parental\HasParent;
class NovaResourceProvider extends ServiceProvider
{
    public function boot()
    {
        if (\class_exists(Nova::class)) {
            Nova::serving(function () {
                $this->setNovaResources();
            });
        }
    }
    protected function setNovaResources()
    {
        $map = [];
        foreach (Nova::$resources as $resource) {
            $parent = $resource::$model;
            $map[$parent] = $resource;
            $traits = \class_uses_recursive($parent);
            if (isset($traits[HasChildren::class]) && !isset($traits[HasParent::class])) {
                foreach ((new $parent())->getChildTypes() as $child) {
                    if (!isset($map[$child])) {
                        $map[$child] = $resource;
                    }
                }
            }
        }
        Nova::$resourcesByModel = \array_merge($map, Nova::$resourcesByModel);
    }
}
