<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // Ajoute le message flash pour Inertia
        $request->session()->flash('logout_message', 'Vous avez bien été déconnecté.');

        // Redirige vers la page de login
        return redirect()->route('login');
    }
}
