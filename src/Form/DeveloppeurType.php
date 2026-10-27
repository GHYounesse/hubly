<?php

namespace App\Form;

use App\Entity\Developpeur;
use App\Entity\Projet;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Count;
class DeveloppeurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('telephone')
            ->add('created', null, [
                'widget' => 'single_text',
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function ($user) {
                    return $user->getNom() . ' ' . $user->getPrenom();
                }, // ou 'nom', selon ce que tu veux afficher
                'placeholder' => 'Utilisateur',
                'empty_data' => null,
                'required' => true,
                'constraints' => [
                new NotNull([
                    'message' => 'Vous devez choisir un utilisateur !', // your custom message
                ]),
                new NotBlank([
                    'message' => 'Vous devez choisir un utilisateur !', // custom message
                ]),
                


            ],
            ])
            ->add('updated', null, [
                'widget' => 'single_text',
            ])
            
            // ->add('Enregistrer', SubmitType::class, [
            //     'attr' => ['class' => 'btn btn-info']
            // ])
        ;
    

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Developpeur::class,
        ]);
    }
}
