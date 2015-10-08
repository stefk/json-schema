<?php

namespace JsonSchema;

class Context
{
    private $value;
    private $path = '';
    private $errors = [];

    public static function appendPath($basePath, $subPath)
    {
        if ($basePath === '') {
            $basePath = $subPath;
        } elseif (is_int($subPath)) {
            $basePath .= '[' . $subPath . ']';
        } else {
            $basePath .= '.' . $subPath;
        }

        return $basePath;
    }

    public function setNode($value, $path)
    {
        $this->value = $value;
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function addError($message)
    {
        $this->errors[] = array(
            'path' => $this->path,
            'message' => $message
        );
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return count($this->errors) !== 0;
    }

    public function duplicate()
    {
        // no object ref for now, clone is sufficient

        return clone $this;
    }

    public function merge(Context $context)
    {
        $this->errors = array_merge(
            $this->errors,
            array_values($context->getErrors())
        );
    }

    public function hasSameErrors(Context $context) {
        return $this->errors === $context->getErrors();
    }
}
