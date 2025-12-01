<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users; // Model untuk User
use App\Models\Admin; // Model untuk Admin
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse; // Tambahkan untuk tipe return

class AuthController extends Controller
{
    // === METHOD REGISTER USER ===
    public function register(Request $request): JsonResponse
    {
        try {
            // Validasi Input
            $validatedData = $request->validate([
                'nama' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:3',
                'no_handphone' => 'required|string|max:15',
                'alamat' => 'required|string'
            ]);

            // Buat User menggunakan Model Eloquent
            $user = Users::create([
                'nama' => $validatedData['nama'],
                'email' => $validatedData['email'],
                // Pastikan password di-hash saat registrasi!
                'password' => Hash::make($validatedData['password']),
                'no_handphone' => $validatedData['no_handphone'],
                'alamat' => $validatedData['alamat'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'data' => [
                    'id' => $user->id_users,
                    'nama' => $user->nama,
                    'email' => $user->email,
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // === METHOD LOGIN USER ===
    public function login(Request $request): JsonResponse
    {
        try {
            // Validasi input
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            // Cari user
            $user = Users::where('email', $request->email)->first();

            // Cek user ditemukan DAN password cocok (menggunakan Hash::check)
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password salah.'
                ], 401);
            }

            // Login berhasil
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil!',
                'data' => [
                    'id' => $user->id_users,
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_handphone' => $user->no_handphone,
                    'alamat' => $user->alamat
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}