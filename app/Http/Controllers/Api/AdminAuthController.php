<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin; // Import Model Admin yang baru
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // Tambahkan untuk otentikasi

class AdminAuthController extends Controller
{
    /**
     * Register a new admin (CREATE - STORE)
     */
    public function register(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:100',
                // Pastikan unique di tabel 'admin'
                'email' => 'required|email|unique:admin,email', 
                'password' => 'required|string|min:6',
                'no_handphone' => 'nullable|string|max:15',
                'alamat' => 'nullable|string',
                'role' => 'required|in:admin,staff'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Buat admin baru menggunakan Model Eloquent
            $admin = Admin::create([
                'nama' => $request->nama,
                'email' => $request->email,
                // Password di-hash otomatis karena ada casting di Model Admin.php
                'password' => $request->password, 
                'role' => $request->role,
                'no_handphone' => $request->no_handphone,
                'alamat' => $request->alamat,
            ]);

            // Opsional: Generate token saat register (seperti login)
            $token = $admin->createToken('authToken')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Admin berhasil didaftarkan',
                'data' => [
                    'id' => $admin->id,
                    'nama' => $admin->nama,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran admin gagal: ' . $e->getMessage(),
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Login admin (Otentikasi) - DITAMBAH TOKEN GENERATION
     */
    public function login(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cari admin berdasarkan email menggunakan Model
            $admin = Admin::where('email', $request->email)->first(); 

            if (!$admin || !Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kredensial tidak valid (Email atau Password salah)'
                ], 401);
            }
            
            // Hapus token lama untuk keamanan
            $admin->tokens()->delete(); 

            // ** 🔑 GENERATE API TOKEN BARU (PENTING UNTUK FLUTTER) **
            $token = $admin->createToken('authToken')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login admin berhasil!',
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'nama' => $admin->nama,
                        'email' => $admin->email,
                        'role' => $admin->role
                    ],
                    'token' => $token, // Kirim token ke Flutter
                    'token_type' => 'Bearer'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login admin gagal',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
    
    /**
     * Logout admin (Mencabut token)
     */
    public function logout(Request $request)
    {
        // Mencabut token yang sedang digunakan (Current Token)
        $request->user()->currentAccessToken()->delete(); 

        return response()->json([
            'success' => true,
            'message' => 'Logout admin berhasil'
        ], 200);
    }
    
    /**
     * Get all admins (READ - INDEX)
     */
    public function index()
    {
        try {
            $admins = Admin::select('id', 'nama', 'email', 'role', 'created_at', 'updated_at')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'count' => $admins->count(),
                'data' => $admins
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data admin',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Get single admin by ID (READ - SHOW)
     */
    public function show($id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $admin
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data admin',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Update admin (UPDATE)
     */
    public function update(Request $request, $id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak ditemukan'
                ], 404);
            }
            
            $validator = Validator::make($request->all(), [
                'nama' => 'sometimes|string|max:100',
                // Pengecualian ID saat ini
                'email' => 'sometimes|email|unique:admin,email,' . $id . ',id', 
                'password' => 'nullable|string|min:6', // Diubah menjadi nullable/sometimes
                'role' => 'sometimes|in:admin,staff'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Gunakan update dengan Model Eloquent
            $admin->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Admin berhasil diupdate',
                'data' => $admin
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update admin',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi Skesalahan server'
            ], 500);
        }
    }

    /**
     * Delete admin (DELETE - DESTROY)
     */
    public function destroy($id)
    {
        try {
            $admin = Admin::find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak ditemukan'
                ], 404);
            }

            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Admin berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus admin',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
}