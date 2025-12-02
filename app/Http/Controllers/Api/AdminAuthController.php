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
    \Log::info('Admin Register Request:', $request->all());
    
    try {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:admin,email',
            'password' => 'required|string|min:6',
            'no_handphone' => 'nullable|string|max:15',
            'alamat' => 'nullable|string',
            'role' => 'required|in:admin,staff'
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        \Log::info('Creating admin with data:', [
            'nama' => $request->nama,
            'email' => $request->email,
            'has_password' => !empty($request->password)
        ]);

        // Buat admin baru
        $adminData = [
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Tambahkan kolom opsional jika ada
        if ($request->has('no_handphone') && !empty($request->no_handphone)) {
            $adminData['no_handphone'] = $request->no_handphone;
        }
        
        if ($request->has('alamat') && !empty($request->alamat)) {
            $adminData['alamat'] = $request->alamat;
        }

        \Log::info('Admin data to insert:', $adminData);

        // Coba insert data
        $adminId = DB::table('admin')->insertGetId($adminData);
        \Log::info('Admin created with ID:', ['id' => $adminId]);

        // Ambil data admin yang baru dibuat
        $admin = DB::table('admin')->where('id', $adminId)->first();
        
        if (!$admin) {
            \Log::error('Admin not found after creation:', ['id' => $adminId]);
            throw new \Exception('Admin tidak ditemukan setelah dibuat');
        }

        return response()->json([
            'success' => true,
            'message' => 'Admin berhasil didaftarkan',
            'data' => [
                'id' => $admin->id,
                'nama' => $admin->nama,
                'email' => $admin->email,
                'no_handphone' => $admin->no_handphone ?? null,
                'alamat' => $admin->alamat ?? null,
                'role' => $admin->role,
                'created_at' => $admin->created_at
            ]
        ], 201);

    } catch (\Illuminate\Database\QueryException $e) {
        \Log::error('Database error in admin register:', [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'sql' => $e->getSql(),
            'bindings' => $e->getBindings()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage(),
            'error_code' => $e->getCode()
        ], 500);
        
    } catch (\Exception $e) {
        \Log::error('General error in admin register:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Pendaftaran admin gagal: ' . $e->getMessage(),
            'error' => $e->getMessage(),
            'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
        ], 500);
    }
}
    /**
     * Login admin (Otentikasi) - DITAMBAH TOKEN GENERATION
     */
    public function login(Request $request)
    {
        try {
            // Cek jika tabel admin ada
            if (!Schema::hasTable('admin')) { // PERUBAHAN: 'admin' bukan 'admins'
                return response()->json([
                    'success' => false,
                    'message' => 'Tabel admin tidak ditemukan',
                    'note' => 'Pastikan database sudah diimport'
                ], 503);
            }

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

            // Cari admin berdasarkan email
            $admin = DB::table('admin')->where('email', $request->email)->first(); // PERUBAHAN: 'admin'

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
            if (!Schema::hasTable('admin')) { // PERUBAHAN: 'admin'
                return response()->json([
                    'success' => false,
                    'message' => 'Tabel admin tidak tersedia'
                ], 503);
            }

            $admins = DB::table('admin') // PERUBAHAN: 'admin'
                ->select('id', 'nama', 'email', 'role', 'created_at', 'updated_at')
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

    // -------------------------------------------------------------------------------- //

    /**
     * Get single admin by ID (READ - SHOW)
     */
    public function show($id)
    {
        try {
            $admin = DB::table('admin')->where('id', $id)->first(); // PERUBAHAN: 'admin'

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

    // -------------------------------------------------------------------------------- //

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
                'email' => 'sometimes|email|unique:admin,email,' . $id, // PERUBAHAN: 'admin'
                'password' => 'sometimes|string|min:6',
                'role' => 'sometimes|in:admin,staff'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];
            if ($request->has('nama')) {
                $updateData['nama'] = $request->nama;
            }
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }
            if ($request->has('role')) {
                $updateData['role'] = $request->role;
            }
            $updateData['updated_at'] = now();

            DB::table('admin')->where('id', $id)->update($updateData); // PERUBAHAN: 'admin'

            $admin = DB::table('admin')->where('id', $id)->first(); // PERUBAHAN: 'admin'

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

    // -------------------------------------------------------------------------------- //

    /**
     * Delete admin (DELETE - DESTROY)
     */
    public function destroy($id)
    {
        try {
            $admin = DB::table('admin')->where('id', $id)->first(); // PERUBAHAN: 'admin'

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin tidak ditemukan'
                ], 404);
            }

            DB::table('admin')->where('id', $id)->delete(); // PERUBAHAN: 'admin'

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