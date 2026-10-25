<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

/**
 * Regla de negocio que se rompió. No es un bug: no se registra en el log
 * y bootstrap/app.php la responde con el código de `$status`.
 */
abstract class BusinessException extends RuntimeException implements ShouldntReport
{
    protected int $status = 422;

    public function status(): int
    {
        return $this->status;
    }
}
