<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('roles')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('email', 'like', '%'.$request->q.'%')
                ->orWhere('phone', 'like', '%'.$request->q.'%')))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('slug', $request->string('role'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::all(),
        ]);
    }

    public function show(User $user): View
    {
        $user->load('roles', 'propertyUsers.property', 'serviceRequests');

        return view('admin.users.show', [
            'user' => $user,
            'roles' => Role::all(),
        ]);
    }

    public function update(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:active,suspended,locked'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $old = ['status' => $user->status, 'roles' => $user->roles->pluck('slug')];

        if (isset($data['status'])) {
            abort_unless($request->user()->hasPermission('users.manage'), 403);
            abort_if($user->id === $request->user()->id && $data['status'] !== 'active', 422, 'You cannot suspend your own account.');
            $user->forceFill([
                'status' => $data['status'],
                'locked_until' => $data['status'] === 'active' ? null : $user->locked_until,
                'failed_login_attempts' => $data['status'] === 'active' ? 0 : $user->failed_login_attempts,
            ])->save();
        }

        if (isset($data['roles'])) {
            abort_unless($request->user()->hasPermission('users.manage'), 403);
            $user->roles()->sync($data['roles']);
        }

        $audit->record('user.updated', $user, $old, [
            'status' => $user->status, 'roles' => $user->roles->pluck('slug'),
        ]);

        return back()->with('success', 'User updated.');
    }
}
