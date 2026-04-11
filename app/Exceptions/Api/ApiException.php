<?php

namespace App\Exceptions\Api;

use Exception;
class ApiException extends Exception
{
    public function __construct(
        protected string $title,
        string $message,
        protected int $status = 400,
        protected array $extraContext = []
    ){
        parent::__construct($message, $status);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getContext(): array
    {
        return $this->extraContext;
    }
}
