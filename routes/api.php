<?php

<<<<<<< HEAD
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthApiController;
use App\Http\Controllers\OrderApiController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthApiController::class, 'login']);
Route::post('/register', [AuthApiController::class, 'register']);

/*
|--------------------------------------------------------------------------
| USERS (JANGAN DIHAPUS ✅)
|--------------------------------------------------------------------------
*/
Route::get('/users', [AuthApiController::class, 'users']);

/*
|--------------------------------------------------------------------------
| ORDERS - UMUM / USER
|--------------------------------------------------------------------------
*/
Route::post('/orders', [OrderApiController::class, 'store']);
Route::get('/orders', [OrderApiController::class, 'index']);
Route::get('/orders/{id}', [OrderApiController::class, 'show']);
Route::get('/my-orders/{userId}', [OrderApiController::class, 'myOrders']);

/*
|--------------------------------------------------------------------------
| ADMIN / DRIVER
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // update SATU order
    Route::put(
        '/admin/orders/{id}/status',
        [OrderApiController::class, 'updateStatus']
    );

    // 🔥 TAMBAHAN: update BANYAK order
    Route::put(
        '/admin/orders/bulk-status',
        [OrderApiController::class, 'bulkUpdateStatus']
    );

    // 🔥 TAMBAHAN: update SEMUA order
    Route::put(
        '/admin/orders/status-all',
        [OrderApiController::class, 'updateAllStatus']
    );

    // get current user (cukup SATU)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
=======
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DeliveryZoneController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\UsersOrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ==================== TEST API ====================
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Laundry API - READY',
        'database' => DB::connection()->getDatabaseName(),
        'time' => now()->format('Y-m-d H:i:s')
    ]);
});

// ==================== CHECK DATABASE STATUS ====================
Route::get('/status', function () {
    $tables = ['users', 'orders', 'order_item', 'payments', 'laundry_services', 'delivery_zones', 'drivers', 'admin', 'reviews', 'notifications'];
    $status = [];

    foreach ($tables as $table) {
        try {
            if (Schema::hasTable($table)) {
                $columns = DB::select("DESCRIBE $table");
                $status[$table] = [
                    'exists' => true,
                    'count' => DB::table($table)->count(),
                    'columns' => array_column($columns, 'Field'),
                    'sample_data' => DB::table($table)->first()
                ];
            } else {
                $status[$table] = ['exists' => false, 'error' => 'Table not found'];
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

        // Create users table if not exists (PLURAL!)
        if (!Schema::hasTable('users')) {
            DB::statement("
                CREATE TABLE `users` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nama` varchar(100) NOT NULL,
                    `email` varchar(100) NOT NULL,
                    `password` varchar(225) NOT NULL,
                    `no_handphone` varchar(15) NOT NULL,
                    `alamat` text NOT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Insert sample data
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
                ],
                [
                    'nama' => 'Rizky Hidayat',
                    'email' => 'rizky@gmail.com',
                    'password' => Hash::make('12345'),
                    'no_handphone' => '08334567891',
                    'alamat' => 'Jl. Ahmad Yani No. 2',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            $results['users'] = 'Table created with sample data';
        } else {
            $results['users'] = 'Table already exists';
        }

        // Create admin table if not exists (SINGULAR!)
        if (!Schema::hasTable('admin')) {
            DB::statement("
                CREATE TABLE `admin` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nama` varchar(100) NOT NULL,
                    `email` varchar(100) NOT NULL,
                    `password` varchar(225) NOT NULL,
                    `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `admin_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Insert sample data
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
                    'nama' => 'Staff',
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

        // Create drivers table if not exists
        if (!Schema::hasTable('drivers')) {
            DB::statement("
                CREATE TABLE `drivers` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nama` varchar(100) NOT NULL,
                    `email` varchar(100) NOT NULL,
                    `password` varchar(225) NOT NULL,
                    `no_handphone` varchar(15) NOT NULL,
                    `status` enum('available', 'on_delivery', 'off_duty') NOT NULL DEFAULT 'available',
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `drivers_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('drivers')->insert([
                [
                    'nama' => 'Joko Antar',
                    'email' => 'joko.driver@gmail.com',
                    'password' => Hash::make('driver123'),
                    'no_handphone' => '087711223344',
                    'status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            $results['drivers'] = 'Table created with sample data';
        } else {
            $results['drivers'] = 'Table already exists';
        }

        // Create delivery_zones table if not exists
        if (!Schema::hasTable('delivery_zones')) {
            DB::statement("
                CREATE TABLE `delivery_zones` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nama_zona` varchar(100) NOT NULL,
                    `biaya_kirim` DECIMAL(10, 2) NOT NULL,
                    `estimasi_waktu` varchar(50) NOT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('delivery_zones')->insert([
                ['nama_zona' => 'Area A (Pusat Kota)', 'biaya_kirim' => 10000.00, 'estimasi_waktu' => '1-2 jam', 'created_at' => now(), 'updated_at' => now()],
                ['nama_zona' => 'Area B (Pinggiran)', 'biaya_kirim' => 15000.00, 'estimasi_waktu' => '2-3 jam', 'created_at' => now(), 'updated_at' => now()],
            ]);

            $results['delivery_zones'] = 'Table created with sample data';
        } else {
            $results['delivery_zones'] = 'Table already exists';
        }

        // Create laundry_services table if not exists
        if (!Schema::hasTable('laundry_services')) {
            DB::statement("
                CREATE TABLE `laundry_services` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nama_layanan` varchar(100) NOT NULL,
                    `deskripsi` text NOT NULL,
                    `satuan` enum('kg', 'pcs', 'unit') NOT NULL,
                    `harga_per_satuan` DECIMAL(10, 2) NOT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('laundry_services')->insert([
                ['nama_layanan' => 'Cuci Kering Satuan', 'deskripsi' => 'Mencuci dan mengeringkan pakaian per unit/pcs.', 'satuan' => 'pcs', 'harga_per_satuan' => 5000.00, 'created_at' => now(), 'updated_at' => now()],
                ['nama_layanan' => 'Cuci Setrika Reguler', 'deskripsi' => 'Mencuci, mengeringkan, dan menyetrika per kilogram, 3 hari selesai.', 'satuan' => 'kg', 'harga_per_satuan' => 7000.00, 'created_at' => now(), 'updated_at' => now()],
                ['nama_layanan' => 'Cuci Setrika Express', 'deskripsi' => 'Mencuci, mengeringkan, dan menyetrika per kilogram, 1 hari selesai.', 'satuan' => 'kg', 'harga_per_satuan' => 12000.00, 'created_at' => now(), 'updated_at' => now()],
            ]);

            $results['laundry_services'] = 'Table created with sample data';
        } else {
            $results['laundry_services'] = 'Table already exists';
        }

        // Create orders table if not exists
        if (!Schema::hasTable('orders')) {
            DB::statement("
                CREATE TABLE `orders` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_user` BIGINT UNSIGNED NOT NULL,
                    `kode_order` varchar(20) NOT NULL UNIQUE,
                    `tanggal_masuk` date NOT NULL,
                    `tanggal_selesai_estimasi` date,
                    `id_driver` BIGINT UNSIGNED NULL,
                    `id_delivery_zone` BIGINT UNSIGNED NOT NULL,
                    `total_harga_layanan` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                    `biaya_kirim` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                    `total_akhir` DECIMAL(10, 2) NOT NULL,
                    `status_order` enum('menunggu_pickup','diproses','siap_kirim','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_pickup',
                    `metode_pembayaran` enum('cash','transfer','ewallet') NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `orders_id_user_foreign` (`id_user`),
                    KEY `orders_id_driver_foreign` (`id_driver`),
                    KEY `orders_id_delivery_zone_foreign` (`id_delivery_zone`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('orders')->insert([
                [
                    'id_user' => 1,
                    'kode_order' => 'LNDR-' . time() . '001',
                    'tanggal_masuk' => now()->toDateString(),
                    'tanggal_selesai_estimasi' => now()->addDays(3)->toDateString(),
                    'id_driver' => 1,
                    'id_delivery_zone' => 1,
                    'total_harga_layanan' => 14000.00,
                    'biaya_kirim' => 10000.00,
                    'total_akhir' => 24000.00,
                    'status_order' => 'diproses',
                    'metode_pembayaran' => 'cash',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            $results['orders'] = 'Table created with sample data';
        } else {
            $results['orders'] = 'Table already exists';
        }

        // Create order_item table if not exists
        if (!Schema::hasTable('order_item')) {
            DB::statement("
                CREATE TABLE `order_item` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_order` BIGINT UNSIGNED NOT NULL,
                    `id_service` BIGINT UNSIGNED NOT NULL,
                    `kuantitas` DECIMAL(8, 2) NOT NULL,
                    `subtotal` DECIMAL(10, 2) NOT NULL,
                    `catatan` text NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `order_item_id_order_foreign` (`id_order`),
                    KEY `order_item_id_service_foreign` (`id_service`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('order_item')->insert([
                [
                    'id_order' => 1,
                    'id_service' => 2, // Cuci Setrika Reguler (7000/kg)
                    'kuantitas' => 2.00,
                    'subtotal' => 14000.00,
                    'catatan' => 'Baju putih dipisah.',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            $results['order_item'] = 'Table created with sample data';
        } else {
            $results['order_item'] = 'Table already exists';
        }

        // Create payments table if not exists
        if (!Schema::hasTable('payments')) {
            DB::statement("
                CREATE TABLE `payments` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_order` BIGINT UNSIGNED NOT NULL,
                    `id_user` BIGINT UNSIGNED NOT NULL,
                    `kode_transaksi` varchar(50) NOT NULL UNIQUE,
                    `jumlah_bayar` DECIMAL(10, 2) NOT NULL,
                    `metode` enum('cash','transfer','ewallet') NOT NULL,
                    `status_pembayaran` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `payments_id_order_foreign` (`id_order`),
                    KEY `payments_id_user_foreign` (`id_user`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('payments')->insert([
                [
                    'id_order' => 1,
                    'id_user' => 1,
                    'kode_transaksi' => 'TX-' . time() . '001',
                    'jumlah_bayar' => 24000.00,
                    'metode' => 'cash',
                    'status_pembayaran' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            $results['payments'] = 'Table created with sample data';
        } else {
            $results['payments'] = 'Table already exists';
        }

        // Create reviews table if not exists
        if (!Schema::hasTable('reviews')) {
            DB::statement("
                CREATE TABLE `reviews` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_order` BIGINT UNSIGNED NOT NULL,
                    `rating` int(11) NOT NULL,
                    `komentar` text NOT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `reviews_id_order_foreign` (`id_order`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $results['reviews'] = 'Table created';
        } else {
            $results['reviews'] = 'Table already exists';
        }

        // Create notifications table if not exists
        if (!Schema::hasTable('notifications')) {
            DB::statement("
                CREATE TABLE `notifications` (
                    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_user` BIGINT UNSIGNED NOT NULL,
                    `pesan` text NOT NULL,
                    `status` enum('terkirim','dibaca') NOT NULL DEFAULT 'terkirim',
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `notifications_id_user_foreign` (`id_user`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            DB::table('notifications')->insert([
                [
                    'id_user' => 1,
                    'pesan' => 'Selamat datang di layanan Laundry kami!',
                    'status' => 'terkirim',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            $results['notifications'] = 'Table created';
        } else {
            $results['notifications'] = 'Table already exists';
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

// ==================== AUTHENTICATION (USER) ====================

// REGISTER - POST
// ==================== REGISTRASI USER BARU ====================
Route::post('/register', function (Request $request) {
    try {
        // 1. Validasi Input
        $request->validate([
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:3',
            'no_handphone' => 'required|string|max:15',
            'alamat' => 'required|string'
        ]);

        // 2. Insert Data
        $userId = DB::table('users')->insertGetId([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'no_handphone' => $request->no_handphone,
            'alamat' => $request->alamat,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 3. AMBIL DATA USER YANG BARU DIBUAT (DIPERBAIKI)
        $user = DB::table('users')->where('id', $userId)->first();

        // 4. Beri Respons Sukses
        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data' => $user
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
            'message' => 'Registrasi gagal',
            'error' => $e->getMessage()
        ], 500);
    }
});

// LOGIN - POST
Route::post('/login', function (Request $request) {
    try {
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        // Cari user berdasarkan email
        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak terdaftar'
            ], 401);
        }

        // Check password
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
                    'id' => $user->id, // DIPERBAIKI: id bukan id_users
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_handphone' => $user->no_handphone,
                    'alamat' => $user->alamat
                ]
            ]
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
            'message' => 'Login gagal',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== ADMIN AUTH API ====================

// Admin Register (Hanya untuk keperluan setup/admin utama)
Route::post('/admin/register', [AdminAuthController::class, 'register']);

// Admin Login
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Admin CRUD Routes (Harusnya dilindungi dengan middleware Auth/Role, tapi kita biarkan terbuka untuk contoh)
Route::get('/admins', [AdminAuthController::class, 'index']);
Route::get('/admins/{id}', [AdminAuthController::class, 'show']);
Route::put('/admins/{id}', [AdminAuthController::class, 'update']);
Route::delete('/admins/{id}', [AdminAuthController::class, 'destroy']);

// ==================== DRIVER AUTH API ====================

Route::post('/driver/register', [DriverController::class, 'register']);
Route::post('/driver/login', [DriverController::class, 'login']);

// ==================== ORDERS API ====================
// Rute untuk Customer membuat, melihat, dan membatalkan pesanan.

// Membuat pesanan baru (POST)
Route::post('/orders', [OrderController::class, 'store']);

// Mendapatkan semua pesanan user (GET)
Route::get('/orders/user/{id_user}', [OrderController::class, 'getUserOrders']);

// Mendapatkan detail pesanan (GET)
Route::get('/orders/{id}', [OrderController::class, 'show']);

// Memperbarui status pesanan (PUT - Khusus Admin/Staff)
Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

// Pembatalan pesanan (PUT)
Route::put('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);

// ==================== ADMIN/STAFF ORDERS API ====================

// Mendapatkan semua pesanan (GET - Khusus Admin/Staff)
Route::get('/admin/orders', [OrderController::class, 'index']);

// Menetapkan Driver ke Pesanan (PUT - Khusus Admin/Staff)
Route::put('/admin/orders/{id}/assign-driver', [OrderController::class, 'assignDriver']);

// Mencatat item laundry ke dalam pesanan (POST - Khusus Admin/Staff)
Route::post('/admin/orders/{id}/items', [OrderController::class, 'addOrderItems']);

Route::post('/register', [UsersOrderController::class, 'register']); // Mengaitkan POST api/register
Route::post('/login', [UsersOrderController::class, 'login']);      // Mengaitkan POST api/login

// Mendapatkan daftar SEMUA Order (GET - Khusus Admin/Staff)
Route::get('/orders/all', [OrderController::class, 'index'])->middleware('auth:sanctum'); // <<< TAMBAHKAN INI

// Membuat Order Baru (POST - Khusus User)
Route::post('/orders', [OrderController::class, 'store'])->middleware('auth:sanctum');

// Mendapatkan detail Order (GET)
Route::get('/orders/{id}', [OrderController::class, 'show']);

// Mendapatkan daftar Order untuk user tertentu (GET - Khusus User)
Route::get('/orders/user/{id_user}', [OrderController::class, 'indexByUser'])->middleware('auth:sanctum');

// Mengubah status Order (PUT/PATCH - Khusus Admin/Staff)
Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->middleware('auth:sanctum');


// ==================== LAUNDRY SERVICES API ====================

// Mendapatkan semua layanan (GET)
Route::get('/services', [ServiceController::class, 'index']);

// Mendapatkan detail layanan (GET)
Route::get('/services/{id}', [ServiceController::class, 'show']);

// Menambahkan layanan baru (POST - Khusus Admin/Staff)
Route::post('/admin/services', [ServiceController::class, 'store']);

// Memperbarui layanan (PUT - Khusus Admin/Staff)
Route::put('/admin/services/{id}', [ServiceController::class, 'update']);

// Menghapus layanan (DELETE - Khusus Admin/Staff)
Route::delete('/admin/services/{id}', [ServiceController::class, 'destroy']);

// ==================== PAYMENTS API ====================

// Membuat entri pembayaran baru (POST - Dipicu setelah order dibuat atau ketika user ingin membayar)
Route::post('/payments', [PaymentController::class, 'store']);

// Memperbarui status pembayaran (PUT - Khusus Admin/Webhooks)
Route::put('/payments/{id}/status', [PaymentController::class, 'updateStatus']);

// Mendapatkan detail pembayaran (GET)
Route::get('/payments/{id}', [PaymentController::class, 'show']);

// Mendapatkan semua transaksi pembayaran (GET - Khusus Admin/Staff)
Route::get('/admin/payments', [PaymentController::class, 'index']);

// ==================== DELIVERY ZONES API ====================

// Mendapatkan semua zona pengiriman (GET)
Route::get('/delivery-zones', [DeliveryZoneController::class, 'index']);

// Mendapatkan detail zona pengiriman (GET)
Route::get('/delivery-zones/{id}', [DeliveryZoneController::class, 'show']);

// Menambahkan zona pengiriman baru (POST - Khusus Admin/Staff)
Route::post('/admin/delivery-zones', [DeliveryZoneController::class, 'store']);

// Memperbarui zona pengiriman (PUT - Khusus Admin/Staff)
Route::put('/admin/delivery-zones/{id}', [DeliveryZoneController::class, 'update']);

// Menghapus zona pengiriman (DELETE - Khusus Admin/Staff)
Route::delete('/admin/delivery-zones/{id}', [DeliveryZoneController::class, 'destroy']);

// ==================== DRIVERS API ====================

// Mendapatkan semua driver (GET - Khusus Admin/Staff)
Route::get('/admin/drivers', [DriverController::class, 'index']);

// Mendapatkan detail driver (GET - Khusus Admin/Staff)
Route::get('/admin/drivers/{id}', [DriverController::class, 'show']);

// Memperbarui detail driver (PUT - Khusus Admin/Staff)
Route::put('/admin/drivers/{id}', [DriverController::class, 'update']);

// Menghapus driver (DELETE - Khusus Admin/Staff)
Route::delete('/admin/drivers/{id}', [DriverController::class, 'destroy']);

// Memperbarui status ketersediaan driver (PUT - Khusus Driver)
Route::put('/driver/status', [DriverController::class, 'updateAvailability']);

// Mendapatkan daftar tugas/orders driver (GET - Khusus Driver)
Route::get('/driver/orders/{id_driver}', [DriverController::class, 'getAssignedOrders']);

// ==================== USER PROFILE API ====================

// Mendapatkan detail profil user (GET)
Route::get('/user/profile/{id}', [UsersOrderController::class, 'show']);
//login users 
Route::post('/register', [AuthController::class, 'register']); // <<< PASTIKAN INI

Route::post('/login', [AuthController::class, 'login']);
// Memperbarui profil user (PUT)
Route::put('/user/profile/{id}', [UsersOrderController::class, 'update']);
// get users
Route::get('/users', [UsersOrderController::class, 'index']);
// Memperbarui password user (PUT)
Route::put('/user/password/{id}', [UsersOrderController::class, 'updatePassword']);

// ==================== REVIEWS & RATINGS API ====================

// Mengirimkan ulasan dan rating (POST)
Route::post('/reviews', [ReviewController::class, 'store']);

// Mendapatkan semua ulasan (GET - Filterable oleh Admin)
Route::get('/reviews', [ReviewController::class, 'index']);

// Mendapatkan ulasan berdasarkan ID Order (GET)
Route::get('/reviews/order/{id_order}', [ReviewController::class, 'showByOrder']);

// ==================== NOTIFICATIONS API ====================

// Mendapatkan semua notifikasi untuk user tertentu (GET)
Route::get('/notifications/user/{id_user}', [NotificationController::class, 'getUserNotifications']);

// Menandai notifikasi sebagai 'dibaca' (PUT)
Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
>>>>>>> f060a5238c9be001064f299807a527823f9b6ff1
