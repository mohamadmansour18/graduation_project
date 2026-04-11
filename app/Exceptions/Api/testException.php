<?php

namespace App\Exceptions\Api;

class testException extends ApiException
{
    public function __construct(
        string $message = "test message",
        array $extraContext = []
    ){
        parent::__construct(
            title: "test title" ,
            message: $message,
            status: 409,
            extraContext: $extraContext)
        ;
    }
}
