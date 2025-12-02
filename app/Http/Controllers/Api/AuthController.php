<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Users; // Model untuk User
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    // === METHOD REGISTER USER ===
    public function register(Request $request): JsonResponse
    {
        try {
            // Validasi Input
            // !!! PENTING: Gunakan 'confirmed' untuk memastikan adanya password_confirmation
            $validatedData = $request->validate([
                'nama' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'no_handphone' => 'required|string|max:15',
                'alamat' => 'required|string'
            ]);

            $dataToCreate = $validatedData;

            // !!! Wajib: Hapus field yang tidak ada di tabel
            unset($dataToCreate['password_confirmation']);

            // !!! Perbaikan: Gunakan array $dataToCreate yang sudah bersih untuk Mass Assignment
            $user = Users::create([
                'nama' => $dataToCreate['nama'],
                'email' => $dataToCreate['email'],
                // Password akan otomatis di-hash oleh Model Users karena ada $casts = ['password' => 'hashed']
                'password' => $dataToCreate['password'],
                'no_handphone' => $dataToCreate['no_handphone'],
                'alamat' => $dataToCreate['alamat'],
            ]);

            // Generate token setelah registrasi berhasil
            $token = $user->createToken('authToken')->plainTextToken;

             return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil! Silakan login.',
                'data' => [
                    'user' => [
                        'id' => $user->id_users,
                        'nama' => $user->nama,
                        'email' => $user->email,
                        'no_handphone' => $user->no_handphone,
                        'alamat' => $user->alamat
                    ],
                    'token' => $token, // Kirim token ke Flutter
                    'token_type' => 'Bearer'
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
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

   
    // === METHOD LOGIN USER ===
    public function login(Request $request): JsonResponse
    {
        try {
            // Validasi Login
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Cari user
            $user = Users::where('email', $request->email)->first();

            // Cek Password
            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Kredensial yang diberikan salah.'],
                ]);
            }

            // Hapus token lama dan buat token baru untuk keamanan
            $user->tokens()->delete();
            $token = $user->createToken('authToken')->plainTextToken;

            // Login berhasil
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil!',
                'data' => [
                    'user' => [
                        'id' => $user->id_users,
                        'nama' => $user->nama,
                        'email' => $user->email,
                        'no_handphone' => $user->no_handphone,
                        'alamat' => $user->alamat
                    ],
                    'token' => $token, // Kirim token ke Flutter
                    'token_type' => 'Bearer'
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
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    // === METHOD LOGOUT USER ===
    public function logout(Request $request): JsonResponse
    {
        // Mencabut token yang sedang digunakan (Current Token)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ], 200);
    }
}