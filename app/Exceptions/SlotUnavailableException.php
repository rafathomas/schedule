<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

class SlotUnavailableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Este horário não está mais disponível. Escolha outra opção.');
    }
}
