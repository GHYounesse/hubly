<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roles = '';

        if (isset($options['data']) && $options['data']) {
            $roles = implode(', ', $options['data']->getRoles());
        }

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('roles', TextType::class, [
                'label' => 'Rôle',
                'attr' => ['class' => 'form-control', 'readonly' => true],
                'mapped' => false,
                'data' => $roles,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
            ])
            ->add('statut', TextType::class, [
                'label' => 'Statut',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('created', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de création',
            ])
            ->add('updated', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de mise à jour',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
