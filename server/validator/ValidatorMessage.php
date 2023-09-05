<?php

namespace Selpol\Validator;

use Stringable;

class ValidatorMessage implements Stringable
{
    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }
}