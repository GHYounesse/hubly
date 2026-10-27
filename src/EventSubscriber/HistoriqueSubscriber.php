<?php

namespace App\EventSubscriber;

use App\Entity\Historique;
use App\Entity\Notification;
use App\Entity\ResetPasswordRequest;
use App\Entity\UptimeCheck;
use App\Entity\User;
use App\Service\Audit\ChangeFormatter;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Audit trail: records who created, changed or deleted which entity, and for changes the
 * before / after value of every field.
 *
 * Nothing is recorded outside a logged-in session (console commands, fixtures...).
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class HistoriqueSubscriber
{
    /** Technical entities that are not worth an audit line. */
    private const IGNORED_ENTITIES = [
        Historique::class,
        Notification::class,
        UptimeCheck::class,
        ResetPasswordRequest::class,
    ];

    /** @var object[] New entities: their id only exists after the INSERT, see postFlush() */
    private array $pendingCreations = [];

    public function __construct(
        private readonly Security $security,
        private readonly ChangeFormatter $formatter,
    ) {}

    public function onFlush(OnFlushEventArgs $args): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->isIgnored($entity)) {
                continue;
            }

            $changes = $this->formatter->format($uow->getEntityChangeSet($entity));
            if ($changes === []) {
                continue; // only timestamps or secrets changed
            }

            $this->record($em, $user, $entity, 'modification', $changes);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$this->isIgnored($entity)) {
                $this->record($em, $user, $entity, 'suppression', null);
            }
        }

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->isIgnored($entity)) {
                $this->pendingCreations[] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->pendingCreations === []) {
            return;
        }

        $user = $this->security->getUser();
        $created = $this->pendingCreations;
        $this->pendingCreations = [];

        if (!$user instanceof User) {
            return;
        }

        $em = $args->getObjectManager();
        foreach ($created as $entity) {
            $em->persist($this->build($em, $user, $entity, 'creation', null));
        }

        // The audit rows are ignored entities, so this second flush cannot loop
        $em->flush();
    }

    /** @param array<string, array{old: mixed, new: mixed}>|null $changes */
    private function record(EntityManagerInterface $em, User $user, object $entity, string $action, ?array $changes): void
    {
        $historique = $this->build($em, $user, $entity, $action, $changes);
        $em->persist($historique);
        // Inside onFlush new entities must be added to the unit of work by hand
        $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata(Historique::class), $historique);
    }

    /** @param array<string, array{old: mixed, new: mixed}>|null $changes */
    private function build(EntityManagerInterface $em, User $user, object $entity, string $action, ?array $changes): Historique
    {
        $class = $em->getClassMetadata($entity::class)->getName();

        return (new Historique())
            ->setUser($user)
            ->setEntiteModifiee((new \ReflectionClass($class))->getShortName())
            ->setEntiteModifieeId(method_exists($entity, 'getId') ? $entity->getId() : null)
            ->setAction($action)
            ->setChanges($changes)
            ->setDateModification(new \DateTimeImmutable());
    }

    private function isIgnored(object $entity): bool
    {
        foreach (self::IGNORED_ENTITIES as $ignored) {
            if ($entity instanceof $ignored) {
                return true;
            }
        }

        return false;
    }
}
