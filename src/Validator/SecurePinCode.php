<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SecurePinCode extends Constraint
{
    public string $messageDigitsOnly = 'unsecure_pin_code.digit_only';
    public string $messageMinLength = 'unsecure_pin_code.min_length';
    public string $messageRepeatedDigit = 'unsecure_pin_code.repeated';
    public string $messageSequential = 'unsecure_pin_code.sequential';

    public int $min = 4;

}
