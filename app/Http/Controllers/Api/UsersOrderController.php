<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use App\Models\Users;
use App\Models\OrderItem;
use App\Models\LaundryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Tambahkan Validator
use Illuminate\Support\Facades\Hash; // Tambahkan Hash untuk password
use Illuminate\Http\JsonResponse;
use Carbon\Carbon; // Untuk manajemen tanggal

class UsersOrderController extends Controller
{
    /**
     * Mendapatkan detail profil User berdasarkan ID.
     * Route: GET /user/profile/{id}
     *
     * @param int $id ID User
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Users::find($id); // Menggunakan Model Users

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil pengguna tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail profil berhasil dimuat',
                'data' => [
                    'id' => $user->id_users,
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_handphone' => $user->no_handphone,
                    'alamat' => $user->alamat,
                    'created_at' => $user->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat profil',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Memperbarui profil User.
     * Route: PUT /user/profile/{id}
     *
     * @param Request $request
     * @param int $id ID User
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = Users::find($id); // Menggunakan Model Users

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil pengguna tidak ditemukan'
                ], 404);
            }

            // Validasi input, pastikan email diabaikan jika milik user yang sama
            $request->validate([
                'nama' => 'sometimes|required|string|max:100',
                'email' => 'sometimes|required|email|unique:users,email,' . $id . ',id_users',
                'no_handphone' => 'sometimes|required|string|max:15',
                'alamat' => 'sometimes|required|string'
            ]);

            $user->update($request->only([
                'nama',
                'email',
                'no_handphone',
                'alamat'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui',
                'data' => [
                    'id' => $user->id_users,
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_handphone' => $user->no_handphone,
                    'alamat' => $user->alamat,
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi pembaruan profil gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profil',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Memperbarui password User.
     * Route: PUT /user/password/{id}
     *
     * @param Request $request
     * @param int $id ID User
     * @return JsonResponse
     */
    public function updatePassword(Request $request, int $id): JsonResponse
    {
        try {
            $user = Users::find($id); // Menggunakan Model Users

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil pengguna tidak ditemukan'
                ], 404);
            }

            // Validasi input
            $request->validate([
                'current_password' => 'required|string',
                // Pastikan password baru minimal 6 karakter dan dikonfirmasi
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            // Cek apakah current_password sesuai dengan password di database
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password lama tidak valid'
                ], 403);
            }

            // Update password baru. Karena $casts di Model Users sudah 'hashed', Hash::make tidak wajib
            $user->password = $request->new_password;
            $user->save();

            // Opsional: Cabut semua token lama setelah ganti password
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diperbarui. Silakan login kembali.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi pembaruan password gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui password',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
}