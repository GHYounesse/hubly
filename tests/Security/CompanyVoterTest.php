<?php

namespace App\Tests\Security;

use App\Entity\Projet;
use App\Entity\Societe;
use App\Entity\User;
use App\Security\Voter\CompanyVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CompanyVoterTest extends TestCase
{
    private CompanyVoter $voter;
    private Societe $atlas;
    private Societe $nour;

    protected function setUp(): void
    {
        $this->voter = new CompanyVoter();
        $this->atlas = (new Societe())->setDenomination('Atlas');
        $this->nour = (new Societe())->setDenomination('Nour');
    }

    private function vote(User $user, object $subject): int
    {
        return $this->voter->vote(new UsernamePasswordToken($user, 'main', $user->getRoles()), $subject, [CompanyVoter::ACCESS]);
    }

    private function user(array $roles, Societe ...$societes): User
    {
        $user = (new User())->setEmail('u@example.test')->setRoles($roles);
        foreach ($societes as $societe) {
            $user->addSociete($societe);
        }

        return $user;
    }

    public function testAUserSeesTheirOwnCompanyAndItsProjects(): void
    {
        $user = $this->user(['ROLE_USER'], $this->atlas);
        $project = (new Projet())->setSociete($this->atlas);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $this->atlas));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($user, $project));
    }

    public function testAUserCannotOpenAnotherCompanyOrItsProjects(): void
    {
        $user = $this->user(['ROLE_USER'], $this->atlas);
        $foreignProject = (new Projet())->setSociete($this->nour);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $this->nour));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($user, $foreignProject));
    }

    public function testAnAdministratorSeesEverything(): void
    {
        $admin = $this->user(['ROLE_ADMIN']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, $this->nour));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, new Projet()));
    }

    public function testAProjectWithoutCompanyIsForAdministratorsOnly(): void
    {
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->user(['ROLE_USER'], $this->atlas), new Projet()));
    }

    public function testOtherSubjectsAreIgnored(): void
    {
        $user = $this->user(['ROLE_USER']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote($user, new \stdClass()));
    }
}
