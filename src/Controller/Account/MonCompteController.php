<?php

namespace App\Controller\Account;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use App\Form\UserType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
/**
 * Class MonCompteController
 * @package App\Controller\Account
 * Controller managing the "My Account" section for users.
 *
 * - Handles viewing and updating the currently authenticated user's account.
 * - Uses EntityManagerInterface (injected per-method) to fetch and persist User entities.
 * - Ensures that only authenticated users can access the account pages.
 */
final class MonCompteController extends AbstractController
{   
    // Route pour la page de compte
    /**
     * Displays the account page for the currently authenticated user.
     *
     * - Uses `EntityManagerInterface` to access User data if needed.
     * - Removes sensitive fields (password, created, updated) from the form before rendering.
     * - Throws AccessDeniedException if the user is not authenticated.
     *
     * @param EntityManagerInterface $entityManager The entity manager to handle user entities.
     *
     * @return Response The rendered account page with the user form.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException If no user is logged in.
     */
    #[Route('/compte', name: 'app_mon_compte', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager  ): Response
    { 
        // Récupère l'utilisateur connecté
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->remove('password');
        $form->remove('updated');
        $form->remove('created');
        
        // Vérifie que l'utilisateur est authentifié
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        return $this->render('compte/index.html.twig', [
            'user' => $user,
            'form' => $form, 
        ]);
    }


    // Route pour la page de compte
    /**
     * Displays a user-specific account page for the currently authenticated user.
     *
     * - Uses `EntityManagerInterface` to access User data if needed.
     * - Removes sensitive fields (password, created, updated) from the form before rendering.
     * - Throws AccessDeniedException if the user is not authenticated.
     *
     * @param EntityManagerInterface $entityManager The entity manager to handle user entities.
     *
     * @return Response The rendered user-specific account page with the user form.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException If no user is logged in.
     */
    #[Route('/compte/user', name: 'app_compte_user', methods: ['GET'])]
    public function compte_user(EntityManagerInterface $entityManager  ): Response
    { 
        // Récupère l'utilisateur connecté
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->remove('password');
        $form->remove('updated');
        $form->remove('created');
        
        // Vérifie que l'utilisateur est authentifié
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }
    
        return $this->render('compte/index_user.html.twig', [
            'user' => $user,
            'form' => $form, 
        ]);
    }

    // Route pour la mise à jour du compte

    /**
     * Updates the currently authenticated user's account.
     *
     * - Uses `ManagerRegistry` and `EntityManagerInterface` to fetch and persist User entities.
     * - Handles the submission of the User form and updates the `updated` timestamp.
     * - Throws NotFoundHttpException if the User with the given ID does not exist.
     *
     * @param ManagerRegistry $doctrine Doctrine registry to access the entity manager.
     * @param Request $request The current HTTP request.
     * @param int $id The ID of the user to update.
     * @param EntityManagerInterface $entityManager The entity manager to persist changes.
     *
     * @return Response The rendered update form or a redirect after successful update.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If the user does not exist.
     */
    #[Route('/compte/update/{id}', name: 'app_mon_compte_update', methods: ['GET', 'POST'])]
    public function update(ManagerRegistry $doctrine, Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->remove('password');
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdated(new \DateTime());
            $entityManager->flush();
            return $this->redirectToRoute('app_mon_compte');
        }

        return $this->render('compte/update.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
