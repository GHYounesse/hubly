<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TwoFactorSubscriber implements EventSubscriberInterface
{
    private const SESSION_KEY = '2fa_verified';

    private const ALLOWED_ROUTES = [
        'app_login',
        'app_logout', 
        'app_2fa_verify',
        'app_2fa_resend',
    ];

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RouterInterface $router,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 10]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (str_starts_with($request->getPathInfo(), '/api')) {
            return; // stateless JWT API, no session
        }
        $route   = $request->attributes->get('_route');
        $token   = $this->tokenStorage->getToken();

        if (!$token?->getUser() || in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        if (!$request->getSession()->get(self::SESSION_KEY)) {
            $event->setResponse(
                new RedirectResponse($this->router->generate('app_2fa_verify'))
            );
        }
    }
}