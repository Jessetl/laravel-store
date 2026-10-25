<?php

namespace App\Exceptions;

final class DuplicateProductPrice extends BusinessException
{
    protected int $status = 409;

    protected $message = 'The product already has a price for the specified currency.';
}
