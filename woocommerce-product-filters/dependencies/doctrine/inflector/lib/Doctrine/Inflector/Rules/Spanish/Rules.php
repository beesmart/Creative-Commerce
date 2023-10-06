<?php

declare (strict_types=1);
namespace Barn2\Plugin\WC_Filters\Dependencies\Doctrine\Inflector\Rules\Spanish;

use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\Inflector\Rules\Patterns;
use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\Inflector\Rules\Ruleset;
use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\Inflector\Rules\Substitutions;
use Barn2\Plugin\WC_Filters\Dependencies\Doctrine\Inflector\Rules\Transformations;
final class Rules
{
    public static function getSingularRuleset() : Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getSingular()), new Patterns(...Uninflected::getSingular()), (new Substitutions(...Inflectible::getIrregular()))->getFlippedSubstitutions());
    }
    public static function getPluralRuleset() : Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getPlural()), new Patterns(...Uninflected::getPlural()), new Substitutions(...Inflectible::getIrregular()));
    }
}
