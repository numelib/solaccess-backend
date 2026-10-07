<?php

namespace App\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Field used to add raw HTML to any tabs or fieldset
 *
 * Can be used to add custom tables, charts and other content in specific
 * area without having to override entire templates
 */
final class RawHTMLField implements FieldInterface
{
    use FieldTrait;

    public string $innerHTML = "";

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return new self()
            ->setProperty("")
            ->setTemplatePath("admin/field/raw_html.html.twig");
    }

    public function setInnerHTML(string $value): static
    {
        return $this->setCustomOption("innerHTML", $value);
    }
}
