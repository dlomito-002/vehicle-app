@extends('layouts.app')

@section('title', 'Correo y notificaciones')

@section('content')
    <div class="page-heading">
        <div>
            <span class="page-kicker">Administración</span>
            <h1 class="page-title">Correo y notificaciones</h1>
            <p class="page-subtitle">Estado del envío de códigos de acceso y avisos de la flota.</p>
        </div>
        <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary">Auditoría</a>
    </div>

    <div class="eco-two-col">
        <section class="card eco-mail-status">
            <div class="card-header"><strong>Estado del canal</strong></div>
            <div class="card-body">
                <dl class="eco-detail">
                    <div>
                        <dt>Modo</dt>
                        <dd>
                            @if ($status['mailer'] === 'smtp')
                                <x-status-badge status="ok">SMTP activo</x-status-badge>
                            @else
                                <x-status-badge status="pending">{{ $status['mailer'] }} (no envía correos reales)</x-status-badge>
                            @endif
                        </dd>
                    </div>
                    <div><dt>Servidor</dt><dd>{{ $status['host'] ?: '—' }}{{ $status['port'] ? ':'.$status['port'] : '' }} {{ $status['scheme'] ? '('.strtoupper($status['scheme']).')' : '' }}</dd></div>
                    <div><dt>Usuario</dt><dd>{{ $status['username'] ?: '—' }}</dd></div>
                    <div><dt>Contraseña</dt><dd>{{ $status['password_set'] ? 'Configurada (oculta)' : 'Sin configurar' }}</dd></div>
                    <div><dt>Remitente</dt><dd>{{ $status['from_name'] }} &lt;{{ $status['from'] }}&gt;</dd></div>
                    <div>
                        <dt>Reciben avisos</dt>
                        <dd>
                            @forelse ($recipients as $r)
                                {{ $r->name }} ({{ $r->email }})@if (! $loop->last), @endif
                            @empty
                                Nadie marcado. Se usa <code>VEHICLE_MANAGER_EMAIL</code> si está configurado.
                            @endforelse
                        </dd>
                    </div>
                </dl>
                <p class="field-help">La configuración vive en <code>.env</code> (no se sube a GitHub). Quién recibe avisos se marca en <a href="{{ route('users.index') }}">Usuarios</a>.</p>
            </div>
        </section>

        <section class="card eco-mail-test">
            <div class="card-header"><strong>Enviar correo de prueba</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.mail.test') }}" data-single-submit>
                    @csrf
                    <label class="form-label" for="m-to">Enviar a</label>
                    <input id="m-to" type="email" name="to" value="{{ old('to', auth()->user()->email) }}" required>
                    <div class="eco-actions-row">
                        <button type="submit" class="btn btn-primary">Enviar correo de prueba</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div class="card data-table-shell">
        <div class="card-header"><strong>Pruebas recientes</strong></div>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>Fecha</th><th>Destino</th><th>Persona</th><th>Resultado</th></tr>
                </thead>
                <tbody>
                    @forelse ($recent as $row)
                        <tr>
                            <td data-label="Fecha" class="data-table-nowrap">{{ $row->created_at?->format('d/m/Y H:i') }}</td>
                            <td data-label="Destino">{{ $row->subject_id }}</td>
                            <td data-label="Persona">{{ $row->actor }}</td>
                            <td data-label="Resultado">
                                @if (($row->details['resultado'] ?? '') === 'enviado')
                                    <x-status-badge status="ok">Enviado</x-status-badge>
                                @else
                                    <x-status-badge status="anomaly">Falló</x-status-badge> <span class="muted">{{ $row->details['error'] ?? '' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="data-table-empty">Todavía no se han enviado pruebas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
