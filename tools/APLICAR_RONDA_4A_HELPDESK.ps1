param(
    [string]$ProjectPath = "C:\xampp\htdocs\ControlVehiculosCarrousel"
)

$ErrorActionPreference = "Stop"
Set-StrictMode -Version 2.0

function Step([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Replace-Once {
    param(
        [string]$Text,
        [string]$Old,
        [string]$New,
        [string]$Label
    )
    $first = $Text.IndexOf($Old, [System.StringComparison]::Ordinal)
    if ($first -lt 0) {
        throw "No se encontro el bloque esperado: $Label"
    }
    $second = $Text.IndexOf($Old, $first + $Old.Length, [System.StringComparison]::Ordinal)
    if ($second -ge 0) {
        throw "El bloque '$Label' aparece mas de una vez. Se detuvo para evitar un reemplazo ambiguo."
    }
    return $Text.Substring(0, $first) + $New + $Text.Substring($first + $Old.Length)
}

function Replace-RegexOnce {
    param(
        [string]$Text,
        [string]$Pattern,
        [string]$Replacement,
        [string]$Label
    )
    $matches = [regex]::Matches($Text, $Pattern, [System.Text.RegularExpressions.RegexOptions]::Multiline)
    if ($matches.Count -ne 1) {
        throw "Patron '$Label': se esperaba 1 coincidencia y se encontraron $($matches.Count)."
    }
    return [regex]::Replace(
        $Text,
        $Pattern,
        $Replacement,
        [System.Text.RegularExpressions.RegexOptions]::Multiline
    )
}

function Read-Utf8([string]$Path) {
    return [System.IO.File]::ReadAllText($Path, [System.Text.Encoding]::UTF8)
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    $enc = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Content, $enc)
}

$cssPath = Join-Path $ProjectPath "resources\css\app.css"
$layoutPath = Join-Path $ProjectPath "resources\views\layouts\app.blade.php"
$logPath = Join-Path $ProjectPath "CAMBIOS_RAMA_LUIS.md"

foreach ($p in @($cssPath, $layoutPath, $logPath)) {
    if (-not (Test-Path $p)) {
        throw "No se encontro: $p"
    }
}

Push-Location $ProjectPath
try {
    Step "Verificando rama y archivos"
    $branch = (git branch --show-current).Trim()
    if ($branch -ne "luis/setup-local") {
        throw "Rama actual: '$branch'. Se requiere 'luis/setup-local'."
    }

    $css = Read-Utf8 $cssPath
    $layout = Read-Utf8 $layoutPath
    $log = Read-Utf8 $logPath

    if ($log.Contains("ronda 4A: shell + dark refinement")) {
        Write-Host "[INFO] La Ronda 4A ya aparece documentada. No se volvera a aplicar." -ForegroundColor Yellow
        exit 0
    }

    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $backupRoot = Join-Path ([System.IO.Path]::GetTempPath()) "ControlVehiculosCarrousel_Round4A"
    $backupDir = Join-Path $backupRoot $stamp
    New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
    Copy-Item $cssPath (Join-Path $backupDir "app.css")
    Copy-Item $layoutPath (Join-Path $backupDir "app.blade.php")
    Copy-Item $logPath (Join-Path $backupDir "CAMBIOS_RAMA_LUIS.md")
    Write-Host "[OK] Respaldo: $backupDir" -ForegroundColor DarkGray

    Step "Modo oscuro y tokens"
    $css = Replace-Once $css @'
	[data-theme="dark"] {
		color-scheme: dark;
		--ink: #f5f7fb;
		--muted: #aeb8ca;
		--bg: #0b0f17;
		--card: #121821;
		--border: #2a3545;
		--danger-bg: #351b20;
		--success-bg: #113226;
		--warning-bg: #332b17;
		--info-bg: #142743;
		--shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
		--shadow-lg: 0 18px 45px rgba(0, 0, 0, 0.45);
	}
'@ @'
	[data-theme="dark"] {
		color-scheme: dark;
		--brand: #7fa7ff;
		--brand-dark: #dce7ff;
		--brand-2: #9db9ff;
		--blue: #4f86ed;
		--ink: #f2f5f9;
		--muted: #9eacbd;
		--bg: #0b0f17;
		--card: #121821;
		--border: #2a3545;
		--danger: #ff9d98;
		--danger-bg: #321a20;
		--success: #75d7ab;
		--success-bg: #10291f;
		--warning: #efc66b;
		--warning-bg: #302816;
		--info: #a8c5ff;
		--info-bg: #13233a;
		--shadow: 0 2px 8px rgba(0, 0, 0, 0.24);
		--shadow-lg: 0 18px 48px rgba(0, 0, 0, 0.42);
	}
'@ "dark tokens"

    Step "Ancho util, titulos y controles"
    $css = Replace-Once $css @'
.content {
	flex: 1 0 auto;
	display: flex;
	flex-direction: column;
	padding: 24px;
	max-width: 1400px;
	width: 100%;
	margin: 0 auto;
}
'@ @'
.content {
	flex: 1 0 auto;
	display: flex;
	flex-direction: column;
	padding: 20px clamp(18px, 2.1vw, 32px);
	max-width: none;
	width: 100%;
	margin: 0;
}
'@ "content width"

    $css = Replace-Once $css @'
.page-title {
	margin: 0;
	font-size: 24px;
	font-weight: 750;
	color: var(--ink);
}
'@ @'
.page-title {
	margin: 0;
	font-size: clamp(26px, 2vw, 34px);
	line-height: 1.15;
	font-weight: 750;
	color: var(--ink);
}
'@ "page title"

    $css = Replace-Once $css @'
.btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 7px;
	border: 1px solid transparent;
	border-radius: 8px;
	padding: 9px 15px;
	font-weight: 700;
	font-size: 13.5px;
	line-height: 1.15;
	min-height: 40px;
	cursor: pointer;
	text-decoration: none;
	transition: filter 0.12s ease, background-color 0.12s ease;
}
'@ @'
.btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 7px;
	border: 1px solid transparent;
	border-radius: 10px;
	padding: 10px 16px;
	font-weight: 700;
	font-size: 13.5px;
	line-height: 1.15;
	min-height: 44px;
	cursor: pointer;
	text-decoration: none;
	transition: filter 0.12s ease, background-color 0.12s ease, border-color 0.12s ease;
}
'@ "button base"

    $css = Replace-Once $css @'
