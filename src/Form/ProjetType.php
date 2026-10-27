<?php

namespace App\Form;

use App\Entity\Developpeur;
use App\Entity\Hebergement;
use App\Entity\User;
use App\Entity\Projet;
use App\Entity\Societe;
use App\Entity\Technologie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProjetType extends AbstractType
{
    private Security $security;
    private EntityManagerInterface $em;


    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $societe = null;
        if ($options['societe']) {
            $societe = $this->em->getRepository(Societe::class)->find($options['societe']);
        }

        $builder
            /* row 1 */
            ->add('nom', TextType::class, [
                'label' => 'Nom du projet',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Saisissez le nom du projet',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Web' => 'web',
                    'Mobile' => 'mobile',
                    'Desktop' => 'desktop',
                ],
                'required' => true,
                'label' => 'Type du projet',
                'placeholder' => 'Choisissez un type',
                'attr' => [
                    'class' => 'select2'
                ]
            ])
            ->add('societe', EntityType::class, [
                'class' => Societe::class,
                'choice_label' => 'denomination',
                'placeholder' => 'Choisissez une société',
                'required' => true,
                'data' => $societe,
                'choices' => $user->getSocietes(),
                'label' => 'Société',
                'attr' => [
                    'class' => 'select2',
                ],

            ])

            /* row 2 */
            ->add('url', TextType::class, [
                'required' => false,
                'label' => 'URL',
                'attr' => [
                    'placeholder' => 'https://monprojet.com/',
                ],
            ])
            ->add('accesCodeSource', TextareaType::class, [
                'label' => 'Accès Code Source',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrez le lien du code source (GitHub, GitLab, etc.)',
                ],
            ])

            /* row 3 */
            ->add('accesEnvironnement', TextareaType::class, [
                'label' => 'Accès Environnement',
                'required' => false,
                'attr' => [
                    'placeholder' => "username=xxxxx\npassword=xxxxxxx",
                    'rows' => 3,
                ],
            ])

            /* row 4 */
            ->add('technologie', EntityType::class, [
                'class' => Technologie::class,
                'choice_label' => 'nom',
                'required' => true,
                'multiple' => true,
                'attr' => [
                    'id' => 'projet_technologie',
                ],
            ])
            ->add('developpeur', EntityType::class, [
                'class' => Developpeur::class,
                'required' => true,
                'multiple' => true,
                'attr' => [
                    'class' => 'select2',
                ],
            ])
            ->add('hebergement', EntityType::class, [
                'class' => Hebergement::class,
                'choice_label' => function (Hebergement $hebergement) {
                    return $hebergement->getSite() . ' - ' . $hebergement->getFournisseur();
                },
                'multiple' => true,
                'attr' => [
                    'class' => 'select2',
                ],
                'required' => false
            ])

            ->add('created', null, [
                'widget' => 'single_text',
            ])
            ->add('updated', null, [
                'widget' => 'single_text',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'À faire' => 'à faire',
                    'En cours' => 'en cours',
                    'Terminé' => 'terminé',
                ],
                'label' => 'Statut du projet',
                'placeholder' => 'Choisissez un statut',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])

            /* row 5 */
            ->add('suiviPar', EntityType::class, [
                'class' => User::class,
                'choice_label' => function ($user) {
                    return $user->getNom() . ' ' . $user->getPrenom();
                },
                'placeholder' => 'Sélectionner un superviseur',
                'required' => true,
            ])
            ->add('developpeurPrincipal', EntityType::class, [
                'class' => Developpeur::class,
                'choice_label' => function ($developpeur) {
                    return $developpeur->getNom() . ' ' . $developpeur->getPrenom();
                },
                'placeholder' => 'Sélectionner le développeur principal',
                'required' => true,
            ])
            ->add('commercial', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner le commercial',
                'required' => true,
            ])

            /* row 6 */
            ->add('priorite', ChoiceType::class, [
                'choices' => [
                    'Normal' => 'normal',
                    'Moyen' => 'moyen',
                    'Urgent' => 'urgent',
                    'Très urgent' => 'tres urgent',
                ],
                'required' => true,
                'placeholder' => 'Choisir la priorité',
            ])
            ->add('dateCommande', DateType::class, ['widget' => 'single_text', 'required' => true])
            ->add('dateLivraison', DateType::class, ['widget' => 'single_text', 'required' => true])

            /* row 7 */
            ->add('remarques', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => "Une simple description",
                ],
            ])
            ->add('risqueLivraison', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => "Une explication détaillée",
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => "Une explication courte du projet",
                ],
            ])

            /* row 8 */
            ->add('projetTeste', CheckboxType::class, [
                'label' => 'Projet testé',
                'required' => false,
                'label_attr' => [
                    'class' => '!ml-1 !pl-5 mr-4 !my-0 text-sm font-medium text-gray-900 dark:text-white',
                ],
            ])
            ->add('projetValide', CheckboxType::class, [
                'label' => 'Projet validé',
                'required' => false,
                'label_attr' => [
                    'class' => '!ml-1 !pl-5 mr-4 !my-0 text-sm font-medium text-gray-900 dark:text-white',
                ],
            ])
            ->add('clientGratuite', CheckboxType::class, [
                'label' => 'Client gratuité',
                'required' => false,
                'label_attr' => [
                    'class' => '!ml-1 !pl-5 mr-4 !my-0 text-sm font-medium text-gray-900 dark:text-white',
                ],
            ])
            ->add('clientAmeliorations', CheckboxType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => '!ml-1 !pl-5 mr-4 !my-0 text-sm font-medium text-gray-900 dark:text-white',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Projet::class,
            'csrf_protection' => true, // ✅ Make sure this is true (it's true by default)
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'projet',
            'societe' => null,
        ]);
    }
}
