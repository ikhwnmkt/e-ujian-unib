<?php
namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index', ['tests' => Test::orderByDesc('tanggal')->get()]);
    }

    public function detail(Test $test)
    {
        return view('admin.detail', ['test' => $test->load('dosen', 'attempts.user')->loadCount('questions')]);
    }

    public function users(string $role)
    {
        abort_unless(in_array($role, ['mahasiswa', 'dosen']), 404);
        return view('admin.users', ['role' => $role, 'users' => User::where('role', $role)->orderBy('name')->get()]);
    }

    public function createUser(string $role)
    {
        $this->validateRole($role);
        return view('admin.user-form', ['role' => $role, 'user' => new User()]);
    }

    public function storeUser(Request $request, string $role)
    {
        $this->validateRole($role);
        $data = $request->validate($this->userRules($role));
        $data['role'] = $role;
        User::create($data);

        return redirect()->route('admin.users', $role)->with('ok', 'Data pengguna berhasil ditambahkan.');
    }

    public function userDetail(string $role, User $user)
    {
        $this->ensureUserRole($role, $user);
        return view('admin.user-detail', compact('role', 'user'));
    }

    public function editUser(string $role, User $user)
    {
        $this->ensureUserRole($role, $user);
        return view('admin.user-form', compact('role', 'user'));
    }

    public function updateUser(Request $request, string $role, User $user)
    {
        $this->ensureUserRole($role, $user);
        $data = $request->validate($this->userRules($role, $user));
        $data['role'] = $role;
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('admin.users.show', [$role, $user])->with('ok', 'Data pengguna berhasil diperbarui.');
    }

    private function validateRole(string $role): void
    {
        abort_unless(in_array($role, ['mahasiswa', 'dosen'], true), 404);
    }

    private function ensureUserRole(string $role, User $user): void
    {
        $this->validateRole($role);
        abort_unless($user->role === $role, 404);
    }

    private function userRules(string $role, ?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];
        $emailRule = Rule::unique('users', 'email');
        $numberRule = Rule::unique('users', 'nomor_induk');
        if ($user) {
            $emailRule->ignore($user->id);
            $numberRule->ignore($user->id);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'nomor_induk' => ['required', 'string', 'max:30', $numberRule],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'fakultas' => ['required', 'string', 'max:255'],
            'prodi' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'password' => $passwordRules,
        ];
    }
}
