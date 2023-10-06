<?php

// phpcs:ignore WordPress.Files.FileName
/**
 * Metadata class.
 *
 * @package   Sematico\fluent-query
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */
namespace Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Model\Meta;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Relations\BelongsTo;
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Model\Term;
/**
 * Term metadata model.
 */
class TermMeta extends BaseMeta
{
    /** @var string */
    protected $table = 'termmeta';
    /** @var string */
    protected $primaryKey = 'meta_id';
    /** @var array */
    protected $fillable = ['meta_key', 'meta_value', 'term_id'];
    /**
     * Get the term to which the metadata belongs to.
     *
     * @return BelongsTo
     */
    public function term() : BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }
}
