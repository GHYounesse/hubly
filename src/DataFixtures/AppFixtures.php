<?php

namespace App\DataFixtures;

use App\Entity\Developpeur;
use App\Entity\Hebergement;
use App\Entity\Historique;
use App\Entity\Notification;
use App\Entity\Projet;
use App\Entity\Societe;
use App\Entity\Technologie;
use App\Entity\UptimeCheck;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Demo data: a small agency with clients, projects, hosting plans and an uptime history.
 *
 *   php bin/console doctrine:fixtures:load --group=demo
 *
 * Demo accounts (password: Demo1234!):
 *   admin@hubly.test   administrator
 *   equipe@hubly.test  regular user
 */
class AppFixtures extends Fixture implements FixtureGroupInterface
{
    private const PASSWORD = 'Demo1234!';

    public function __construct(private readonly UserPasswordHasherInterface $hasher) {}

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();

        // ------------------------------------------------------------ users
        $admin = $this->user($manager, 'admin@hubly.test', ['ROLE_ADMIN'], 'Salma', 'Benani');
        $equipe = $this->user($manager, 'equipe@hubly.test', ['ROLE_USER'], 'Yassine', 'Idrissi');

        // ------------------------------------------------------ technologies
        $techs = [];
        foreach ([
            ['Symfony', '7.2'], ['PostgreSQL', '16'], ['React', '18'],
            ['Tailwind CSS', '3.4'], ['Docker', '27'], ['WordPress', '6.6'],
        ] as [$nom, $version]) {
            $techs[$nom] = (new Technologie())->setNom($nom)->setVersion($version)->setLogo(null)
                ->setCreated($now)->setUpdated($now);
            $manager->persist($techs[$nom]);
        }

        // ---------------------------------------------------------- hosting
        $hostings = [];
        foreach ([
            ['atlas-immo.ma', 'OVH', 'VPS', '+12 days', true, '14.99', 'mensuel'],
            ['boutique-nour.ma', 'Hostinger', 'Mutualisé', '+30 days', true, '89.00', 'annuel'],
            ['alaoui-avocats.ma', 'o2switch', 'Mutualisé', '-3 days', false, '96.00', 'annuel'],
            ['portail-nour.com', 'AWS', 'EC2', '+200 days', true, '32.50', 'mensuel'],
            ['atlas-vitrine.ma', 'Vercel', 'Plateforme', '+90 days', true, '0.00', 'mensuel'],
        ] as [$site, $fournisseur, $type, $expire, $actif, $prix, $periodicite]) {
            $h = (new Hebergement())
                ->setSite($site)
                ->setFournisseur($fournisseur)
                ->setType($type)
                ->setUrl('https://' . $site)
                ->setDateExpiration(new \DateTime($expire))
                ->setEstActif($actif)
                ->setPrix($prix)
                ->setPeriodicite($periodicite)
                ->setAcces('Accès communiqués par le client (voir le coffre de mots de passe).');
            $h->setCreated($now)->setUpdated($now);
            $manager->persist($h);
            $hostings[$site] = $h;
        }

        // ----------------------------------------------------------- clients
        $societes = [];
        foreach ([
            ['Atlas Immobilier', 'Agence immobilière à Casablanca', 'contact@atlas-immo.ma'],
            ['Boutique Nour', 'Boutique de décoration en ligne', 'bonjour@boutique-nour.ma'],
            ['Cabinet Alaoui', 'Cabinet d’avocats d’affaires', 'secretariat@alaoui-avocats.ma'],
        ] as [$denomination, $description, $email]) {
            $s = (new Societe())
                ->setDenomination($denomination)
                ->setStatut('actif')
                ->setDescription($description)
                ->setEmail($email)
                ->setLogo('images/hubly-mark.svg');
            $manager->persist($s);
            $societes[$denomination] = $s;
        }

