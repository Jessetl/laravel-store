<?php

namespace App\Exceptions;

use RuntimeException;

final class DuplicateProductPrice extends RuntimeException
{
    protected $message = 'The product already has a price for the specified currency.';
}
