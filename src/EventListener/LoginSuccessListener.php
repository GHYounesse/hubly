<?php
namespace App\EventListener;

use App\Entity\User;
use App\Service\Security\TwoFactorCodeService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener]
class LoginSuccessListener
{
    public function __construct(
        private readonly TwoFactorCodeService $twoFactorCodeService,
        private readonly RouterInterface $router,
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        // The REST API (firewall "api_login") hands out a JWT instead: no session, no redirect
        if ($event->getFirewallName() !== 'main') {
            return;
        }

        /** @var User $user */
        $user = $event->getAuthenticatedToken()->getUser();

        $this->twoFactorCodeService->generateAndSend($user);

        $event->setResponse(
            new RedirectResponse($this->router->generate('app_2fa_verify'))
        );
    }
}