<?php

namespace App\Controller\Home;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;


/**
 * Class AccueilController
 * @package App\Controller\Home
 * Controller for the application home page (accueil).
 */
final class AccueilController extends AbstractController
{
    /**
     * Displays the home page.
     *
     * @return Response The rendered home page template.
    */
    #[Route('/', name: 'app_accueil', methods: ['GET'])]
    public function index(Security $security): Response
    {
        $user = $security->getUser();
        if ($user) {
            if ($security->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_dashboard');
            }
            return $this->redirectToRoute('app_user_dashboard');
        }
        return $this->render('home/index.html.twig');
    }
}