        // The regular user only works for two companies: they cannot open the third one (see CompanyVoter)
        $equipe->addSociete($societes['Atlas Immobilier']);
        $equipe->addSociete($societes['Boutique Nour']);

        // ------------------------------------------------------- developers
        $developers = [];
        foreach ([
            ['dev.amine@hubly.test', 'Amine', 'Tazi'],
            ['dev.sara@hubly.test', 'Sara', 'El Fassi'],
            ['dev.omar@hubly.test', 'Omar', 'Berrada'],
        ] as [$email, $prenom, $nom]) {
            $devUser = $this->user($manager, $email, ['ROLE_USER'], $prenom, $nom);
            $dev = (new Developpeur())->setUser($devUser)->setPrenom($prenom)->setNom($nom)->setEmail($email)
                ->setTelephone('06 00 00 00 0' . count($developers));
            $dev->setCreated($now)->setUpdated($now);
            $manager->persist($dev);
            $developers[$prenom] = $dev;
        }

        // ---------------------------------------------------------- projects
        $projects = [];
        foreach ([
            ['Refonte du site Atlas', 'Site vitrine', 'en cours', 'urgent', 'Atlas Immobilier', 'Amine', 'atlas-immo.ma', ['Symfony', 'PostgreSQL', 'Tailwind CSS'], '+20 days', false],
            ['Portail client Nour', 'Application web', 'terminé', 'normal', 'Boutique Nour', 'Sara', 'portail-nour.com', ['React', 'Docker'], '-15 days', true],
            ['Boutique en ligne Nour', 'E-commerce', 'en cours', 'moyen', 'Boutique Nour', 'Sara', 'boutique-nour.ma', ['WordPress', 'Docker'], '+45 days', false],
            ['Site vitrine Atlas', 'Site vitrine', 'à faire', 'normal', 'Atlas Immobilier', 'Omar', 'atlas-vitrine.ma', ['React', 'Tailwind CSS'], '+60 days', false],
            ['Espace documents Alaoui', 'Intranet', 'en cours', 'tres urgent', 'Cabinet Alaoui', 'Amine', 'alaoui-avocats.ma', ['Symfony', 'PostgreSQL'], '+8 days', false],
            ['API de réservation', 'API', 'à faire', 'moyen', 'Atlas Immobilier', 'Omar', null, ['Symfony', 'PostgreSQL', 'Docker'], '+75 days', false],
        ] as [$nom, $type, $status, $priorite, $client, $devName, $site, $stack, $livraison, $valide]) {
            $p = (new Projet())
                ->setNom($nom)
                ->setType($type)
                ->setDescription('Projet de démonstration : ' . lcfirst($type) . ' pour ' . $client . '.')
                ->setStatus($status)
                ->setPriorite($priorite)
                ->setSociete($societes[$client])
                ->setDeveloppeurPrincipal($developers[$devName])
                ->setSuiviPar($equipe)
                ->setCommercial($admin)
                ->setDateCommande(new \DateTime('-60 days'))
                ->setDateLivraison(new \DateTime($livraison))
                ->setProjetTeste($valide)
                ->setProjetValide($valide)
                ->setUrl($site ? 'https://' . $site : null);
            $p->setCreated($now)->setUpdated($now);

            foreach ($stack as $techName) {
                $p->addTechnologie($techs[$techName]);
            }
            $p->addDeveloppeur($developers[$devName]);
            if ($site && isset($hostings[$site])) {
                $p->addHebergement($hostings[$site]);
            }

            $manager->persist($p);
            $projects[$nom] = $p;
        }

        // TLS certificates (normally filled by app:check-ssl)
        foreach ([
            'Refonte du site Atlas' => '+9 days',
            'Portail client Nour' => '+200 days',
            'Boutique en ligne Nour' => '+45 days',
        ] as $name => $expires) {
            $projects[$name]->setSslExpiresAt(new \DateTimeImmutable($expires))->setSslCheckedAt(new \DateTimeImmutable('-2 hours'));
        }

