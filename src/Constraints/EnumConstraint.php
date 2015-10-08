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
 * The EnumConstraint Constraints, validates an element against a given set of possibilities
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class EnumConstraint extends Constraint
{
    /**
     * {@inheritDoc}
     */
    public function check($value, stdClass $schema, Context $context)
    {
        // Only validate enum if the attribute exists
        if ($value instanceof UndefinedConstraint && (!isset($schema->required) || !$schema->required)) {
            return;
        }

        foreach ($schema->enum as $enum) {
            $type = gettype($value);
            if ($type === gettype($enum)) {
                if ($type == "object") {
                    if ($value == $enum)
                        return;
                } else {
                    if ($value === $enum)
                        return;

                }
            }
        }

        $context->addError("Does not have a value in the enumeration " . print_r($schema->enum, true));
    }
}
