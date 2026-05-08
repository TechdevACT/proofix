<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class OperatorController extends Controller
{
    public function index()
    {
        $operators = User::where('role', 'operator')
            ->withCount('recordings')
            ->withMax('recordings', 'recorded_at')
            ->orderBy('name')
            ->get();

        return inertia('Admin/Operators', compact('operators'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'station'  => 'nullable|string|max:100',
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'operator',
            'station'  => $data['station'] ?? null,
        ]);

        return back()->with('message', "Operator {$data['name']} berhasil ditambahkan.");
    }

    public function update(Request $request, User $operator)
    {
        // Pastikan hanya update operator (bukan admin)
        abort_if($operator->isAdmin(), 403, 'Tidak bisa mengubah akun admin.');

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => "required|email|unique:users,email,{$operator->id}",
            'password' => ['nullable', Password::min(8)],
            'station'  => 'nullable|string|max:100',
        ]);

        $operator->update([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'station' => $data['station'] ?? null,
        ]);

        // Hanya update password jika diisi
        if (! empty($data['password'])) {
            $operator->update(['password' => Hash::make($data['password'])]);
        }

        return back()->with('message', "Data operator {$operator->name} berhasil diperbarui.");
    }

    public function destroy(User $operator)
    {
        abort_if($operator->isAdmin(), 403, 'Tidak bisa menghapus akun admin.');

        $name = $operator->name;
        $operator->delete();

        return back()->with('message', "Operator {$name} berhasil dihapus.");
    }
}
