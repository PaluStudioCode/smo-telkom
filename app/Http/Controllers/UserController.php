<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Method ini digunakan untuk menampilkan daftar pengguna.
     * Menerima Request untuk keperluan filtering (pencarian, peran, status), sorting, dan pagination.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('user.view');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in($this->roleValues())],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['name', 'email', 'role', 'is_active', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ]);

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 10);

        $users = User::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('is_active', $status === 'active'))
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user) => $this->serializeUser($user));

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'roles' => $this->roles(),
            'statusOptions' => [
                ['value' => 'active', 'label' => 'Aktif'],
                ['value' => 'inactive', 'label' => 'Tidak Aktif'],
            ],
        ]);
    }

    /**
     * Method untuk menyimpan data pengguna baru ke database.
     * StoreUserRequest digunakan untuk memvalidasi input secara otomatis sebelum kode di method ini dijalankan.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('user.create');

        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Method untuk memperbarui data pengguna yang sudah ada.
     * UpdateUserRequest melakukan validasi khusus saat pembaruan (misal: mengabaikan email yang sama milik user ini).
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('user.update');

        $validated = $request->validated();

        if (filled($validated['password'] ?? null)) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('user.update');

        if ($request->user()->is($user)) {
            return back()->with('error', 'Anda tidak dapat mengubah status akun sendiri.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update([
            'is_active' => $validated['is_active'],
        ]);

        return back()->with('success', $user->is_active
            ? 'Akun pengguna berhasil diaktifkan.'
            : 'Akun pengguna berhasil dinonaktifkan.');
    }

    /**
     * Method untuk menghapus data pengguna dari database.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('user.delete');

        if ($request->user()->is($user)) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function roles(): array
    {
        return [
            ['value' => User::ROLE_SUPER_ADMIN, 'label' => 'Super Admin'],
            ['value' => User::ROLE_ADMIN_INPUTER, 'label' => 'Admin / Inputer'],
            ['value' => User::ROLE_ACCOUNT_MANAGER, 'label' => 'Account Manager'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function roleValues(): array
    {
        return collect($this->roles())->pluck('value')->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => collect($this->roles())->firstWhere('value', $user->role)['label'] ?? $user->role,
            'phone' => $user->phone,
            'bio' => $user->bio,
            'is_active' => $user->is_active,
            'status_label' => $user->is_active ? 'Aktif' : 'Tidak Aktif',
            'created_at' => $user->created_at?->format('Y-m-d H:i'),
            'has_operational_records' => $user->hasOperationalRecords(),
        ];
    }
}
