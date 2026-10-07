<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactPickerType extends AbstractType
{
    public function __construct(public TranslatorInterface $translator) {}

    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add("mode", ChoiceType::class, [
            "mapped" => false,
            "label" => false,
            "expanded" => true,
            "data" => "select",
            "choices" => [
                "select_existing_contact" => "select",
                "create_new_contact" => "create",
            ],
            "attr" => ["class" => "contact-mode form-check-inline"],
        ]);

        $builder->add("existing", EntityType::class, [
            "class" => Contact::class,
            "mapped" => false,
            "label" => false,
            // Filled by JS with AJAX
            "choice_label" => fn(Contact $choice) => $choice->__toString(),
        ]);

        $builder
            ->add("firstname", TextType::class, [
                "required" => false,
                "mapped" => false,
                "label" => "firstname",
                "attr" => ["class" => "contact-choice-new"],
            ])
            ->add("lastname", TextType::class, [
                "required" => false,
                "mapped" => false,
                "label" => "lastname",
                "attr" => ["class" => "contact-choice-new"],
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (
            FormEvent $event,
        ) {
            $contact = $event->getData();
            $form = $event->getForm();

            if ($contact instanceof Contact && $contact->getId() !== null) {
                $form->get("existing")->setData($contact);
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (
            FormEvent $event,
        ) {
            $form = $event->getForm();

            $firstname = $form->get("firstname")->getData();
            $lastname = $form->get("lastname")->getData();
            $mode = $form->get("mode")->getData();
            $existing = $form->get("existing")->getData();

            if ($mode === "select" && $existing !== null) {
                $event->setData($existing);
            }

            if ($mode === "create" && isset($firstname, $lastname)) {
                $contact = new Contact();
                $contact->setFirstname($firstname);
                $contact->setLastname($lastname);
                $event->setData($contact);
            }
        });

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $form = $event->getForm();
                $mode = $form->get("mode")->getData();

                /** @var User|null */
                $deviceOwner = $form->getRoot()->get("user")->getData();
                $mode = $form->get("mode")->getData();
                $firstname = $form->get("firstname")->getData();
                $lastname = $form->get("lastname")->getData();
                $existing = $form->get("existing")->getData();

                if ($mode === "select" && $existing === null) {
                    $message = $this->translator->trans(
                        "contact_select_empty_error",
                    );
                    $form->get("mode")->addError(new FormError($message));
                }

                if ($mode === "select" && $existing instanceof Contact) {
                    $ownerContacts = $deviceOwner?->getContacts();
                    if (
                        $ownerContacts !== null &&
                        !$ownerContacts->contains($existing)
                    ) {
                        $message = $this->translator->trans(
                            "contact_select_wrong_owner",
                        );
                        $form->get("mode")->addError(new FormError($message));
                    }
                }

                if ($mode === "create" && !isset($lastname, $firstname)) {
                    $message = $this->translator->trans(
                        "contact_create_info_missing_error",
                    );
                    $form->get("mode")->addError(new FormError($message));
                }
            },
            -10,
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "empty_data" => null,
            "data_class" => null,
        ]);
    }
}
