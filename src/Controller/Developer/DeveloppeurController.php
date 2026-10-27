<?php

namespace App\Controller\Developer;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Developpeur;
use App\Form\DeveloppeurType;
use App\Form\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DeveloppeurRepository;

/**
 * Class DeveloppeurController
 * @package App\Controller\Developer
 * Controller managing CRUD operations for Developpeur entities.
 */
#[Route('/developpeur')]
final class DeveloppeurController extends AbstractController
{
    /**
     * List all developers (`Developpeur`) and optionally search by name.
     *
     * - Uses EntityManagerInterface to fetch `Developpeur` entities.
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('', name: 'app_developpeur_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        // Liste des développeurs par défaut
        $developpeurs = $em->getRepository(Developpeur::class)->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!empty($data['search'])) {
                // Recherche des développeurs par nom
                $developpeurs = $em->getRepository(Developpeur::class)
                    ->createQueryBuilder('d')
                    ->where('LOWER(d.nom) LIKE LOWER(:search)')
                    ->setParameter('search', '%' . $data['search'] . '%')
                    ->getQuery()
                    ->getResult();
            }

            return $this->render('developpeur/_search_result.html.twig', [
                'form' => $form->createView(),
                'developpeurs' => $developpeurs,
            ]);
        }

        return $this->render('developpeur/index.html.twig', [
            'form' => $form->createView(),
            'developpeurs' => $developpeurs,
        ]);
    }

    /**
     * Creates a new developer.
     *
     * - Handles the creation form and sets the created/updated timestamps automatically.
     * - Persists the new developer to the database.
     *
     * @param ManagerRegistry $doctrine Doctrine service for entity management.
     * @param Request $request The current HTTP request.
     *
     * @return Response The rendered form page or redirect after successful creation.
     */
    #[Route('/new', name: 'app_developpeur_new', methods: ["GET","POST"])]
    public function new(ManagerRegistry $doctrine,Request $request,DeveloppeurRepository $developpeurRepo): Response
    {
        $developpeur=new Developpeur();
        $form=$this->createForm(DeveloppeurType::class,$developpeur);
        $form->remove('created');
        $form->remove('updated');
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $developpeur->setCreated(new \DateTime());
            $developpeur->setUpdated(new \DateTime());
            $user = $developpeur->getUser();

            // Check if this user already has a developpeur
            if ($developpeurRepo->findOneBy(['user' => $user])) {
                $this->addFlash('error', 'Cet utilisateur a déjà un profil développeur.');
                return $this->redirectToRoute('app_developpeur_index');
            }
            $entityManager=$doctrine->getManager();
            $entityManager->persist($developpeur);
            $entityManager->flush();
            return $this->redirectToRoute('app_developpeur_index');
        }
        return $this->render('developpeur/new.html.twig', [
            'form' => $form->createView(),
            
        ]);
    }
    /**
     * Updates an existing developer.
     *
     * - Loads the developer by ID, throws NotFoundHttpException if not found.
     * - Handles the update form and sets the updated timestamp automatically.
     *
     * @param EntityManagerInterface $entityManager Entity manager for database operations.
     * @param ManagerRegistry $doctrine Doctrine service.
     * @param Request $request The current HTTP request.
     * @param int $id The ID of the developer to update.
     *
     * @return Response The rendered edit form or redirect after successful update.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If no developer exists with the given ID.
     */
    #[Route('/update/{id}', name: 'app_developpeur_update', methods: ["GET","POST"])]
    public function update(EntityManagerInterface $entityManager,ManagerRegistry $doctrine,Request $request,int $id): Response
    {
        $developpeur = $entityManager->getRepository(Developpeur::class)->find($id);
        
        if (!$developpeur) {
            throw $this->createNotFoundException(
            'No product found for id '.$id);
        }
    
        $form=$this->createForm(DeveloppeurType::class,$developpeur);
        $form->remove('created');
        $form->remove('updated');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $developpeur->setUpdated(new \DateTime());
            $entityManager->flush();
            return $this->redirectToRoute('app_developpeur_index');
        }

        return $this->render('developpeur/update.html.twig', [
            'form' => $form->createView(),
            'developpeur' => $developpeur,  // Assurez-vous de passer l'entité devloppeur à Twig
        ]);
    }
    /**
     * Deletes a developer by ID.
     *
     * - Throws a NotFoundHttpException if the developer does not exist.
     * - Removes the developer from the database.
     *
     * @param ManagerRegistry $doctrine Doctrine service for entity management.
     * @param int $id The ID of the developer to delete.
     *
     * @return Response Redirects to the developers index page after deletion.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If no developer exists with the given ID.
     */
    #[Route('/delete/{id}', name: 'app_developpeur_delete', methods: ["GET","POST"])]
    public function delete(ManagerRegistry $doctrine,int $id): Response
    {
        $entityManager = $doctrine->getManager();
        $developpeur = $entityManager->getRepository(Developpeur::class)->find($id);

        if (!$developpeur) {
            throw $this->createNotFoundException('Aucun développeur trouvé avec l\'id '.$id);
        }

        $entityManager->remove($developpeur);
        $entityManager->flush();

        return $this->redirectToRoute('app_developpeur_index');
    }
}
