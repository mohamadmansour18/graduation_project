<?php

namespace App\Services\Auth;

use App\Repositories\Auth\PasswordResetRepository;

class PasswordResetService
{
    public function __construct(
        protected PasswordResetRepository $passwordResetRepository
    )
    {}


}
