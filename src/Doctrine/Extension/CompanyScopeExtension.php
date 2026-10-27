<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Projet;
use App\Entity\Societe;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * API collections only list what the caller may see (same rule as CompanyVoter):
 * administrators see everything, other users only the companies assigned to them
 * and the projects of those companies.
 */
final class CompanyScopeExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly Security $security) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (!in_array($resourceClass, [Projet::class, Societe::class], true)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $societes = $user->getSocietes()->toArray();
        if ($societes === []) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameter = $queryNameGenerator->generateParameterName('visible_societes');

        $queryBuilder
            ->andWhere($resourceClass === Projet::class ? sprintf('%s.societe IN (:%s)', $alias, $parameter) : sprintf('%s IN (:%s)', $alias, $parameter))
            ->setParameter($parameter, $societes);
    }
}
