<?php

use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

// ==================== TEST API ====================
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Laundry API - READY',
        'database' => DB::connection()->getDatabaseName(),
        'time' => now()->format('Y-m-d H:i:s')
    ]);
});

// ==================== FIX USERS TABLE ====================
Route::get('/fix-users-table', function () {
    try {
        // Check if table exists and get current structure
        if (Schema::hasTable('users')) {
            $columns = DB::select("DESCRIBE users");
            $columnNames = array_column($columns, 'Field');

            // Check if id_users exists (old structure)
            if (in_array('id_users', $columnNames)) {
                // Backup existing data
                $existingUsers = DB::table('users')->get();

                // PENTING: Disable foreign key checks untuk drop table
                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                // Drop and recreate table with correct structure
                DB::statement('DROP TABLE IF EXISTS `users`');

                // Re-enable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } else {
                // Jika sudah pakai 'id', tidak perlu fix
                return response()->json([
                    'success' => true,
                    'message' => 'Tabel users sudah menggunakan struktur yang benar!',
                    'structure' => DB::select('DESCRIBE users'),
                    'total_users' => DB::table('users')->count(),
                    'note' => 'Primary key sudah menggunakan "id"'
                ]);
            }
        }

        // Create users table with correct structure (using 'id' not 'id_users')
        if (!Schema::hasTable('users')) {
            DB::statement("
                CREATE TABLE `users` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nama` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(100) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `no_handphone` VARCHAR(15) NOT NULL,
                    `alamat` TEXT NOT NULL,
                    `created_at` TIMESTAMP NULL DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Restore data if exists
            if (isset($existingUsers) && $existingUsers->count() > 0) {
                foreach ($existingUsers as $user) {
                    DB::table('users')->insert([
                        'nama' => $user->nama,
                        'email' => $user->email,
                        'password' => $user->password,
                        'no_handphone' => $user->no_handphone,
                        'alamat' => $user->alamat,
                        'created_at' => $user->created_at ?? now(),
                        'updated_at' => $user->updated_at ?? now()
                    ]);
                }
            } else {
                // Insert sample data jika tidak ada data sebelumnya
                DB::table('users')->insert([
                    [
                        'nama' => 'Budi Santoso',
                        'email' => 'budi@gmail.com',
                        'password' => Hash::make('12345'),
                        'no_handphone' => '08123456789',
                        'alamat' => 'Jl. Melati No. 5',
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    [
                        'nama' => 'Ani Setiawan',
                        'email' => 'ani@gmail.com',
                        'password' => Hash::make('12345'),
                        'no_handphone' => '08234567890',
                        'alamat' => 'Jl. Diponegoro No. 10',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tabel users berhasil diperbaiki dengan struktur yang benar!',
            'structure' => DB::select('DESCRIBE users'),
            'total_users' => DB::table('users')->count(),
            'restored_users' => isset($existingUsers) ? $existingUsers->count() : 0,
            'note' => 'Primary key sekarang menggunakan "id" bukan "id_users"'
        ]);

    } catch (\Exception $e) {
        // Pastikan foreign key checks di-enable kembali jika terjadi error
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbaiki tabel users',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== CHECK DATABASE STATUS ====================
Route::get('/status', function () {
    $tables = ['users', 'admin', 'orders', 'order_item', 'payments', 'laundry_services', 'delivery_zones', 'drivers', 'reviews', 'notifications'];
    $status = [];

    foreach ($tables as $table) {
        try {
            if (Schema::hasTable($table)) {
                $columns = DB::select("DESCRIBE $table");
                $status[$table] = [
                    'exists' => true,
                    'count' => DB::table($table)->count(),
                    'columns' => array_column($columns, 'Field'),
                    'primary_key' => collect($columns)->firstWhere('Key', 'PRI')->Field ?? 'unknown'
                ];
            } else {
                $status[$table] = ['exists' => false];
            }
        } catch (\Exception $e) {
            $status[$table] = ['exists' => false, 'error' => $e->getMessage()];
        }
    }

    return response()->json([
        'success' => true,
        'database' => DB::connection()->getDatabaseName(),
        'status' => $status
    ]);
});

// ==================== AUTO CREATE MISSING TABLES ====================
Route::get('/auto-create-tables', function () {
    try {
        $results = [];

        // Create users table if not exists
        if (!Schema::hasTable('users')) {
            DB::statement("
                CREATE TABLE `users` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nama` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(100) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `no_handphone` VARCHAR(15) NOT NULL,
                    `alamat` TEXT NOT NULL,
                    `created_at` TIMESTAMP NULL DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('users')->insert([
                [
                    'nama' => 'Budi Santoso',
                    'email' => 'budi@gmail.com',
                    'password' => Hash::make('12345'),
                    'no_handphone' => '08123456789',
                    'alamat' => 'Jl. Melati No. 5',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nama' => 'Ani Setiawan',
                    'email' => 'ani@gmail.com',
                    'password' => Hash::make('12345'),
                    'no_handphone' => '08234567890',
                    'alamat' => 'Jl. Diponegoro No. 10',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            $results['users'] = 'Table created with sample data';
        } else {
            $results['users'] = 'Table already exists';
        }

        // Create admin table if not exists
        if (!Schema::hasTable('admin')) {
            DB::statement("
                CREATE TABLE `admin` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nama` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(100) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `no_handphone` VARCHAR(15) NULL,
                    `alamat` TEXT NULL,
                    `role` ENUM('admin','staff') NOT NULL DEFAULT 'staff',
                    `created_at` TIMESTAMP NULL DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `admin_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('admin')->insert([
                [
                    'nama' => 'Admin Utama',
                    'email' => 'admin@gmail.com',
                    'password' => Hash::make('admin123'),
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nama' => 'Staff Laundry',
                    'email' => 'staff@gmail.com',
                    'password' => Hash::make('staff123'),
                    'role' => 'staff',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            $results['admin'] = 'Table created with sample data';
        } else {
            $results['admin'] = 'Table already exists';
        }

        return response()->json([
            'success' => true,
            'message' => 'Table creation process completed',
            'results' => $results
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create tables',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== USER AUTHENTICATION ====================

// USER REGISTER
Route::post('/register', function (Request $request) {
    try {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3',
            'no_handphone' => 'required|string|max:15',
            'alamat' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = DB::table('users')->insertGetId([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'no_handphone' => $request->no_handphone,
            'alamat' => $request->alamat,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $user = DB::table('users')->where('id', $userId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
                'no_handphone' => $user->no_handphone,
                'alamat' => $user->alamat
            ]
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Registrasi gagal',
            'error' => $e->getMessage()
        ], 500);
    }
});

// USER LOGIN
Route::post('/login', function (Request $request) {
    try {
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

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak terdaftar'
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_handphone' => $user->no_handphone,
                    'alamat' => $user->alamat
                ]
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Login gagal',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== ADMIN AUTHENTICATION ====================

// ADMIN REGISTER
Route::post('/admin/register', function (Request $request) {
    try {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
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

        $adminData = [
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'created_at' => now(),
            'updated_at' => now()
        ];

        if ($request->filled('no_handphone')) {
            $adminData['no_handphone'] = $request->no_handphone;
        }

        if ($request->filled('alamat')) {
            $adminData['alamat'] = $request->alamat;
        }

        $adminId = DB::table('admin')->insertGetId($adminData);
        $admin = DB::table('admin')->where('id', $adminId)->first();

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

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Pendaftaran admin gagal',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ADMIN LOGIN
Route::post('/admin/login', function (Request $request) {
    try {
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

        if (!Schema::hasTable('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel admin tidak ditemukan. Jalankan /api/auto-create-tables'
            ], 503);
        }

        $admin = DB::table('admin')->where('email', $request->email)->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Email admin tidak terdaftar'
            ], 401);
        }

        if (!Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login admin berhasil!',
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'nama' => $admin->nama,
                    'email' => $admin->email,
                    'role' => $admin->role
                ]
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Login admin gagal',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET ALL ADMINS
Route::get('/admins', function () {
    try {
        if (!Schema::hasTable('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel admin tidak tersedia'
            ], 503);
        }

        $admins = DB::table('admin')
            ->select('id', 'nama', 'email', 'role', 'no_handphone', 'alamat', 'created_at', 'updated_at')
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
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET SINGLE ADMIN
Route::get('/admins/{id}', function ($id) {
    try {
        $admin = DB::table('admin')->where('id', $id)->first();

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
            'error' => $e->getMessage()
        ], 500);
    }
});

// UPDATE ADMIN
Route::put('/admins/{id}', function (Request $request, $id) {
    try {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:admin,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'no_handphone' => 'nullable|string|max:15',
            'alamat' => 'nullable|string',
            'role' => 'sometimes|in:admin,staff'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = ['updated_at' => now()];

        if ($request->filled('nama'))
            $updateData['nama'] = $request->nama;
        if ($request->filled('email'))
            $updateData['email'] = $request->email;
        if ($request->filled('password'))
            $updateData['password'] = Hash::make($request->password);
        if ($request->filled('no_handphone'))
            $updateData['no_handphone'] = $request->no_handphone;
        if ($request->filled('alamat'))
            $updateData['alamat'] = $request->alamat;
        if ($request->filled('role'))
            $updateData['role'] = $request->role;

        DB::table('admin')->where('id', $id)->update($updateData);
        $admin = DB::table('admin')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Admin berhasil diupdate',
            'data' => $admin
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal update admin',
            'error' => $e->getMessage()
        ], 500);
    }
});

// DELETE ADMIN
Route::delete('/admins/{id}', function ($id) {
    try {
        $admin = DB::table('admin')->where('id', $id)->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin tidak ditemukan'
            ], 404);
        }

        DB::table('admin')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin berhasil dihapus'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus admin',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== MASTER DATA ====================

// GET ALL USERS
Route::get('/users', function () {
    try {
        $users = DB::table('users')->get(['id', 'nama', 'email', 'no_handphone', 'alamat', 'created_at']);
        return response()->json([
            'success' => true,
            'count' => $users->count(),
            'data' => $users
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil users',
            'error' => $e->getMessage()
        ], 500);
    }
});

// DELETE USER
Route::delete('/users/{id}', function ($id) {
    try {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        DB::table('users')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus user',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== UTILITY ENDPOINTS ====================

// RESET USERS
Route::get('/reset-users', function () {
    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'success' => true,
            'message' => 'Semua users berhasil dihapus'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal reset users',
            'error' => $e->getMessage()
        ], 500);
    }
});

// RESET ADMINS
Route::get('/reset-admins', function () {
    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('admin')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'success' => true,
            'message' => 'Semua admin berhasil dihapus'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal reset admin',
            'error' => $e->getMessage()
        ], 500);
    }
});
// ==================== GUEST ORDER API ====================

// CREATE NEW ORDER (GUEST/TANPA LOGIN) - DIPERBAIKI
Route::post('/orders', function (Request $request) {
    try {
        // Menerima kedua format: order_details atau order_detail
        $orderDetailsData = $request->order_details ?? $request->order_detail;

        if (!$orderDetailsData) {
            return response()->json([
                'success' => false,
                'message' => 'Field order_details atau order_detail diperlukan'
            ], 422);
        }

        // Validasi input untuk guest order
        $validator = Validator::make([
            'customer' => $request->customer,
            'order_details' => $orderDetailsData
        ], [
            'customer' => 'required|array',
            'customer.nama' => 'required|string|max:100',
            'customer.email' => 'required|email',
            'customer.no_handphone' => 'required|string|max:15',
            'customer.alamat' => 'required|string',

            'order_details' => 'required|array',
            'order_details.id_zone' => 'required|integer|exists:delivery_zones,id_zone',
            'order_details.kategori_laundry' => 'required|array|min:1',
            'order_details.kategori_laundry.*' => 'string|in:pakaian,sepatu,tas,karpet,kering,setrika',
            'order_details.berat' => 'required|numeric|min:0.1|max:50',
            'order_details.tanggal_jemput' => 'required|date|after:now',
            'order_details.catatan' => 'nullable|string|max:500',
            'order_details.payment_method' => 'required|in:cash,transfer,ewallet',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mulai transaction
        DB::beginTransaction();

        // 1. Cek atau buat user berdasarkan email
        $customer = $request->customer;
        $user = DB::table('users')->where('email', $customer['email'])->first();

        if (!$user) {
            // Buat user baru jika belum ada
            $userId = DB::table('users')->insertGetId([
                'nama' => $customer['nama'],
                'email' => $customer['email'],
                'password' => Hash::make('default123'),
                'no_handphone' => $customer['no_handphone'],
                'alamat' => $customer['alamat'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $user = DB::table('users')->where('id', $userId)->first();
        } else {
            $userId = $user->id;

            // Update data user jika ada perubahan
            DB::table('users')->where('id', $userId)->update([
                'nama' => $customer['nama'],
                'no_handphone' => $customer['no_handphone'],
                'alamat' => $customer['alamat'],
                'updated_at' => now()
            ]);
        }

        $orderDetails = $orderDetailsData;

        // 2. Get delivery fee
        $zone = DB::table('delivery_zones')
            ->where('id_zone', $orderDetails['id_zone'])
            ->first();

        if (!$zone) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Zona pengiriman tidak valid'
            ], 400);
        }

        $deliveryFee = $zone->ongkir;

        // 3. Cari services berdasarkan kategori yang dipilih
        $services = DB::table('laundry_services')
            ->whereIn('kategori', $orderDetails['kategori_laundry'])
            ->get();

        if ($services->isEmpty()) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada layanan yang tersedia untuk kategori yang dipilih'
            ], 400);
        }

        $totalItems = 0;
        $orderItems = [];

        // 4. Hitung total harga berdasarkan services yang tersedia
        foreach ($services as $service) {
            $subtotal = $orderDetails['berat'] * $service->harga;
            $totalItems += $subtotal;

            $orderItems[] = [
                'id_service' => $service->id_service,
                'jumlah' => 0,
                'berat_kg' => $orderDetails['berat'],
                'subtotal' => $subtotal
            ];
        }

        // 5. Buat order
        $orderId = DB::table('orders')->insertGetId([
            'id_user' => $userId,
            'id_zone' => $orderDetails['id_zone'],
            'status' => 'menunggu',
            'kategori_laundry' => json_encode($orderDetails['kategori_laundry']),
            'berat_total' => $orderDetails['berat'],
            'tanggal_jemput' => $orderDetails['tanggal_jemput'],
            'catatan' => $orderDetails['catatan'] ?? null,
            'tanggal_pesan' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 6. Buat order items
        foreach ($orderItems as &$item) {
            $item['id_order'] = $orderId;
        }
        DB::table('order_item')->insert($orderItems);

        // 7. Buat payment
        $paymentId = DB::table('payments')->insertGetId([
            'id_order' => $orderId,
            'metode' => $orderDetails['payment_method'],
            'jumlah' => $totalItems + $deliveryFee,
            'status' => 'ditunda',
            'tanggal_bayar' => null,
            'created_at' => now()
        ]);

        DB::commit();

        // 8. Ambil data order yang baru dibuat untuk response
        $newOrder = DB::table('orders')
            ->where('id_order', $orderId)
            ->first();

        $orderItemsDetail = DB::table('order_item')
            ->join('laundry_services', 'order_item.id_service', '=', 'laundry_services.id_service')
            ->where('order_item.id_order', $orderId)
            ->select(
                'laundry_services.nama_service',
                'laundry_services.kategori',
                'laundry_services.harga',
                'order_item.berat_kg',
                'order_item.subtotal'
            )
            ->get();

        $zoneDetail = DB::table('delivery_zones')
            ->where('id_zone', $orderDetails['id_zone'])
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dibuat!',
            'data' => [
                'id_order' => $orderId,
                'no_order' => 'ORD' . str_pad($orderId, 6, '0', STR_PAD_LEFT),
                'customer' => [
                    'nama' => $customer['nama'],
                    'email' => $customer['email'],
                    'no_handphone' => $customer['no_handphone'],
                    'alamat' => $customer['alamat']
                ],
                'order_details' => [
                    'kategori_laundry' => $orderDetails['kategori_laundry'],
                    'berat' => (float) $orderDetails['berat'],
                    'tanggal_jemput' => $orderDetails['tanggal_jemput'],
                    'catatan' => $orderDetails['catatan'] ?? '',
                    'zona_pengiriman' => $zoneDetail->nama_zone
                ],
                'items' => $orderItemsDetail,
                'total_amount' => $totalItems + $deliveryFee,
                'breakdown' => [
                    'subtotal_items' => $totalItems,
                    'delivery_fee' => $deliveryFee,
                    'total' => $totalItems + $deliveryFee
                ],
                'status' => 'menunggu',
                'tanggal_pesan' => $newOrder->tanggal_pesan
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Gagal membuat order',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET LAUNDRY CATEGORIES WITH SERVICES
Route::get('/laundry-categories', function () {
    try {
        $categories = DB::table('laundry_services')
            ->select('kategori')
            ->distinct()
            ->get()
            ->pluck('kategori');

        $services = DB::table('laundry_services')
            ->where('kategori', '!=', 'kering')
            ->where('kategori', '!=', 'setrika')
            ->select('id_service', 'nama_service', 'kategori', 'harga', 'tipe_harga', 'estimasi_hari')
            ->get()
            ->groupBy('kategori');

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'services_by_category' => $services
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil kategori laundry',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET ORDER STATUS BY EMAIL AND ORDER ID
Route::get('/orders/status', function (Request $request) {
    try {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'order_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = DB::table('orders')
            ->join('users', 'orders.id_user', '=', 'users.id')
            ->where('users.email', $request->email)
            ->where('orders.id_order', $request->order_id)
            ->select('orders.*', 'users.nama as customer_name')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan'
            ], 404);
        }

        $order->items = DB::table('order_item')
            ->join('laundry_services', 'order_item.id_service', '=', 'laundry_services.id_service')
            ->where('order_item.id_order', $request->order_id)
            ->select(
                'order_item.id_item',
                'order_item.berat_kg',
                'order_item.subtotal',
                'laundry_services.nama_service',
                'laundry_services.kategori',
                'laundry_services.harga'
            )
            ->get();

        $order->payment = DB::table('payments')
            ->where('id_order', $request->order_id)
            ->first();

        $order->zone = DB::table('delivery_zones')
            ->where('id_zone', $order->id_zone)
            ->first();

        // Calculate total
        $itemsTotal = collect($order->items)->sum('subtotal');
        $deliveryFee = $order->zone ? $order->zone->ongkir : 0;
        $order->total_amount = $itemsTotal + $deliveryFee;
        $order->no_order = 'ORD' . str_pad($order->id_order, 6, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'message' => 'Status order berhasil diambil',
            'data' => $order
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil status order',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET SINGLE PAYMENT BY ID
Route::get('/payments/{id}', function ($id) {
    try {
        $payment = DB::table('payments')
            ->where('id_payment', $id)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        // Tambahkan order info
        $payment->order = DB::table('orders')
            ->where('id_order', $payment->id_order)
            ->first();

        if ($payment->order) {
            $payment->customer = DB::table('users')
                ->where('id', $payment->order->id_user)
                ->first(['id', 'nama', 'email']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment berhasil diambil',
            'data' => $payment
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil payment',
            'error' => $e->getMessage()
        ], 500);
    }
});

// POST CREATE PAYMENT (Manual)
Route::post('/payments', function (Request $request) {
    try {
        $request->validate([
            'id_order' => 'required|integer|exists:orders,id_order',
            'metode' => 'required|in:cash,transfer,ewallet',
            'jumlah' => 'required|numeric|min:0'
        ]);

        // Cek apakah payment sudah ada untuk order ini
        $existing = DB::table('payments')
            ->where('id_order', $request->id_order)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Payment sudah ada untuk order ini'
            ], 409);
        }

        // Insert payment
        $paymentId = DB::table('payments')->insertGetId([
            'id_order' => $request->id_order,
            'metode' => $request->metode,
            'jumlah' => $request->jumlah,
            'status' => 'ditunda',
            'tanggal_bayar' => null
        ]);

        $payment = DB::table('payments')->where('id_payment', $paymentId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Payment berhasil dibuat',
            'data' => $payment
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal membuat payment',
            'error' => $e->getMessage()
        ], 500);
    }
});

// PUT UPDATE PAYMENT
Route::put('/payments/{id}', function (Request $request, $id) {
    try {
        $request->validate([
            'metode' => 'nullable|in:cash,transfer,ewallet',
            'jumlah' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:ditunda,lunas,gagal'
        ]);

        $payment = DB::table('payments')->where('id_payment', $id)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        $updateData = [];

        if ($request->has('metode')) {
            $updateData['metode'] = $request->metode;
        }
        if ($request->has('jumlah')) {
            $updateData['jumlah'] = $request->jumlah;
        }
        if ($request->has('status')) {
            $updateData['status'] = $request->status;

            // Auto set tanggal_bayar jika status = lunas
            if ($request->status === 'lunas') {
                $updateData['tanggal_bayar'] = now();
            }
        }

        DB::table('payments')->where('id_payment', $id)->update($updateData);

        $updatedPayment = DB::table('payments')->where('id_payment', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Payment berhasil diupdate',
            'data' => $updatedPayment
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal update payment',
            'error' => $e->getMessage()
        ], 500);
    }
});

// PUT CONFIRM PAYMENT (Set to Lunas)
Route::put('/payments/{id}/confirm', function (Request $request, $id) {
    try {
        $payment = DB::table('payments')->where('id_payment', $id)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        DB::table('payments')
            ->where('id_payment', $id)
            ->update([
                'status' => 'lunas',
                'tanggal_bayar' => $request->tanggal_bayar ?? now()
            ]);

        // Update order status juga
        DB::table('orders')
            ->where('id_order', $payment->id_order)
            ->update(['status' => 'diproses']);

        $updatedPayment = DB::table('payments')->where('id_payment', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Payment berhasil dikonfirmasi',
            'data' => $updatedPayment
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal konfirmasi payment',
            'error' => $e->getMessage()
        ], 500);
    }
});

// DELETE PAYMENT
Route::delete('/payments/{id}', function ($id) {
    try {
        $payment = DB::table('payments')->where('id_payment', $id)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        DB::table('payments')->where('id_payment', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment berhasil dihapus'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus payment',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== MASTER DATA ====================

// GET ALL SERVICES
Route::get('/services', function () {
    try {
        $services = DB::table('laundry_services')->get();
        return response()->json([
            'success' => true,
            'count' => $services->count(),
            'data' => $services
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil services',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET ALL ZONES
Route::get('/zones', function () {
    try {
        $zones = DB::table('delivery_zones')->where('status', 'aktif')->get();
        return response()->json([
            'success' => true,
            'count' => $zones->count(),
            'data' => $zones
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil zones',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET ALL DRIVERS
Route::get('/drivers', function () {
    try {
        $drivers = DB::table('drivers')->where('status', 'aktif')->get();
        return response()->json([
            'success' => true,
            'count' => $drivers->count(),
            'data' => $drivers
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil drivers',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET ALL USERS
Route::get('/users', function () {
    try {
        $users = DB::table('users')->get(['id', 'nama', 'email', 'no_handphone', 'alamat', 'created_at']);
        return response()->json([
            'success' => true,
            'count' => $users->count(),
            'data' => $users
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil users',
            'error' => $e->getMessage()
        ], 500);
    }
});

// DELETE USER
Route::delete('/users/{id}', function ($id) {
    try {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        DB::table('users')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus user',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== ADMIN ROUTES (Controller Based) ====================
// NOTE: Route closure untuk GET /admins sudah dihapus karena menggunakan controller

// GET ALL REVIEWS
Route::get('/reviews', function () {
    try {
        if (!Schema::hasTable('reviews')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel reviews tidak tersedia',
                'note' => 'Jalankan: /api/auto-create-tables untuk membuat tabel otomatis'
            ], 503);
        }

        $reviews = DB::table('reviews')
            ->join('orders', 'reviews.id_order', '=', 'orders.id_order')
            ->join('users', 'orders.id_user', '=', 'users.id')
            ->select('reviews.*', 'users.nama as customer_name')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $reviews->count(),
            'data' => $reviews
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil reviews',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET ALL NOTIFICATIONS
Route::get('/notifications', function () {
    try {
        if (!Schema::hasTable('notifications')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel notifications tidak tersedia',
                'note' => 'Jalankan: /api/auto-create-tables untuk membuat tabel otomatis'
            ], 503);
        }

        $notifications = DB::table('notifications')
            ->join('users', 'notifications.id_user', '=', 'users.id')
            ->select('notifications.*', 'users.nama as user_name')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $notifications->count(),
            'data' => $notifications
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil notifications',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== UTILITY ENDPOINTS ====================

// CHECK STRUCTURE
Route::get('/check-structure', function () {
    $tables = ['users', 'orders', 'order_item', 'payments', 'laundry_services', 'delivery_zones', 'drivers', 'admin', 'reviews', 'notifications'];
    $results = [];

    foreach ($tables as $table) {
        try {
            if (Schema::hasTable($table)) {
                $columns = DB::select("DESCRIBE $table");
                $results[$table] = [
                    'exists' => true,
                    'columns' => array_column($columns, 'Field')
                ];
            } else {
                $results[$table] = ['exists' => false, 'columns' => []];
            }
        } catch (\Exception $e) {
            $results[$table] = ['exists' => false, 'error' => $e->getMessage()];
        }
    }

    return response()->json([
        'success' => true,
        'data' => $results
    ]);
});

// QUICK SETUP
Route::get('/setup', function () {
    try {
        $user = DB::table('users')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan. Pastikan ada data user.'
            ], 404);
        }

        // Buat order sample
        $orderId = DB::table('orders')->insertGetId([
            'id_user' => $user->id,
            'id_driver' => 1,
            'id_zone' => 1,
            'status' => 'selesai',
            'tanggal_pesan' => now()->subDays(2),
            'tanggal_selesai' => now()->subDays(1)
        ]);

        // Buat order item
        DB::table('order_item')->insert([
            'id_order' => $orderId,
            'id_service' => 1,
            'jumlah' => 0,
            'berat_kg' => 2.5,
            'subtotal' => 17500.00
        ]);

        // Buat payment
        DB::table('payments')->insert([
            'id_order' => $orderId,
            'metode' => 'cash',
            'jumlah' => 22500.00,
            'status' => 'lunas',
            'tanggal_bayar' => now()->subDays(1)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sample order created successfully!',
            'order_id' => $orderId,
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Setup failed: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ], 500);
    }
});

// QUICK GENERATE
Route::get('/quick-generate', function () {
    try {
        $user = DB::table('users')->first();

        if (!$user) {
            // Buat user sample jika tidak ada
            $userId = DB::table('users')->insertGetId([
                'nama' => 'putri',
                'email' => 'putri@gmail.com',
                'password' => Hash::make('12345'),
                'no_handphone' => '08123456789',
                'alamat' => 'Jl. Test No. 1',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $userId = $user->id;
        }

        // Buat sample order
        $orderId = DB::table('orders')->insertGetId([
            'id_user' => $userId,
            'id_zone' => 1,
            'status' => 'selesai',
            'tanggal_pesan' => now()->subDays(2),
            'tanggal_selesai' => now()->subDays(1)
        ]);

        DB::table('order_item')->insert([
            'id_order' => $orderId,
            'id_service' => 1,
            'jumlah' => 0,
            'berat_kg' => 2.5,
            'subtotal' => 17500.00
        ]);

        DB::table('payments')->insert([
            'id_order' => $orderId,
            'metode' => 'cash',
            'jumlah' => 22500.00,
            'status' => 'lunas',
            'tanggal_bayar' => now()->subDays(1)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quick sample order generated!',
            'order_id' => $orderId
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to generate sample',
            'error' => $e->getMessage()
        ], 500);
    }
});

// RESET DATA
Route::get('/reset', function () {
    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('order_item')->truncate();
        DB::table('payments')->truncate();
        DB::table('orders')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'success' => true,
            'message' => 'Data orders berhasil direset.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Reset gagal',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== DEBUGGING (DELETE AFTER FIX) ====================
Route::get('/debug/admin', function () {
    try {
        // Ambil data admin yang seharusnya terdaftar (admin@gmail.com, sesuai Seeder Anda)
        $admin = DB::table('admin')->where('email', 'admin@gmail.com')->first();

        if ($admin) {
            return response()->json([
                'success' => true,
                'message' => 'Admin data found for inspection',
                'admin_data' => [
                    'email' => $admin->email,
                    'stored_password_value' => $admin->password,
                    'is_hashed_format' => str_starts_with($admin->password ?? '', '$2y$') || str_starts_with($admin->password ?? '', '$2a$')
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Admin not found in DB. Seeder did not run correctly.'
            ], 404);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database error during debug',
            'error' => $e->getMessage()
        ], 500);
    }
});