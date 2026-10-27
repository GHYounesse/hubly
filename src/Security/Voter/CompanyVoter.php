<?php

namespace App\Security\Voter;

use App\Entity\Projet;
use App\Entity\Societe;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may open a company, or a project of that company?
 *
 * - an administrator: every company;
 * - any other user: only the companies assigned to them (the user_societe link).
 *
 * Without this rule, any signed-in user could open any project by guessing its id in the URL.
 *
 * @extends Voter<string, Societe|Projet>
 */
final class CompanyVoter extends Voter
{
    public const ACCESS = 'COMPANY_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ACCESS && ($subject instanceof Societe || $subject instanceof Projet);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $societe = $subject instanceof Projet ? $subject->getSociete() : $subject;

        // A project without company is not assigned to anyone: administrators only
        return $societe instanceof Societe && $user->getSocietes()->contains($societe);
    }
}
