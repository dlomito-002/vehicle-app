<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendTestMailRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/** Administración › Correo y notificaciones (ecosystem standard screen). */
class MailController extends Controller
{
    public function index(): View
    {
        $mailer = (string) config('mail.default');
        $smtp = (array) config('mail.mailers.smtp', []);

        $status = [
            'mailer' => $mailer,
            'host' => $smtp['host'] ?? '',
            'port' => $smtp['port'] ?? '',
            'scheme' => $smtp['scheme'] ?? ($smtp['encryption'] ?? ''),
            'username' => $smtp['username'] ?? '',
            'password_set' => filled($smtp['password'] ?? null),
            'from' => (string) config('mail.from.address'),
            'from_name' => (string) config('mail.from.name'),
        ];

        $recipients = User::where('receives_notification_emails', true)->orderBy('name')->get(['name', 'email']);
        $recent = AuditLog::whereIn('action', ['mail_test'])->latest('id')->limit(15)->get();

        return view('admin.mail.index', compact('status', 'recipients', 'recent'));
    }

    public function test(SendTestMailRequest $request): RedirectResponse
    {
        $to = $request->validated('to');

        try {
            Mail::send('emails.test', ['sentBy' => $request->user()->email],
                fn ($message) => $message->to($to)->subject('Prueba de correo | Control de Vehículos'));
        } catch (\Throwable $e) {
            Audit::record('mail_test', null, ['resultado' => 'falló', 'error' => mb_substr($e->getMessage(), 0, 300)], 'Correo', $to);

            return back()->withErrors(['to' => 'No se pudo enviar: '.mb_substr($e->getMessage(), 0, 200)]);
        }

        Audit::record('mail_test', null, ['resultado' => 'enviado'], 'Correo', $to);

        return back()->with('status', 'Correo de prueba enviado a '.$to.'.');
    }
}
