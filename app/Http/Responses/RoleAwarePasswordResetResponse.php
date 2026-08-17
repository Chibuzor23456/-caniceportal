<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;

class RoleAwarePasswordResetResponse implements PasswordResetResponseContract
{
    public function __construct(protected string $status) {}

    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($this->status)], 200);
        }

        $user = User::where('email', $request->input('email'))->first();

        $loginRoute = $user?->isAdmin() ? route('admin.login') : route('login');

        return redirect($loginRoute)->with('status', trans($this->status));
    }
}
