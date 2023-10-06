<?php

declare (strict_types=1);
namespace Barn2\Plugin\WC_Filters\Dependencies\Doctrine\Inflector\Rules\Spanish;

use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\Inflector\GenericLanguageInflectorFactory;
use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\Inflector\Rules\Ruleset;
final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset() : Ruleset
    {
        return Rules::getSingularRuleset();
    }
    protected function getPluralRuleset() : Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
