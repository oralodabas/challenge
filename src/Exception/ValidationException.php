<?php


namespace App\Exception;


class ValidationException extends \Exception
{
    private $violations;

    public function __construct(array $violations)
    {
        parent::__construct('Validation error');

        $this->violations = $violations;
    }

    public function getErrors()
    {
        return $this->violations;
    }
}