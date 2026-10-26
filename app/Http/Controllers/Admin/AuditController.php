<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Administración › Auditoría (ecosystem standard screen). */
class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'action' => (string) $request->query('action', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        $logs = AuditLog::query()
            ->when($filters['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('actor', 'like', '%'.$filters['q'].'%')
                ->orWhere('subject_id', 'like', '%'.$filters['q'].'%')
                ->orWhere('subject_type', 'like', '%'.$filters['q'].'%')
                ->orWhere('ip', 'like', '%'.$filters['q'].'%')))
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->when(strtotime($filters['from']) !== false && $filters['from'] !== '', fn ($q) => $q->where('created_at', '>=', $filters['from'].' 00:00:00'))
            ->when(strtotime($filters['to']) !== false && $filters['to'] !== '', fn ($q) => $q->where('created_at', '<=', $filters['to'].' 23:59:59'))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $weekAgo = now()->subDays(7);
        $stats = [
            'today' => AuditLog::where('created_at', '>=', today())->count(),
            'week' => AuditLog::where('created_at', '>=', $weekAgo)->count(),
            'logins' => AuditLog::where('action', 'login')->where('created_at', '>=', $weekAgo)->count(),
            'failures' => AuditLog::where('action', 'login_code_failed')->where('created_at', '>=', $weekAgo)->count(),
        ];

        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit.index', compact('logs', 'stats', 'actions', 'filters'));
    }
}
