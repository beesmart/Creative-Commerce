<?php

/**
 * Thanks to https://github.com/flaushi for his suggestion:
 * https://github.com/doctrine/dbal/issues/2873#issuecomment-534956358
 */
namespace Barn2\Plugin\WC_Filters\Dependencies\Carbon\Doctrine;

use Barn2\Plugin\WC_Filters\Dependencies\Carbon\Carbon;
use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\DBAL\Types\VarDateTimeType;
class DateTimeType extends VarDateTimeType implements CarbonDoctrineType
{
    /** @use CarbonTypeConverter<Carbon> */
    use CarbonTypeConverter;
}
