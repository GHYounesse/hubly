<?php

namespace App;

use App\Doctrine\Type\EncryptedTextType;
use App\Security\FieldEncryptor;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        $alreadyBooted = $this->booted;
        parent::boot();

        if (!$alreadyBooted) {
            // The encrypted Doctrine type is built by DBAL, not by the container: give it the key lazily
            EncryptedTextType::setEncryptorProvider(
                fn (): FieldEncryptor => new FieldEncryptor((string) $this->getContainer()->getParameter('app.encryption_key')),
            );
        }
    }
}
