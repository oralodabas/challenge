<?php

namespace App\Exception;


class RepositoryException extends \Exception
{

    /**
     * @var \Exception
     */
    private $exception;

    public function __construct(\Exception $exception)
    {
        parent::__construct($exception->getMessage());

        $this->exception = $exception;
    }

    public function getException()
    {
        return $this->exception;
    }
}