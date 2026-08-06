<?php

namespace App\Exceptions;

use RuntimeException;

class TesterNotClockedInException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Tester must be clocked in to take or submit a test.');
    }
}
