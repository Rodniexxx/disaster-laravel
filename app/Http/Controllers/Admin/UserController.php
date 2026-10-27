<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leaderboard;
use App\Models\PlayerProgress;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $users = User::query()
            ->with('playerProfile')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $sessions = Leaderboard::query()
            ->selectRaw('user_id, COUNT(*) as sessions, MAX(score) as best_score')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $completed = PlayerProgress::query()
            ->where('status', PlayerProgress::STATUS_COMPLETED)
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $editing = null;
        if ($request->filled('edit')) {
            $editing = User::query()->find((int) $request->query('edit'));
        }

        return view('admin.users.index', [
            'users' => $users,
            'sessions' => $sessions,
            'completed' => $completed,
            'search' => $search,
            'status' => $status,
            'editing' => $editing,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => true,
        ]);

        $this->audit->log($request->user(), 'created user', 'users', ['id' => $user->id, 'username' => $user->username]);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        if ($user->isAdmin() && $data['role'] !== User::ROLE_ADMIN && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'You cannot demote the last active administrator.');
        }

        $user->fill([
            'username' => $data['username'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        if (! empty($data['password'])) {
            $user->password_hash = Hash::make($data['password']);
        }

        $user->save();

        $this->audit->log($request->user(), 'updated user', 'users', ['id' => $user->id]);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->isActive() && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'You cannot deactivate the last active administrator.');
        }

        $user->forceFill(['is_active' => ! $user->isActive()])->save();

        $this->audit->log($request->user(), 'updated user status', 'users', [
            'id' => $user->id,
            'is_active' => (int) $user->is_active,
        ]);

        return back()->with('status', 'User status updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($this->isLastActiveAdmin($user)) {
            return back()->with('error', 'You cannot delete the last active administrator.');
        }

        $user->delete();

        $this->audit->log($request->user(), 'deleted user', 'users', ['id' => $user->id]);

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->doesntExist();
    }
}
