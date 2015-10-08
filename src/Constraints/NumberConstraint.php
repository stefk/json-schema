<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\Context;
use stdClass;

/**
 * The NumberConstraint Constraints, validates an number against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class NumberConstraint extends Constraint
{
    /**
     * {@inheritDoc}
     */
    public function check($value, stdClass $schema, Context $context)
    {
        // Verify minimum
        if (isset($schema->exclusiveMinimum)) {
            if (isset($schema->minimum)) {
                if ($schema->exclusiveMinimum && $value === $schema->minimum) {
                    $context->addError("Must have a minimum value greater than boundary value of " . $schema->minimum);
                } else if ($value < $schema->minimum) {
                    $context->addError("Must have a minimum value of " . $schema->minimum);
                }
            } else {
                $context->addError("Use of exclusiveMinimum requires presence of minimum");
            }
        } else if (isset($schema->minimum) && $value < $schema->minimum) {
            $context->addError("Must have a minimum value of " . $schema->minimum);
        }

        // Verify maximum
        if (isset($schema->exclusiveMaximum)) {
            if (isset($schema->maximum)) {
                if ($schema->exclusiveMaximum && $value === $schema->maximum) {
                    $context->addError("Must have a maximum value less than boundary value of " . $schema->maximum);
                } else if ($value > $schema->maximum) {
                    $context->addError("Must have a maximum value of " . $schema->maximum);
                }
            } else {
                $context->addError("Use of exclusiveMaximum requires presence of maximum");
            }
        } else if (isset($schema->maximum) && $value > $schema->maximum) {
            $context->addError("Must have a maximum value of " . $schema->maximum);
        }

        // Verify divisibleBy - Draft v3
        if (isset($schema->divisibleBy) && $this->fmod($value, $schema->divisibleBy) != 0) {
            $context->addError("Is not divisible by " . $schema->divisibleBy);
        }

        // Verify multipleOf - Draft v4
        if (isset($schema->multipleOf) && $this->fmod($value, $schema->multipleOf) != 0) {
            $context->addError("Must be a multiple of " . $schema->multipleOf);
        }

        $this->checkFormat($value, $schema, $context);
    }

    private function fmod($number1, $number2)
    {
        $modulus = fmod($number1, $number2);
        $precision = abs(0.0000000001);
        $diff = (float)($modulus - $number2);

        if (-$precision < $diff && $diff < $precision) {
            return 0.0;
        }

        $decimals1 = mb_strpos($number1, ".") ? mb_strlen($number1) - mb_strpos($number1, ".") - 1 : 0;
        $decimals2 = mb_strpos($number2, ".") ? mb_strlen($number2) - mb_strpos($number2, ".") - 1 : 0;

        return (float)round($modulus, max($decimals1, $decimals2));
    }
}
