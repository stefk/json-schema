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
 * The Constraints Interface
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Stéphane Klein   <stephaneklein221@gmail.com>
 */
interface ConstraintInterface
{
    /**
     * Validates a value against a JSON schema and populates
     * the validation context with any encountered error.
     *
     * @abstract
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     */
    public function check($value, stdClass $schema, Context $context);
}
