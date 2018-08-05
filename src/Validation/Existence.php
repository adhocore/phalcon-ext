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

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use PhalconExt\Di\ProvidesDi;

/**
 * Check that a field exists in the related table.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Existence extends Validator
{
    use ProvidesDi;

    /**
     * Executes the validation.
     *
     * @param validation $validation
     * @param string     $field
     */
    public function validate(Validation $validation, $field) : bool
    {
        $options = $this->_options + [
            'table'  => $field,
            'column' => isset($this->_options['table']) ? $field : 'id',
        ];

        $count = $this->di('db')->countBy($options['table'], [
            $options['column'] => $validation->getValue($field),
        ]);

        if ($count > 0) {
            return true;
        }

        return $this->failed($validation, $field);
    }

    /**
     * Set message when valdiation failed.
     *
     * @param Validation $validation
     * @param string     $field
     *
     * @return bool
     */
    protected function failed(Validation $validation, $field): bool
    {
        $label = $this->getOption('label') ?: $validation->getLabel($field);
        $error = $this->getOption('message') ?: $validation->getDefaultMessage('Existence');
        $error = \strtr($error, [':field' => $label]);

        $validation->appendMessage(
            new Message($error, $field, 'Existence', $this->getOption('code'))
        );

        return false;
    }
}
