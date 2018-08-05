<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Validation;

/**
 * Validation rules helper.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Rules
{
    /** @var Validation */
    protected $validation;

    public function __construct(Validation $validation)
    {
        $this->validation = $validation;
    }

    /**
     * Normalize rules if needed.
     *
     * @param mixed $rules
     *
     * @return array
     */
    public function normalizeRules($rules): array
    {
        if (\is_string($rules)) {
            $rules = $this->parseRules($rules);
        }

        if (!\is_array($rules)) {
            throw new \UnexpectedValueException('The rules should be an array or string');
        }

        return $this->cancelOnFail($rules);
    }

    /**
     * Parse string representation of the rules and make it array.
     *
     * Rule Format: `rule1:key1:value11,value12;key2:value22|rule2:key21:value21|rule3`
     *
     * @param string $rules Example: 'required|length:min:1;max:2;|in:domain:1,12,30'
     *
     * @return array
     */
    protected function parseRules(string $rules): array
    {
        $parsed = [];

        foreach (\explode('|', $rules) as $rule) {
            if (false === \strpos($rule, ':')) {
                $parsed[$rule] = [];
                continue;
            }

            list($name, $options) = \explode(':', $rule, 2);
            $parsed[$name]        = $this->parseOptions($options);
        }

        return $parsed;
    }

    /**
     * Parse rule options.
     *
     * @param string $options
     *
     * @return array
     */
    protected function parseOptions(string $options): array
    {
        $parsed = [];

        foreach (\explode(';', $options) as $parts) {
            list($key, $value) = \explode(':', $parts) + ['', ''];
            if (\strpos($value, ',')) {
                $value = \explode(',', $value);
            }

            $parsed[$key] = $value;
        }

        return $parsed;
    }

    /**
     * Make the validator cancel on fail i.e bail on first ever invalid field.
     *
     * @param array $rules
     *
     * @return array
     */
    protected function cancelOnFail(array $rules): array
    {
        if (!isset($rules['abort'])) {
            return $rules;
        }

        unset($rules['abort']);
        foreach ($rules as &$rule) {
            $rule = (array) $rule + ['cancelOnFail' => true];
        }

        return $rules;
    }
}
