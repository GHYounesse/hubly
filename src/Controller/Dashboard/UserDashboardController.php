<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
/**
 * Class UserDashboardController
 * @package App\Controller\Dashboard
 * Controller for the user dashboard.
 *
 * - Renders the dashboard page for authenticated users.
 */
final class UserDashboardController extends AbstractController
{   
    /**
     * Displays the user dashboard.
     *
     * @return Response The rendered dashboard template.
     */
    #[Route('/user/dashboard', name: 'app_user_dashboard')]
    public function index(): Response
    {
        return $this->render('user_dashboard/index.html.twig', [
            'controller_name' => 'UserDashboardController',
        ]);
    }
}
