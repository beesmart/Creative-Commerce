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
use Barn2\Plugin\WC_Filters\Dependencies\Sematico\FluentQuery\Model\User;
/**
 * User metadata model.
 */
class UserMeta extends BaseMeta
{
    /**
     * @var string
     */
    protected $table = 'usermeta';
    /**
     * @var string
     */
    protected $primaryKey = 'umeta_id';
    /**
     * @var array
     */
    protected $fillable = ['meta_key', 'meta_value', 'user_id'];
    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
