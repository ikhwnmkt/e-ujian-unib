<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show() { return view('profil', ['user' => auth()->user()]); }

    public function update(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'no_hp' => 'nullable|string|max:20',
            'prodi' => 'nullable|string|max:255',
            'kelas' => 'nullable|string|max:10',
            'avatar' => 'nullable|image|max:2048',
        ]);
        if ($r->hasFile('avatar')) {
            $data['avatar'] = $r->file('avatar')->store('avatars', 'public');
        }
        $r->user()->update($data);
        return back()->with('ok', 'Profil berhasil diperbarui.');
    }
}