.btn-primary {
	background: var(--brand);
	color: #fff;
}
'@ @'
.btn-primary {
	background: var(--blue);
	color: #fff;
}
'@ "primary button"

    $css = Replace-Once $css @'
.btn-sm {
	padding: 7px 11px;
	font-size: 12.5px;
	min-height: 36px;
}
'@ @'
.btn-sm {
	padding: 7px 10px;
	font-size: 13px;
	min-height: 38px;
}
'@ "small button"

    $css = Replace-Once $css @'
.theme-toggle {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-width: 38px;
	height: 38px;
	padding: 0;
	border-radius: 10px;
	border: 1px solid var(--border);
	background: var(--card);
	color: var(--ink);
	font-size: 15px;
	cursor: pointer;
	transition: background-color 0.15s ease, border-color 0.15s ease;
}
'@ @'
.theme-toggle {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-width: 40px;
	height: 40px;
	padding: 0;
	border-radius: 10px;
	border: 1px solid var(--border);
	background: var(--card);
	color: var(--ink);
	font-size: 15px;
	cursor: pointer;
	transition: background-color 0.15s ease, border-color 0.15s ease;
}
'@ "theme toggle"

    Step "Shell y topbar"
    $css = Replace-Once $css @'
.topbar {
	min-height: 66px;
	background: var(--card);
	border-bottom: 1px solid var(--border);
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 14px;
	padding: 10px 24px;
	position: sticky;
	top: 4px;
	z-index: 10;
}
'@ @'
.topbar {
	min-height: 68px;
	background: var(--card);
	border-bottom: 1px solid var(--border);
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 18px;
	padding: 9px 18px;
	position: sticky;
	top: 4px;
	z-index: 10;
	box-shadow: 0 1px 0 rgba(16, 24, 40, 0.03);
}
'@ "topbar"

    $css = Replace-Once $css @'
.topbar-title {
	font-weight: 700;
	color: var(--ink);
	font-size: 15px;
}
'@ @'
.topbar-left,
.topbar-user {
	display: flex;
	align-items: center;
	gap: 10px;
}

.topbar-title {
	display: flex;
	flex-direction: column;
	min-width: 0;
	color: var(--ink);
	text-decoration: none;
	line-height: 1.15;
}

.topbar-section {
	font-size: 11px;
	font-weight: 700;
	letter-spacing: 0.05em;
	text-transform: uppercase;
	color: var(--muted);
}

