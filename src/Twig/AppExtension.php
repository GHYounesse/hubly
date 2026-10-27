<?php

namespace App\Twig;

use App\Repository\DeveloppeurRepository;
use App\Repository\HebergementRepository;
use App\Repository\NotificationRepository;
use App\Repository\ProjetRepository;
use App\Repository\SocieteRepository;
use App\Repository\TechnologieRepository;
use App\Repository\UserRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $notificationRepository;
    private $developpeurRepository;
    private $hebergementRepository;
    private $projetRepository;
    private $societeRepository;
    private $technologieRepository;
    private $userRepository;

    public function __construct(
        NotificationRepository $notificationRepository,
        DeveloppeurRepository $developpeurRepository,
        HebergementRepository $hebergementRepository,
        ProjetRepository $projetRepository,
        SocieteRepository $societeRepository,
        TechnologieRepository $technologieRepository,
        UserRepository $userRepository
    )
    {
        $this->notificationRepository = $notificationRepository;
        $this->developpeurRepository = $developpeurRepository;
        $this->hebergementRepository = $hebergementRepository;
        $this->projetRepository = $projetRepository;
        $this->societeRepository = $societeRepository;
        $this->technologieRepository = $technologieRepository;
        $this->userRepository = $userRepository;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('audit_field', [$this, 'auditFieldLabel']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getNotification', [$this, 'getNotification']),
            new TwigFunction('getNotificationCount', [$this, 'getNotificationCount']),
            new TwigFunction('getNotificationsForDropdown', [$this, 'getNotificationsForDropdown']),
            new TwigFunction('getDescriptionHistorique', [$this, 'getDescriptionHistorique']),
        ];
    }

    public function getNotification()
    {
        $notifications = $this->notificationRepository->findBy(['isRead' => 0]);

        return $notifications;
    }

    public function getNotificationCount(): int
    {
        return $this->notificationRepository->countUnread();
    }

    public function getNotificationsForDropdown(): array
    {
        return $this->notificationRepository->findLatestUnread(5);
    }

    /**
     * One-line sentence describing an audit entry, e.g. "Le projet Atlas a été modifié par admin@hubly.test".
     * Works for entities that no longer exist (deleted rows keep their audit entry).
     */
    public function getDescriptionHistorique($entite, $entiteId, $action, $userEmail)
    {
        // entity => [article + noun, feminine, repository, getter of a readable name]
        $entities = [
            'Developpeur' => ['Le développeur', false, $this->developpeurRepository, fn ($e) => trim($e->getNom() . ' ' . $e->getPrenom())],
            'Hebergement' => ["L'hébergement", false, $this->hebergementRepository, fn ($e) => $e->getSite()],
            'Projet' => ['Le projet', false, $this->projetRepository, fn ($e) => $e->getNom()],
            'Technologie' => ['La technologie', true, $this->technologieRepository, fn ($e) => $e->getNom()],
            'Societe' => ['La société', true, $this->societeRepository, fn ($e) => $e->getDenomination()],
            'User' => ["L'utilisateur", false, $this->userRepository, fn ($e) => trim($e->getNom() . ' ' . $e->getPrenom())],
        ];

        if (!isset($entities[$entite])) {
            return '';
        }

        [$noun, $feminine, $repository, $label] = $entities[$entite];

        $subject = null;
        if ($entiteId !== null && $action !== 'suppression') {
            $found = $repository->find($entiteId);
            $subject = $found ? $label($found) : null;
        }
        $subject = $subject !== null && $subject !== '' ? $subject : '#' . ($entiteId ?? '?');

        $verb = match ($action) {
            'creation' => $feminine ? 'créée' : 'créé',
            'suppression' => $feminine ? 'supprimée' : 'supprimé',
            'modification' => $feminine ? 'modifiée' : 'modifié',
            default => null,
        };

        return $verb === null ? '' : sprintf('%s %s a été %s par %s', $noun, $subject, $verb, $userEmail);
    }

    /** "dateExpiration" becomes "Date expiration". */
    public function auditFieldLabel(string $field): string
    {
        $words = strtolower((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $field));

        return ucfirst($words);
    }
}