        // ------------------------------------------------- uptime history
        // 30 checks per project, one every 6 hours; a short outage on two of them
        foreach ($projects as $nom => $project) {
            if (!$project->getUrl()) {
                continue;
            }
            $outageAt = match ($nom) {
                'Refonte du site Atlas' => [4, 5],
                'Espace documents Alaoui' => [11],
                default => [],
            };
            for ($i = 29; $i >= 0; --$i) {
                $up = !in_array($i, $outageAt, true);
                $check = (new UptimeCheck())
                    ->setProjet($project)
                    ->setCheckedAt(new \DateTimeImmutable(sprintf('-%d hours', $i * 6)))
                    ->setSuccess($up)
                    ->setStatusCode($up ? 200 : 503)
                    ->setResponseTimeMs($up ? 180 + (($i * 37) % 240) : 5000)
                    ->setError($up ? null : 'Service Unavailable');
                $manager->persist($check);
            }
        }

        // ------------------------------------------------------ notifications
        $alerts = [
            [$hostings['atlas-immo.ma'], null, 'Expiration proche', "L'hébergement 'OVH' expire dans 12 jours."],
            [null, $projects['Espace documents Alaoui'], "Problème avec l'URL du projet", "Le projet 'Espace documents Alaoui' a une URL inaccessible : https://alaoui-avocats.ma. Détail : URL inaccessible (code 503)"],
        ];
        foreach ($alerts as [$hosting, $project, $title, $message]) {
            $n = (new Notification())
                ->setTitle($title)
                ->setMessage($message)
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime())
                ->setIsRead(false)
                ->setHebergement($hosting)
                ->setProjet($project);
            $manager->persist($n);
        }

        $manager->flush();

        // ------------------------------------------------- audit trail
        // Entries are normally written by HistoriqueSubscriber; these make the history page demo-able.
        $audit = [
            [$admin, 'Projet', 'Refonte du site Atlas', 'modification', '-2 hours', [
                'status' => ['old' => 'à faire', 'new' => 'en cours'],
                'priorite' => ['old' => 'normal', 'new' => 'urgent'],
            ]],
            [$equipe, 'Hebergement', 'atlas-immo.ma', 'modification', '-1 day', [
                'dateExpiration' => ['old' => (new \DateTime('+12 days'))->modify('-365 days')->format('Y-m-d'), 'new' => (new \DateTime('+12 days'))->format('Y-m-d')],
                'estActif' => ['old' => false, 'new' => true],
            ]],
            [$admin, 'Societe', 'Boutique Nour', 'creation', '-3 days', null],
            [$admin, 'Projet', 'Portail client Nour', 'modification', '-5 days', [
                'projetValide' => ['old' => false, 'new' => true],
                'remarques' => ['old' => null, 'new' => 'Recette signée par le client.'],
            ]],
        ];
        foreach ($audit as [$author, $entity, $label, $action, $when, $changes]) {
            $entry = (new Historique())
                ->setUser($author)
                ->setEntiteModifiee($entity)
                ->setAction($action)
                ->setChanges($changes)
                ->setDateModification(new \DateTime($when));
            $id = match ($entity) {
                'Projet' => $projects[$label]->getId(),
                'Hebergement' => $hostings[$label]->getId(),
                'Societe' => $societes[$label]->getId(),
            };
            $entry->setEntiteModifieeId($id);
            $manager->persist($entry);
        }

        $manager->flush();
    }

    /** @param string[] $roles */
    private function user(ObjectManager $manager, string $email, array $roles, string $prenom, string $nom): User
    {
        $now = new \DateTime();
        $user = new User();
        $user->setEmail($email)->setRoles($roles)->setStatut('actif')->setPrenom($prenom)->setNom($nom);
        $user->setPassword($this->hasher->hashPassword($user, self::PASSWORD));
        $user->setCreated($now)->setUpdated($now);
        $manager->persist($user);

        return $user;
    }
}
