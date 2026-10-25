<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * El recurso pedido no existe (o fue eliminado). bootstrap/app.php convierte
 * aquí el ModelNotFoundException de Laravel para responderlo como el resto.
 */
final class ResourceNotFound extends BusinessException
{
    protected int $status = 404;

    protected $message = 'The requested resource was not found.';

    public function __construct(ModelNotFoundException $previous)
    {
        // Mensaje explícito: con solo `previous:` Exception recibiría '' y borraría el de arriba
        parent::__construct($this->message, previous: $previous);
    }
}
