<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SecurePinCodeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SecurePinCode) {
            throw new UnexpectedTypeException($constraint, SecurePinCode::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            $value = (string) $value;
        }

        if (!ctype_digit($value)) {
            $this->context->buildViolation($constraint->messageDigitsOnly)
            ->setParameter('%pinCode%', $value)
                ->addViolation();
            return;
        }

        if (mb_strlen($value) < $constraint->min) {
            $this->context->buildViolation($constraint->messageMinLength)
                ->setParameter('{{ limit }}', (string) $constraint->min)
                ->setParameter('%pinCode%', $value)
                ->addViolation();
            return;
        }

        if ($this->isSameDigitRepeated($value)) {
            $this->context->buildViolation($constraint->messageRepeatedDigit)
            ->setParameter('%pinCode%', $value)
                ->addViolation();
            return;
        }

        if ($this->isStrictAscendingSequence($value)) {
            $this->context->buildViolation($constraint->messageSequential)
            ->setParameter('%pinCode%', $value)
                ->addViolation();
        }

    }

    private function isSameDigitRepeated(string $value): bool
    {
        return count(array_unique(str_split($value))) === 1;
    }

    private function isStrictAscendingSequence(string $value): bool
    {
        $digits = str_split($value);

        for ($i = 1, $len = count($digits); $i < $len; $i++) {
            if (((int) $digits[$i]) !== ((int) $digits[$i - 1]) + 1) {
                return false;
            }
        }

        return true;
    }
}
