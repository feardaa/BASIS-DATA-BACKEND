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
            $validatedData = $request->validate([
                'nama' => 'required|string|max:100',
                // Cek email unik di tabel 'users'
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:3',
                'no_handphone' => 'required|string|max:15',
                'alamat' => 'required|string'
            ]);

            // Buat User menggunakan Model Eloquent
            $user = Users::create([
                'nama' => $validatedData['nama'],
                'email' => $validatedData['email'],

                // HANYA LAKUKAN HASH SEKALI di sini
                'password' => Hash::make($validatedData['password']),

                'no_handphone' => $validatedData['no_handphone'],
                'alamat' => $validatedData['alamat'],
            ]);

            // Generate token setelah registrasi berhasil
            // Laravel Sanctum secara otomatis menggunakan primary key 'id_users' karena sudah didefinisikan di Model Users
            $token = $user->createToken('authToken')->plainTextToken;

            // Mengembalikan data user secara eksplisit
            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil',
                'data' => [
                    // PENTING: Menggunakan Primary Key yang benar: id_users
                    'id' => $user->id_users,
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_handphone' => $user->no_handphone,
                    'alamat' => $user->alamat,

                    'token' => $token, // Kirim token
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
            // Gunakan APP_DEBUG untuk menampilkan pesan error detail hanya saat debugging
            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    // === METHOD LOGIN USER - DITAMBAH TOKEN GENERATION ===
    public function login(Request $request): JsonResponse
    {
        try {
            // Validasi input
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            // Cari user: Menggunakan Model Eloquent
            $user = Users::where('email', $request->email)->first();

            // Cek user ditemukan DAN password cocok
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password salah.'
                ], 401);
            }

            // Hapus token lama untuk keamanan
            $user->tokens()->delete();

            // ** 🔑 GENERATE API TOKEN BARU **
            $token = $user->createToken('authToken')->plainTextToken;

            // Login berhasil
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil!',
                'data' => [
                    // Struktur data dirapikan, tidak ada duplikasi 'users'
                    'id' => $user->id_users,
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_handphone' => $user->no_handphone,
                    'alamat' => $user->alamat,
                    'token' => $token, // Kirim token
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
                // Gunakan APP_DEBUG untuk menampilkan pesan error detail
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    // === METHOD LOGOUT USER ===
    public function logout(Request $request): JsonResponse
    {
        try {
            // Mencabut token yang sedang digunakan (Current Token)
            // Pastikan user terautentikasi sebelum memanggil user()
            if ($request->user()) {
                $request->user()->currentAccessToken()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout gagal',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
}