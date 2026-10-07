<?php

namespace App\Field;

use App\Form\ContactPickerType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Contracts\Translation\TranslatableInterface;

class ContactPickerField implements FieldInterface
{
    use FieldTrait;

    public static function new(
        string $propertyName,
        TranslatableInterface|string|bool|null $label = null,
    ): self {
        return new self()
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ContactPickerType::class);
    }
}
