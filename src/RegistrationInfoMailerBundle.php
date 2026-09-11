<?php

declare(strict_types=1);

namespace MenAtWork\RegistrationInfoMailerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class RegistrationInfoMailerBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}

