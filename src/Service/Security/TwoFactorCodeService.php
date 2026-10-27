<?php
namespace App\Service\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class TwoFactorCodeService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $em,
        private readonly string $appName,
        private readonly string $mailFrom,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.debug%')] private readonly bool $debug,
    ) {}

    public function generateAndSend(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->setTwoFactorCode($code);
        $user->setTwoFactorExpiresAt(new \DateTimeImmutable('+5 minutes'));
        $this->em->flush();

        // Local development only: no mail server needed, read the code in var/log/dev.log
        if ($this->debug) {
            $this->logger->info(sprintf('2FA code for %s: %s', $user->getEmail(), $code));
        }

        $email = (new Email())
            ->from(new Address($this->mailFrom, $this->appName))
            ->to($user->getEmail())
            ->subject("{$this->appName} - Votre code de vérification")
            ->html(<<<HTML
                <div style="font-family:sans-serif;max-width:480px;margin:auto;">
                    <p>Bonjour,</p>
                    <p>Votre code de vérification est :</p>
                    <div style="font-size:2rem;font-weight:700;letter-spacing:8px;color:#0d6efd;margin:20px 0;">
                        $code
                    </div>
                    <p>Il expire dans <strong>5 minutes</strong>.</p>
                    <p style="color:#999;font-size:.85rem;">Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
                </div>
            HTML);

        $this->mailer->send($email);
    }
}