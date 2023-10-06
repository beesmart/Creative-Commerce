<?php

// phpcs:ignore WordPress.Files.FileName
/**
 * Tag model.
 *
 * @package   Sematico\fluent-query
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */
namespace Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Model;

use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Scope\TagScope;
/**
 * Tag model.
 */
class Tag extends TermTaxonomy
{
    /**
     * Automatically adjust the query to load tags.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new TagScope());
    }
}
