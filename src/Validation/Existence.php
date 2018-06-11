<?php

namespace PhalconExt\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator\Uniqueness;

/**
 * Check that a field exists in the related table.
 */
class Existence extends Uniqueness
{
    /**
     * Executes the validation.
     */
    public function validate(Validation $validation, $field) : bool
    {
        if (!$this->isUniqueness($validation, $field)) {
            return true;
        }

        $label = $this->getOption('label') ?: $validation->getLabel($field);
        $error = $this->getOption('message') ?: $validation->getDefaultMessage('Existence');
        $error = \strtr($error, [':field' => $label]);

        $validation->appendMessage(
            new Message($error, $field, 'Existence', $this->getOption('code'))
        );

        return false;
    }
}
