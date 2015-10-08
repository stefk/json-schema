<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\Context;
use JsonSchema\Uri\UriResolver;
use stdClass;

/**
 * The UndefinedConstraint Constraints
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
class UndefinedConstraint extends Constraint
{
    /**
     * {@inheritDoc}
     */
    public function check($value, stdClass $schema, Context $context)
    {
        // check special properties
        $this->validateCommonProperties($value, $schema, $context);

        // check allOf, anyOf, and oneOf properties
        $this->validateOfProperties($value, $schema, $context);

        // check known types
        $this->validateTypes($value, $schema, $context);
    }

    /**
     * Validates the value against the types
     *
     * @param mixed     $value
     * @param mixed     $schema
     * @param Context   $context
     */
    public function validateTypes($value, stdClass $schema, Context $context)
    {
        // check array
        if (is_array($value)) {
            $this->checkArray($value, $schema, $context);
        }

        // check object
        if (is_object($value) && (isset($schema->properties) || isset($schema->patternProperties))) {
            $this->checkObject(
                $value,
                isset($schema->properties) ? $schema->properties : new stdClass(),
                $context,
                isset($schema->additionalProperties) ? $schema->additionalProperties : null,
                isset($schema->patternProperties) ? $schema->patternProperties : null
            );
        }

        // check string
        if (is_string($value)) {
            $this->checkString($value, $schema, $context);
        }

        // check numeric
        if (is_numeric($value)) {
            $this->checkNumber($value, $schema, $context);
        }

        // check enum
        if (isset($schema->enum)) {
            $this->checkEnum($value, $schema, $context);
        }
    }

    /**
     * Validates common properties
     *
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     **/
    protected function validateCommonProperties($value, stdClass $schema, Context $context)
    {
        // if it extends another schema, it must pass that schema as well
        if (isset($schema->extends)) {
            if (is_string($schema->extends)) {
                $schema->extends = $this->validateUri($schema, $schema->extends);
            }
            if (is_array($schema->extends)) {
                foreach ($schema->extends as $extends) {
                    $this->checkUndefined($value, $extends, $context);
                }
            } else {
                // FIXME: "extends" shoudln't be null (see draft 3, 5.26)
                $extends = $schema->extends === null ? new stdClass() : $schema->extends;
                $this->checkUndefined($value, $extends, $context);
            }
        }

        // Verify required values
        if (is_object($value)) {

            if (!($value instanceof UndefinedConstraint) && isset($schema->required) && is_array($schema->required) ) {
                // Draft 4 - Required is an array of strings - e.g. "required": ["foo", ...]
                foreach ($schema->required as $required) {
                    if (!property_exists($value, $required)) {
                        $context->addError("The property " . $required . " is required");
                    }
                }
            } else if (isset($schema->required) && !is_array($schema->required)) {
                // Draft 3 - Required attribute - e.g. "foo": {"type": "string", "required": true}
                if ( $schema->required && $value instanceof UndefinedConstraint) {
                    $context->addError("Is missing and it is required");
                }
            }
        }

        // Verify type
        if (!($value instanceof UndefinedConstraint)) {
            $this->checkType($value, $schema, $context);
        }

        // Verify disallowed items
        if (isset($schema->disallow)) {
            $altContext = $context->duplicate();
            $typeSchema = new \stdClass();
            $typeSchema->type = $schema->disallow;
            $this->checkType($value, $typeSchema, $altContext);

            // if no new errors were raised it must be a disallowed value
            if ($altContext->hasSameErrors($context)) {
                $context->addError("Disallowed value was matched");
            }
        }

        if (isset($schema->not)) {
            $altContext = $context->duplicate();
            $this->checkUndefined($value, $schema->not, $altContext);

            // if no new errors were raised then the instance validated against the "not" schema
            if (count($context->getErrors()) === count($altContext->getErrors())) {
                $context->addError("Matched a schema which it should not");
            }
        }

        // Verify minimum and maximum number of properties
        if (is_object($value)) {
            if (isset($schema->minProperties)) {
                if (count(get_object_vars($value)) < $schema->minProperties) {
                    $context->addError("Must contain a minimum of " . $schema->minProperties . " properties");
                }
            }
            if (isset($schema->maxProperties)) {
                if (count(get_object_vars($value)) > $schema->maxProperties) {
                    $context->addError("Must contain no more than " . $schema->maxProperties . " properties");
                }
            }
        }

        // Verify that dependencies are met
        if (is_object($value) && isset($schema->dependencies)) {
            $this->validateDependencies($value, $schema->dependencies, $context);
        }
    }

    /**
     * Validate allOf, anyOf, and oneOf properties
     *
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     */
    protected function validateOfProperties($value, $schema, Context $context)
    {
        // Verify type
        if ($value instanceof UndefinedConstraint) {
            return;
        }

        if (isset($schema->allOf)) {
            $isValid = true;
            foreach ($schema->allOf as $allOf) {
                $initErrors = $context->getErrors();
                $this->checkUndefined($value, $allOf, $context);
                $isValid = $isValid && (count($context->getErrors()) == count($initErrors));
            }
            if (!$isValid) {
                $context->addError("Failed to match all schemas");
            }
        }

        if (isset($schema->anyOf)) {
            $isValid = false;

            foreach ($schema->anyOf as $anyOf) {
                $altContext = $context->duplicate();
                $this->checkUndefined($value, $anyOf, $altContext);

                if ($isValid = 0 === count($altContext->getErrors())) {
                    break;
                }
            }

            if (!$isValid) {
                $context->addError("Failed to match at least one schema");
            }
        }

        if (isset($schema->oneOf)) {
            $cumulatingContext = $context->duplicate();
            $matchedSchemas = 0;

            foreach ($schema->oneOf as $oneOf) {
                $altContext = $context->duplicate();
                $this->checkUndefined($value, $oneOf, $altContext);

                if (count($altContext->getErrors()) === count($context->getErrors())) {
                    $matchedSchemas++;
                } else {
                    $cumulatingContext->merge($altContext);
                }
            }

            if ($matchedSchemas !== 1) {
                $context = $cumulatingContext;
                $context->addError("failed to match exactly one schema");
            }
        }
    }

    /**
     * Validate dependencies
     *
     * @param mixed     $value
     * @param stdClass  $dependencies
     * @param Context   $context
     */
    protected function validateDependencies($value, stdClass $dependencies, Context $context)
    {
        foreach ($dependencies as $key => $dependency) {
            if (property_exists($value, $key)) {
                if (is_string($dependency)) {
                    // Draft 3 string is allowed - e.g. "dependencies": {"bar": "foo"}
                    if (!property_exists($value, $dependency)) {
                        $context->addError("$key depends on $dependency and $dependency is missing");
                    }
                } else if (is_array($dependency)) {
                    // Draft 4 must be an array - e.g. "dependencies": {"bar": ["foo"]}
                    foreach ($dependency as $d) {
                        if (!property_exists($value, $d)) {
                            $context->addError("$key depends on $d and $d is missing");
                        }
                    }
                } else if (is_object($dependency)) {
                    // Schema - e.g. "dependencies": {"bar": {"properties": {"foo": {...}}}}
                    $this->checkUndefined($value, $dependency, $context);
                }
            }
        }
    }

    protected function validateUri($schema, $schemaUri = null)
    {
        $resolver = new UriResolver();
        $retriever = $this->getUriRetriever();

        $jsonSchema = null;
        if ($resolver->isValid($schemaUri)) {
            $schemaId = property_exists($schema, 'id') ? $schema->id : null;
            $jsonSchema = $retriever->retrieve($schemaId, $schemaUri);
        }

        return $jsonSchema;
    }
}
