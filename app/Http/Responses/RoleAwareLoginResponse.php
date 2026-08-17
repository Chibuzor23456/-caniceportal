<?php

namespace App\Http\Responses;

use App\Support\PostAuthRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class RoleAwareLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        return PostAuthRedirect::redirect($request->user());
    }
}
