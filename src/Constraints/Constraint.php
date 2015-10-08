<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\Context;
use JsonSchema\Uri\UriRetriever;
use stdClass;

/**
 * The Base Constraints, all Validators should extend this class
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Bruno Prieto Reis <bruno.p.reis@gmail.com>
 */
abstract class Constraint implements ConstraintInterface
{
    protected $checkMode = self::CHECK_MODE_NORMAL;
    protected $uriRetriever;
    protected $errors = array();
    protected $inlineSchemaProperty = '$schema';

    const CHECK_MODE_NORMAL = 1;
    const CHECK_MODE_TYPE_CAST = 2;

    /**
     * @param int          $checkMode
     * @param UriRetriever $uriRetriever
     */
    public function __construct($checkMode = self::CHECK_MODE_NORMAL, UriRetriever $uriRetriever = null)
    {
        $this->checkMode    = $checkMode;
        $this->uriRetriever = $uriRetriever;
    }

    /**
     * @return UriRetriever $uriRetriever
     */
    public function getUriRetriever()
    {
        if (is_null($this->uriRetriever))
        {
            $this->setUriRetriever(new UriRetriever);
        }

        return $this->uriRetriever;
    }

    /**
     * @param UriRetriever $uriRetriever
     */
    public function setUriRetriever(UriRetriever $uriRetriever)
    {
        $this->uriRetriever = $uriRetriever;
    }

    /**
     * Bubble down the path
     *
     * @param string $path Current path
     * @param mixed  $i    What to append to the path
     *
     * @return string
     */
    protected function incrementPath($path, $i)
    {
        if ($path !== '') {
            if (is_int($i)) {
                $path .= '[' . $i . ']';
            } elseif ($i == '') {
                $path .= '';
            } else {
                $path .= '.' . $i;
            }
        } else {
            $path = $i;
        }

        return $path;
    }

    /**
     * Validates an array
     *
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     */
    protected function checkArray($value, stdClass $schema, Context $context)
    {
        $validator = new CollectionConstraint($this->checkMode, $this->uriRetriever);
        $validator->check($value, $schema, $context);
    }

    /**
     * Validates an object
     *
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     * @param mixed     $patternProperties
     */
    protected function checkObject($value, stdClass $schema, Context $context, $patternProperties = null)
    {
        $validator = new ObjectConstraint($this->checkMode, $this->uriRetriever);
        $validator->check($value, $schema, $context, $patternProperties);
    }

    /**
     * Validates the type of a property
     *
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     */
    protected function checkType($value, stdClass $schema, Context $context)
    {
        $validator = new TypeConstraint($this->checkMode, $this->uriRetriever);
        $validator->check($value, $schema, $context);
    }

    /**
     * Checks a undefined element
     *
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     */
    protected function checkUndefined($value, stdClass $schema, Context $context)
    {
        $validator = new UndefinedConstraint($this->checkMode, $this->uriRetriever);
        $validator->check($value, $schema, $context);
    }

    /**
     * Checks a string element
     *
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     */
    protected function checkString($value, stdClass $schema, Context $context)
    {
        $validator = new StringConstraint($this->checkMode, $this->uriRetriever);
        $validator->check($value, $schema, $context);
    }

    /**
     * Checks a number element
     *
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     */
    protected function checkNumber($value, stdClass $schema, Context $context)
    {
        $validator = new NumberConstraint($this->checkMode, $this->uriRetriever);
        $validator->check($value, $schema, $context);
    }

    /**
     * Checks a enum element
     *
     * @param mixed     $value
     * @param stdClass  $schema
     * @param Context   $context
     */
    protected function checkEnum($value, stdClass $schema, Context $context)
    {
        $validator = new EnumConstraint($this->checkMode, $this->uriRetriever);
        $validator->check($value, $schema, $context);
    }

    protected function checkFormat($value, stdClass $schema, Context $context)
    {
        $validator = new FormatConstraint($this->checkMode, $this->uriRetriever);
        $validator->check($value, $schema, $context);
    }

    /**
     * @param string $uri JSON Schema URI
     * @return string JSON Schema contents
     */
    protected function retrieveUri($uri)
    {
        if (null === $this->uriRetriever) {
            $this->setUriRetriever(new UriRetriever);
        }
        $jsonSchema = $this->uriRetriever->retrieve($uri);
        // TODO validate using schema
        return $jsonSchema;
    }
}
