<?php

// phpcs:ignore WordPress.Files.FileName
/**
 * Comment model.
 *
 * @package   Sematico\fluent-query
 * @author    Alessandro Tesoro <alessandro.tesoro@icloud.com>
 * @copyright Alessandro Tesoro
 * @license   MIT
 */
namespace Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Model;

use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Relations\BelongsTo;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Relations\HasMany;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Model;
use Barn2\Plugin\WC_Filters\Dependencies\Illuminate\Database\Eloquent\Relations\HasOne;
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Concerns\HasMetaFields;
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Concerns\HasOrderScopes;
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Concerns\HasUniqueIdentifier;
/**
 * WordPress Comment model.
 */
class Comment extends Model
{
    use HasMetaFields;
    use HasOrderScopes;
    use HasUniqueIdentifier;
    const CREATED_AT = 'comment_date';
    const UPDATED_AT = null;
    /**
     * @var string
     */
    protected $table = 'comments';
    /**
     * @var string
     */
    protected $primaryKey = 'comment_ID';
    /**
     * @var array
     */
    protected $dates = ['comment_date'];
    /**
     * @return BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'comment_post_ID');
    }
    /**
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->original();
    }
    /**
     * @return BelongsTo
     */
    public function original()
    {
        return $this->belongsTo(self::class, 'comment_parent');
    }
    /**
     * @return HasMany
     */
    public function replies()
    {
        return $this->hasMany(self::class, 'comment_parent');
    }
    /**
     * @return User
     */
    public function author()
    {
        return $this->hasOne(User::class, 'ID', 'user_id')->first();
    }
    /**
     * @param mixed $value
     * @return void
     */
    public function setUpdatedAt($value)
    {
    }
}
