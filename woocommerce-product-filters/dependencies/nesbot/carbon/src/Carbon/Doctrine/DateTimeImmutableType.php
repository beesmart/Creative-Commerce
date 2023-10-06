<?php

/**
 * Thanks to https://github.com/flaushi for his suggestion:
 * https://github.com/doctrine/dbal/issues/2873#issuecomment-534956358
 */
namespace Barn2\Plugin\WC_Filters\Dependencies\Carbon\Doctrine;

use Barn2\Plugin\WC_Filters\Dependencies\Carbon\CarbonImmutable;
use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\DBAL\Types\VarDateTimeImmutableType;
class DateTimeImmutableType extends VarDateTimeImmutableType implements CarbonDoctrineType
{
    /** @use CarbonTypeConverter<CarbonImmutable> */
    use CarbonTypeConverter;
    /**
     * @return class-string<CarbonImmutable>
     */
    protected function getCarbonClassName() : string
    {
        return CarbonImmutable::class;
    }
}
