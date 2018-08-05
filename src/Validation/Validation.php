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

use Phalcon\Validation as BaseValidation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;
use PhalconExt\Validators;

/**
 * Phalcon Validation with batteries.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Validation extends BaseValidation
{
    /** @var array The alias of available validators */
    protected $validators = [
        'alnum'        => Validator\Alnum::class,
        'alpha'        => Validator\Alpha::class,
        'between'      => Validator\Between::class,
        'confirmation' => Validator\Confirmation::class,
        'creditcard'   => Validator\CreditCard::class,
        'date'         => Validator\Date::class,
        'digit'        => Validator\Digit::class,
        'email'        => Validator\Email::class,
        'not_in'       => Validator\ExclusionIn::class,
        'file'         => Validator\File::class,
        'identical'    => Validator\Identical::class,
        'in'           => Validator\InclusionIn::class,
        'numeric'      => Validator\Numericality::class,
        'required'     => Validator\PresenceOf::class,
        'regex'        => Validator\Regex::class,
        'length'       => Validator\StringLength::class,
        'unique'       => Validator\Uniqueness::class,
        'url'          => Validator\Url::class,
        'exist'        => Existence::class,
    ];

    /** @var array The custom validation callbacks */
    protected $callbacks = [];

    /** @var ValidatorInterface The currently validating Validator. */
    protected $validator;

    /** @var Rules */
    protected $rulesHelper;

    public function initialize()
    {
        $this->rulesHelper = new Rules($this);
    }

    /**
     * Register a custom validation rule.
     *
     * @param string             $ruleName
     * @param callable|Validator $handler
     * @param string             $message  Message to use when validation fails
     *
     * @return self
     */
    public function register(string $ruleName, $handler, string $message = ''): self
    {
        if (isset($this->validators[$ruleName])) {
            return $this;
        }

        if ($message) {
            $this->_defaultMessages += [$ruleName => $message];
        }

        $this->validators[$ruleName] = $this->getHandler($ruleName, $handler);

        return $this;
    }

    /**
     * Get validation handler description].
     *
     * @param string $ruleName
     * @param mixed  $handler
     *
     * @return string
     */
    protected function getHandler(string $ruleName, $handler)
    {
        if ($handler instanceof \Closure) {
            $handler = \Closure::bind($handler, $this);
        }

        if (\is_callable($handler)) {
            $this->callbacks[$ruleName] = $handler;
            $handler                    = Validator\Callback::class;
        }

        if (!\is_subclass_of($handler, Validator::class)) {
            throw new \InvalidArgumentException('Unsupported validation rule: ' . $ruleName);
        }

        return $handler;
    }

    /**
     * Registers multiple custom validation rules at once!
     *
     * @param array $ruleHandlers ['rule1' => <handler>, ...]
     * @param array $messages     ['rule1' => 'message', ...]
     *
     * @return self
     */
    public function registerRules(array $ruleHandlers, array $messages = []): self
    {
        foreach ($ruleHandlers as $ruleName => $handler) {
            $this->register($ruleName, $handler, $messages[$ruleName] ?? '');
        }

        return $this;
    }

    /**
     * Check if the validation passes.
     *
     * @return bool
     */
    public function pass(): bool
    {
        return \count($this->_messages) === 0;
    }

    /**
     * Check if the validation fails.
     *
     * @return bool
     */
    public function fail(): bool
    {
        return !$this->pass();
    }

    /**
     * Get the error messages.
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        $messages = [];

        foreach ($this->_messages as $message) {
            $messages[] = [
                'code'    => $message->getCode(),
                'message' => $message->getMessage(),
                'field'   => $message->getField(),
            ];
        }

        return $messages;
    }

    /**
     * self validation with given ruleSet against given arbitrary dataSet.
     *
     * @param array        $ruleSet
     * @param array|object $dataSet
     *
     * @return self
     */
    public function run(array $ruleSet, $dataSet): self
    {
        $this->_messages = $this->_validators = [];

        // See if it is arrayable!
        if (\is_object($dataSet)) {
            $dataSet = $this->prepareDate($dataSet);
        }

        // OK, must be entity!
        if (\is_object($dataSet)) {
            $this->_entity = $dataSet;
        } else {
            $this->_data = $dataSet;
        }

        $this->addRules($ruleSet)->validate();

        return $this;
    }

    /**
     * Prepare data &/or entity.
     *
     * @param array|object $dataSet
     *
     * @return array|object
     */
    protected function prepareDate($dataSet)
    {
        if ($dataSet instanceof \stdClass) {
            return (array) $dataSet;
        }

        if (\method_exists($dataSet, 'toArray')) {
            return $dataSet->toArray();
        }

        if (\method_exists($dataSet, 'getData')) {
            return $dataSet->getData();
        }

        return $dataSet;
    }

    /**
     * Run the validation rules on data set.
     *
     * @param array $ruleSet
     *
     * @return self
     */
    protected function addRules(array $ruleSet): self
    {
        foreach ($ruleSet as $attribute => $rules) {
            $rules = $this->rulesHelper->normalizeRules($rules);

            // Only validate if attribute exists in dataSet when so configured.
            if (isset($rules['if_exist']) && null === $this->getValue($attribute)) {
                continue;
            }

            unset($rules['if_exist']);
            $this->attributeRules($attribute, $rules);
        }

        return $this;
    }

    /**
     * Add all the rules for given attribute to validators list.
     *
     * @param string $attribute
     * @param array  $rules
     *
     * @return void
     */
    protected function attributeRules(string $attribute, array $rules)
    {
        foreach ($rules as $rule => $options) {
            if (!isset($this->validators[$rule])) {
                throw new \InvalidArgumentException('Unknown validation rule: ' . $rule);
            }

            $validator = $this->validators[$rule];
            $options   = (array) $options + [
                'callback' => $this->callbacks[$rule] ?? null,
                'message'  => $this->_defaultMessages[$rule] ?? null,
                '__field'  => $attribute,
            ];

            $this->add($attribute, new $validator($options));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function preChecking($field, ValidatorInterface $validator): bool
    {
        $this->validator = $validator;

        return parent::preChecking($field, $validator);
    }

    /**
     * Get current value being validated.
     *
     * @return mixed
     */
    public function getCurrentValue()
    {
        return $this->getValue($this->validator->getOption('__field'));
    }

    /**
     * Delegate calls to current validator.
     *
     * @param string $method
     * @param mixed  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->validator->$method(...$args);
    }
}
