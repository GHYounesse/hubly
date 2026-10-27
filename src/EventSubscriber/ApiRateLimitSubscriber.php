<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Rate limits every /api request (limiter "api" in config/packages/framework.yaml).
 *
 * The key is the authenticated user, or the client IP for anonymous requests, so one noisy
 * client cannot use up the quota of the others. Responses carry the usual X-RateLimit-* headers.
 * Login attempts have their own, stricter limit (login_throttling in security.yaml).
 */
class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    private const ATTRIBUTE = '_api_rate_limit';

    public function __construct(
        #[Target('api')] private readonly RateLimiterFactoryInterface $apiLimiter,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // After the firewall (priority 8) so the user is known, before the controller
            KernelEvents::REQUEST => ['onRequest', 4],
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        $key = $user instanceof UserInterface
            ? 'user_' . $user->getUserIdentifier()
            : 'ip_' . ($request->getClientIp() ?? 'unknown');

        $limit = $this->apiLimiter->create($key)->consume(1);
        $request->attributes->set(self::ATTRIBUTE, $limit);

        if (!$limit->isAccepted()) {
            $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

            throw new TooManyRequestsHttpException($retryAfter, 'Too many requests, slow down.');
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        $limit = $event->getRequest()->attributes->get(self::ATTRIBUTE);
        if (!$limit instanceof RateLimit) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('X-RateLimit-Limit', (string) $limit->getLimit());
        $headers->set('X-RateLimit-Remaining', (string) $limit->getRemainingTokens());
    }
}