.topbar-page {
	font-size: 14px;
	font-weight: 750;
	color: var(--ink);
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.user-summary {
	min-width: 0;
	text-align: right;
	line-height: 1.2;
}

.user-summary strong {
	display: block;
	max-width: 190px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	font-size: 13px;
}

.portal-btn {
	white-space: nowrap;
}
'@ "topbar structure"

    $css = Replace-Once $css @'
.side-link.active {
	background: rgba(255, 255, 255, 0.16);
	color: #fff;
	box-shadow: inset 3px 0 0 var(--pink);
}

.main-wrap {
'@ @'
.side-link.active {
	background: rgba(255, 255, 255, 0.16);
	color: #fff;
	box-shadow: inset 3px 0 0 var(--pink);
}

.side-icon {
	width: 25px;
	height: 25px;
	display: grid;
	place-items: center;
	flex: 0 0 25px;
	font-size: 18px;
	line-height: 1;
}

.side-label {
	min-width: 0;
}

.main-wrap {
'@ "sidebar icons"

    $darkShell = @'
/* ==========================================================================
   Dark shell refinement - aligned with helpdesk-carrousel.
   ========================================================================== */
html[data-theme="dark"] body,
html[data-theme="dark"] .app-shell,
html[data-theme="dark"] .main-wrap {
	background: #0b0f17;
	color: var(--ink);
}

html[data-theme="dark"] .sidebar {
	background: #0f172a;
	border-right: 1px solid rgba(255, 255, 255, 0.07);
}

html[data-theme="dark"] .sidebar-brand {
	border-bottom-color: rgba(255, 255, 255, 0.08);
}

html[data-theme="dark"] .sidebar-brand strong {
	color: #f7f9fc;
}

html[data-theme="dark"] .sidebar-brand span,
html[data-theme="dark"] .nav-section {
	color: #8796aa;
}

html[data-theme="dark"] .side-link {
	color: #d8e0eb;
}

html[data-theme="dark"] .side-link:hover {
	background: rgba(255, 255, 255, 0.055);
	color: #fff;
}

html[data-theme="dark"] .side-link.active {
	background: rgba(127, 167, 255, 0.13);
	color: #fff;
	box-shadow: inset 3px 0 0 #7fa7ff;
}

html[data-theme="dark"] .side-link.active .side-icon {
	color: #9db9ff;
}

html[data-theme="dark"] .topbar {
	background: #0d141f;
	border-bottom-color: #2a3545;
	box-shadow: 0 1px 0 rgba(255, 255, 255, 0.02);
}

html[data-theme="dark"] .topbar-title,
html[data-theme="dark"] .topbar-page,
html[data-theme="dark"] .user-summary strong {
	color: #f7f9fc;
}

html[data-theme="dark"] .topbar-section {
	color: #93a2b5;
}

html[data-theme="dark"] .theme-toggle,
html[data-theme="dark"] .btn-outline-secondary {
	background: #121821;
	border-color: #3a485c;
	color: #eef3f8;
}

html[data-theme="dark"] .theme-toggle:hover,
html[data-theme="dark"] .btn-outline-secondary:hover {
	background: #1d2836;
	border-color: #4a5b72;
}

html[data-theme="dark"] .btn-primary {
	background: #346fd1;
	border-color: #346fd1;
	color: #fff;
}

'@

    $cardsMarker = @'
/* ==========================================================================
   Tarjetas / badges / botones reutilizables (complementan las utilidades
'@
    $css = Replace-Once $css $cardsMarker ($darkShell + $cardsMarker) "dark shell insertion"

    $responsive = @'
@media (max-width: 760px) {
	.topbar {
		gap: 8px;
		padding-inline: 12px;
	}

	.topbar-left,
	.topbar-user {
		gap: 6px;
	}

	.topbar-section,
	.user-summary,
	.topbar-primary-action .topbar-primary-label,
	.portal-btn .portal-label {
		display: none;
	}

	.topbar-page {
		max-width: 125px;
	}

	.topbar-primary-action,
	.portal-btn {
		min-width: 40px;
		width: 40px;
		padding-inline: 0;
	}
}

'@
    $gridMarker = @'
@media (max-width: 760px) {
	.grid-2,
'@
    $css = Replace-Once $css $gridMarker ($responsive + $gridMarker) "topbar responsive"

    Step "Layout Blade"
    # Robust title replacement: no dependency on accented source text.
    $layout = Replace-RegexOnce $layout '<title>@yield\(''title'',\s*''[^'']+''\)</title>' '<title>@yield(''title'', ''Control de Vehiculos'') · Carrousel</title>' "document title"

    $layout = Replace-Once $layout @'
                    ['route' => 'vehicles.index', 'label' => 'Vehículos', 'icon' => '🚗'],
                    ['route' => 'users.index', 'label' => 'Usuarios', 'icon' => '☺'],
'@ @'
                    ['route' => 'vehicles.index', 'label' => 'Vehículos', 'icon' => '◇'],
                    ['route' => 'users.index', 'label' => 'Usuarios', 'icon' => '♟'],
'@ "admin icons"

    $layout = Replace-Once $layout @'
                            <span aria-hidden="true">{{ $link['icon'] }}</span>
                            <span>{{ $link['label'] }}</span>
'@ @'
                            <span class="side-icon" aria-hidden="true">{{ $link['icon'] }}</span>
                            <span class="side-label">{{ $link['label'] }}</span>
'@ "sidebar classes"

    $layout = Replace-RegexOnce $layout '(?s)\s{12}<header class="topbar">.*?\s{12}</header>' @'
            <header class="topbar">
                <div class="topbar-left">
                    <button class="sidebar-toggle theme-toggle" type="button" @click="open = !open" aria-label="Abrir menu">☰</button>
                    <button class="sidebar-toggle-desktop theme-toggle" type="button"
                            @click="collapsed = !collapsed; try { localStorage.setItem('carrousel-sidebar-collapsed', collapsed ? '1' : '0') } catch (e) {}"
                            title="Ocultar/mostrar panel" aria-label="Ocultar/mostrar panel">☰</button>
                    <a href="{{ route('dashboard') }}" class="topbar-title">
                        <span class="topbar-section">Control de Vehiculos</span>
                        <strong class="topbar-page">@yield('title', 'Panel')</strong>
                    </a>
                </div>

                <div class="topbar-user">
                    <a href="{{ route('receptions.create') }}" class="btn btn-primary btn-sm topbar-primary-action" title="Nueva recepcion">
                        <span aria-hidden="true">＋</span>
                        <span class="topbar-primary-label">Nueva recepcion</span>
                    </a>
                    <button class="theme-toggle" type="button" data-theme-toggle title="Cambiar apariencia" aria-label="Cambiar apariencia">◐</button>
                    <a class="btn btn-outline-secondary btn-sm portal-btn" href="https://portal.carrousel-apps.com/portal/" title="Volver al Portal">
                        <span aria-hidden="true">←</span>
                        <span class="portal-label">Portal</span>
                    </a>
                    <div class="user-summary">
                        <strong>{{ auth()->user()->name }}</strong>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Salir</button>
                    </form>
                </div>
            </header>
'@ "topbar markup"

    $log += @'

## REDISENO VISUAL - ronda 4A: shell + dark refinement alineados al Helpdesk (2026-09-22)

Se comparo directamente contra el estandar vigente de `FernandoZL/helpdesk-carrousel`, incluyendo `shell-v2.css`, `visual-system.css`, `dark-refinement.css` y `data-tables.css`.

- Modo oscuro: tokens y superficies finales alineados al Helpdesk.
- Shell oscuro: sidebar, topbar, navegacion activa, botones outline y CTA principal.
- Ancho util: `.content` deja de limitarse a 1400px.
- Topbar: jerarquia seccion/pagina, acceso al Portal y comportamiento responsive.
- Sidebar: iconografia comun mediante `.side-icon` y `.side-label`.
- Botones/titulos: densidad y escala alineadas al sistema vigente.
- Alcance: solo presentacion/layout. No se modifican rutas, permisos, controladores, modelos, migraciones, datos ni flujos.

No se incorporan busqueda, notificaciones ni modulos propios del Helpdesk.
'@

    Step "Escribiendo archivos"
    Write-Utf8NoBom $cssPath $css
    Write-Utf8NoBom $layoutPath $layout
    Write-Utf8NoBom $logPath $log
    Write-Host "[OK] Archivos actualizados." -ForegroundColor Green

    Step "Validando vistas"
    php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw "Fallo php artisan view:clear" }
    php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw "Fallo php artisan view:cache" }

    Step "Build frontend"
    cmd /c npm run build
    if ($LASTEXITCODE -ne 0) { throw "Fallo npm run build" }

    Step "Tests"
    php artisan test
    if ($LASTEXITCODE -ne 0) { throw "Fallo php artisan test" }

    Step "Resultado"
    git status --short
    Write-Host ""
    Write-Host "[OK] Ronda 4A aplicada y validada." -ForegroundColor Green
}
finally {
    Pop-Location
}
