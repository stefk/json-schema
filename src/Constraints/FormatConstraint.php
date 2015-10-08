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
 * Validates against the "format" property
 *
 * @author Justin Rainbow <justin.rainbow@gmail.com>
 * @link   http://tools.ietf.org/html/draft-zyp-json-schema-03#section-5.23
 */
class FormatConstraint extends Constraint
{
    /**
     * {@inheritDoc}
     */
    public function check($value, stdClass $schema, Context $context)
    {
        if (!isset($schema->format)) {
            return;
        }

        switch ($schema->format) {
            case 'date':
                if (!$date = $this->validateDateTime($value, 'Y-m-d')) {
                    $context->addError(sprintf('Invalid date %s, expected format YYYY-MM-DD', json_encode($value)));
                }
                break;

            case 'time':
                if (!$this->validateDateTime($value, 'H:i:s')) {
                    $context->addError(sprintf('Invalid time %s, expected format hh:mm:ss', json_encode($value)));
                }
                break;

            case 'date-time':
                if (!$this->validateDateTime($value, 'Y-m-d\TH:i:s\Z') &&
                    !$this->validateDateTime($value, 'Y-m-d\TH:i:s.u\Z') &&
                    !$this->validateDateTime($value, 'Y-m-d\TH:i:sP') &&
                    !$this->validateDateTime($value, 'Y-m-d\TH:i:sO')
                ) {
                    $context->addError(sprintf('Invalid date-time %s, expected format YYYY-MM-DDThh:mm:ssZ or YYYY-MM-DDThh:mm:ss+hh:mm', json_encode($value)));
                }
                break;

            case 'utc-millisec':
                if (!$this->validateDateTime($value, 'U')) {
                    $context->addError(sprintf('Invalid time %s, expected integer of milliseconds since Epoch', json_encode($value)));
                }
                break;

            case 'regex':
                if (!$this->validateRegex($value)) {
                    $context->addError('Invalid regex format ' . $value);
                }
                break;

            case 'color':
                if (!$this->validateColor($value)) {
                    $context->addError("Invalid color");
                }
                break;

            case 'style':
                if (!$this->validateStyle($value)) {
                    $context->addError("Invalid style");
                }
                break;

            case 'phone':
                if (!$this->validatePhone($value)) {
                    $context->addError("Invalid phone number");
                }
                break;

            case 'uri':
                if (null === filter_var($value, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE)) {
                    $context->addError("Invalid URL format");
                }
                break;

            case 'email':
                if (null === filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE)) {
                    $context->addError("Invalid email");
                }
                break;

            case 'ip-address':
            case 'ipv4':
                if (null === filter_var($value, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV4)) {
                    $context->addError("Invalid IP address");
                }
                break;

            case 'ipv6':
                if (null === filter_var($value, FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE | FILTER_FLAG_IPV6)) {
                    $context->addError("Invalid IP address");
                }
                break;

            case 'host-name':
            case 'hostname':
                if (!$this->validateHostname($value)) {
                    $context->addError("Invalid hostname");
                }
                break;

            default:
                // Do nothing so that custom formats can be used.
                break;
        }
    }

    protected function validateDateTime($datetime, $format)
    {
        $dt = \DateTime::createFromFormat($format, $datetime);

        if (!$dt) {
            return false;
        }

        return $datetime === $dt->format($format);
    }

    protected function validateRegex($regex)
    {
        return false !== @preg_match('/' . $regex . '/', '');
    }

    protected function validateColor($color)
    {
        if (in_array(strtolower($color), array('aqua', 'black', 'blue', 'fuchsia',
            'gray', 'green', 'lime', 'maroon', 'navy', 'olive', 'orange', 'purple',
            'red', 'silver', 'teal', 'white', 'yellow'))) {
            return true;
        }

        return preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $color);
    }

    protected function validateStyle($style)
    {
        $properties     = explode(';', rtrim($style, ';'));
        $invalidEntries = preg_grep('/^\s*[-a-z]+\s*:\s*.+$/i', $properties, PREG_GREP_INVERT);

        return empty($invalidEntries);
    }

    protected function validatePhone($phone)
    {
        return preg_match('/^\+?(\(\d{3}\)|\d{3}) \d{3} \d{4}$/', $phone);
    }

    protected function validateHostname($host)
    {
        return preg_match('/^[_a-z]+\.([_a-z]+\.?)+$/i', $host);
    }
}
