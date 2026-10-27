<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Societe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Count;

final class SocieteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('denomination', TextType::class, [
                'empty_data' => '',
                'constraints' => [
                    new NotBlank([
                        'message' => 'La dénomination est obligatoire.',
                    ]),
                ],
            ])
            ->add('statut', ChoiceType::class, [
            'choices' => [
                'Actif' => 'actif',
                'Inactif' => 'inactif',],
            'data' => 'actif',
            'placeholder' => 'Sélectionner un statut',
            'constraints' => [
                new NotBlank([
                    'message' => 'Le statut est obligatoire.',
                ]),
            ],])
            ->add('adresse', TextType::class, [
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'constraints' => [
                    new Email([
                        'message' => 'Veuillez entrer une adresse email valide.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'exemple@domaine.com',
                ],
            ])
            ->add('phone', TelType::class, [
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^(?:0\d{9}|\+212\d{9})$/',
                        'message' => 'Le numéro de téléphone doit être valide (ex : +212612345678 ou 0612345678).',
                    ]),
                ],
                'attr' => [
                    'placeholder' => '+212612345678 ou 0612345678',
                ],
            ])
            ->add('ice', TextType::class, [
                'required' => false,
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo',
                'mapped' => false,
                'required' => false,
                'data_class' => null,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, JPG, PNG, WEBP ou GIF).',
                    ])
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'empty_data' => '',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Entrez la description de la société...',
                    'class' => 'form-control',
                ]
            ])
            ->add('users', EntityType::class, [
                'class' => User::class,
                'choice_label' => function ($user) {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
                'attr' => [
                    'id' => 'societe_users',
                ]
            ]);     
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Societe::class,
            'csrf_protection' => true, // ✅ Make sure this is true (it's true by default)
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'societe',
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();

                // If editing (entity has an ID and logo already exists)
                if ($data && $data->getId() && $data->getLogo()) {
                    return ['Default'];
                }

                // If creating new entity
                return ['Default', 'create'];
            },
        ]);
    }
}
