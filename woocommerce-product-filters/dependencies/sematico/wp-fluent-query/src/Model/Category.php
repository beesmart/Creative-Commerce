<?php

// phpcs:ignore WordPress.Files.FileName
/**
 * Category model.
 *
 * @package   Sematico\fluent-query
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */
namespace Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Model;

use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Scope\CategoryScope;
/**
 * Category model.
 */
class Category extends TermTaxonomy
{
    /**
     * Automatically adjust the query to load categories.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CategoryScope());
    }
}
