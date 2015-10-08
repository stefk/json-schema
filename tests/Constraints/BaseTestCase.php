<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Tests\Constraints;

use JsonSchema\Context;
use JsonSchema\RefResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getInvalidTests
     */
    public function testInvalidCases($input, $schema, $checkMode = Validator::CHECK_MODE_NORMAL, $errors = array())
    {
        $schema = json_decode($schema);

        $refResolver = new RefResolver(new UriRetriever);
        $refResolver->resolve($schema);

        $validator = new Validator($checkMode);

        $context = new Context();
        $validator->check(json_decode($input), $schema, $context);

        if (array() !== $errors) {
            $expected = var_export($errors, true);
            $actual = var_export($context->getErrors(), true);
            $msg = sprintf("Actual:\n%s\nExpected:\n%s", $actual, $expected);
            $this->assertEquals($errors, $context->getErrors(), $msg);
        }

        $this->assertFalse($context->hasErrors(), 'At least one error was expected.');
    }

    /**
     * @dataProvider getValidTests
     */
    public function testValidCases($input, $schema, $checkMode = Validator::CHECK_MODE_NORMAL)
    {
        $schema = json_decode($schema);

        $refResolver = new RefResolver(new UriRetriever);
        $refResolver->resolve($schema);

        $validator = new Validator($checkMode);
        $context = new Context();
        $validator->check(json_decode($input), $schema, $context);

        $this->assertTrue(
            !$context->hasErrors(),
            sprintf(
                "No errors were expected.\nErrors:\n%s",
                var_export($context->getErrors(), true)
            )
        );
    }

    abstract public function getValidTests();

    abstract public function getInvalidTests();
}
