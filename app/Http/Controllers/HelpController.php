<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHelpRequest;
use App\Mail\HelpRequestMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function create(): View
    {
        return view('help.create');
    }

    public function store(StoreHelpRequest $request): RedirectResponse
    {
        $managerEmail = config('vehicle.manager_email');

        if (! $managerEmail) {
            return back()
                ->withInput()
                ->withErrors(['message' => 'No hay un correo de soporte configurado. Contacta a un administrador.']);
        }

        Mail::to($managerEmail)->send(new HelpRequestMail(
            reporter: $request->user(),
            message: $request->validated('message'),
        ));

        return redirect()->route('help.create')->with('status', 'Tu reporte fue enviado. Gracias.');
    }
}
