<?php
namespace App\Controller\Auth;

use App\Service\Security\TwoFactorCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TwoFactorController extends AbstractController
{
    private const SESSION_KEY = '2fa_verified';

    #[Route('/2fa/verify', name: 'app_2fa_verify', methods: ['GET', 'POST'])]
    public function verify(
        Request $request,
        Security $security,
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->getSession()->get(self::SESSION_KEY)) {
            return $this->redirectToRoute(
                $security->isGranted('ROLE_ADMIN') ? 'app_dashboard' : 'app_user_dashboard'
            );
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $digits = $request->request->all('digit') ?? [];
            $code   = implode('', $digits);

            if ($user->isTwoFactorCodeValid($code)) {
                $user->setTwoFactorCode(null);
                $user->setTwoFactorExpiresAt(null);

                $request->getSession()->set(self::SESSION_KEY, true);

                return $this->redirectToRoute(
                    $security->isGranted('ROLE_ADMIN') ? 'app_dashboard' : 'app_user_dashboard'
                );
            }

            $error = 'Code invalide ou expiré. Veuillez réessayer.';
        }

        return $this->render('security/2fa_verify.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/2fa/resend', name: 'app_2fa_resend', methods: ['POST'])]
    public function resend(TwoFactorCodeService $twoFactorCodeService): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $twoFactorCodeService->generateAndSend($user);
        $this->addFlash('success', 'Un nouveau code vous a été envoyé.');

        return $this->redirectToRoute('app_2fa_verify');
    }
}