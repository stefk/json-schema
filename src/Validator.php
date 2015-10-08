<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema;

use JsonSchema\Constraints\Constraint;
use stdClass;

/**
 * A JsonSchema Constraint
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 * @see    README.md
 */
class Validator extends Constraint
{
    const SCHEMA_MEDIA_TYPE = 'application/schema+json';

    /**
     * Validates data against a given JSON schema. Both the value and the
     * schema are supposed to be a result of a json_decode call. The
     * validation works as defined by the schema proposal in http://json-schema.org
     *
     * {@inheritDoc}
     */
    public function check($value, stdClass $schema, Context $context)
    {
        $this->checkUndefined($value, $schema, $context);
    }

    /*****************************************************************
     * @todo
     *
     * Add method removed from Constraint:
     *
     * getErrors
     * isValid
     *
     * Remove third param (and Constraint inheritance) ?
     */
}
