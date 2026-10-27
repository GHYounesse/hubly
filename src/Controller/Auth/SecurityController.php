<?php

namespace App\Controller\Auth;

use App\Service\Security\TwoFactorCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController
 *
 * @package App\Controller\Auth
 * 
 * Controller managing user authentication and logout.
 */
class SecurityController extends AbstractController
{   
    /**
     * Handles the login process.
     *
     * - If the user is already logged in, redirects them based on their role:
     *   - ROLE_ADMIN → 'app_dashboard'
     *   - Other roles → 'app_user_dashboard'
     * - If the user is not logged in, renders the login page and passes
     *   the last entered username and any authentication error.
     *
     * @param AuthenticationUtils $authenticationUtils Provides utilities to get the last authentication error and last username.
     * @param Security $security Used to check if a user is authenticated and their roles.
     *
     * @return Response The login page or a redirect response if the user is already authenticated.
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Security $security, Request $request): Response
    {
        // Déjà connecté et 2FA validée → dashboard
        if ($security->getUser() && $request->getSession()->get('2fa_verified')) {
            return $this->redirectToRoute(
                $security->isGranted('ROLE_ADMIN') ? 'app_dashboard' : 'app_user_dashboard'
            );
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Logs out the current user.
     *
     * This method will be intercepted by Symfony's security system based on the firewall configuration.
     *
     * @return void
     *
     * @throws \LogicException Always thrown to indicate that this method is not meant to be executed directly.
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    } 
}
