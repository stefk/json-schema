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
 * The ObjectConstraint Constraints, validates an object against a given schema
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class ObjectConstraint extends Constraint
{
    /**
     * {@inheritDoc}
     */
    function check($value, stdClass $schema, Context $context)
    {
        if ($value instanceof UndefinedConstraint) {
            return;
        }

        $matches = array();


//        if ($patternProperties) {
//            $matches = $this->validatePatternProperties($value, $path, $patternProperties);
//        }

        // validate the definition properties
        $this->validateDefinition($value, $schema, $context);

        // additional the element properties
//        $this->validateElement($value, $matches, $schema, $path, $additionalProp);
    }

    public function validatePatternProperties($element, Context $context, $patternProperties)
    {
        $matches = array();
        foreach ($patternProperties as $pregex => $schema) {
            // Validate the pattern before using it to test for matches
            if (@preg_match('/'. $pregex . '/', '') === false) {
                $context->addError('The pattern "' . $pregex . '" is invalid');
                continue;
            }
            foreach ($element as $i => $value) {
                if (preg_match('/' . $pregex . '/', $i)) {
                    $matches[] = $i;
                    $this->checkUndefined($value, $schema ? : new \stdClass(), $path, $i);
                }
            }
        }
        return $matches;
    }

    /**
     * Validates the element properties
     *
     * @param \stdClass $element          Element to validate
     * @param array     $matches          Matches from patternProperties (if any)
     * @param \stdClass $objectDefinition ObjectConstraint definition
     * @param Context   $context
     * @param mixed     $additionalProp   Additional properties
     */
    public function validateElement($element, $matches, $objectDefinition = null, Context $context, $additionalProp = null)
    {
        foreach ($element as $i => $value) {

            $property = $this->getProperty($element, $i, new UndefinedConstraint());
            $definition = $this->getProperty($objectDefinition, $i);

            // no additional properties allowed
            if (!in_array($i, $matches) && $additionalProp === false && $this->inlineSchemaProperty !== $i && !$definition) {
                $context->addError("The property - " . $i . " - is not defined and the definition does not allow additional properties");
            }

            // additional properties defined
            if (!in_array($i, $matches) && $additionalProp && !$definition) {
                if ($additionalProp === true) {
                    $this->checkUndefined($value, new stdClass(), $path, $i);
                } else {
                    $this->checkUndefined($value, $additionalProp, $path, $i);
                }
            }

            // property requires presence of another
            $require = $this->getProperty($definition, 'requires');
            if ($require && !$this->getProperty($element, $require)) {
                $context->addError("The presence of the property " . $i . " requires that " . $require . " also be present");
            }

            if (!$definition) {
                // normal property verification
                $this->checkUndefined($value, new stdClass(), $context);
            }
        }
    }

    /**
     * Validates the definition properties
     *
     * @param stdClass $element          Element to validate
     * @param stdClass $objectDefinition ObjectConstraint definition
     * @param Context $context
     */
    public function validateDefinition($element, stdClass $objectDefinition, Context $context)
    {
        foreach ($objectDefinition as $i => $value) {
            $property = $this->getProperty($element, $i, new UndefinedConstraint());
            $definition = $this->getProperty($objectDefinition, $i);
            $this->checkUndefined($property, $definition, $context);
        }
    }

    /**
     * retrieves a property from an object or array
     *
     * @param mixed  $element  Element to validate
     * @param string $property Property to retrieve
     * @param mixed  $fallback Default value if property is not found
     *
     * @return mixed
     */
    protected function getProperty($element, $property, $fallback = null)
    {
        if (is_array($element) /*$this->checkMode == self::CHECK_MODE_TYPE_CAST*/) {
            return array_key_exists($property, $element) ? $element[$property] : $fallback;
        } elseif (is_object($element)) {
            return property_exists($element, $property) ? $element->$property : $fallback;
        }

        return $fallback;
    }
}
