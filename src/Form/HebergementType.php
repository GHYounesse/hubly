<?php

namespace App\Form;

use App\Entity\Hebergement;
use App\Entity\Projet;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class HebergementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('site',TextType::class,[
                'label' => 'Nom de domaine',
                'attr' => ['placeholder' => 'Entrez le nom du site']
            ])
            ->add('fournisseur',TextType::class,[
                'label' => 'Fournisseur',
                'required' => false,
                'attr' => ['placeholder' => 'Entrez le nom du fournisseur']
            ])
            ->add('type',TextType::class,[
                'label' => 'Type',
                'required' => false,
                'attr' => ['placeholder' => 'Entrez le type']
            ])
            ->add('url',TextType::class,[
                'label' => 'URL',
                'required' => false,
                'attr' => ['placeholder' => 'Entrez l\'URL']
            ])
            ->add('dateExpiration', null, [
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => ['placeholder' => '0.00', 'min' => 0, 'step' => '0.01'],
            ])
            ->add('periodicite', ChoiceType::class, [
                'label' => 'Facturation',
                'choices' => ['Annuelle' => 'annuel', 'Mensuelle' => 'mensuel'],
            ])
            ->add('acces',TextareaType::class,[
                'label' => 'Accès hébergement',
                'attr' => ['placeholder' => 'Entrez les informations d\'accès']
            ])
            ->add('created', null, [
                'widget' => 'single_text',
            ])
            ->add('updated', null, [
                'widget' => 'single_text',
            ])
            ->add('estActif', ChoiceType::class, [
                'label' => 'Statut',
                'required' => true,
                'choices' => [
                    'Actif' => true,
                    'Inactif' => false,
                ],
                'attr' => [
                    'class' => 'w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hebergement::class,
        ]);
    }
}
