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
/**
 * Comment metadata.
 */
class CommentMeta extends BaseMeta
{
    /**
     * @var string
     */
    protected $table = 'commentmeta';
    /**
     * @var array
     */
    protected $fillable = ['meta_key', 'meta_value', 'comment_id'];
    /**
     * @return BelongsTo
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
}
