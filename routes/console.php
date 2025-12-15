<<<<<<< HEAD
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
=======

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// Test Basic API
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Laundry API Ready for Flutter',
        'time' => now()->format('Y-m-d H:i:s')
    ]);
});

// ==================== CEK STRUKTUR TABEL LOCAL ====================
Route::get('/check-structure', function () {
    $tables = ['user', 'orders', 'drivers', 'laundry_services', 'delivery_zones'];
    $results = [];
    
    foreach ($tables as $table) {
        try {
            $columns = DB::select("DESCRIBE $table");
            $results[$table] = array_column($columns, 'Field');
        } catch (\Exception $e) {
            $results[$table] = ['error' => $e->getMessage()];
        }
    }
    
    return response()->json([
        'success' => true,
        'data' => $results
    ]);
});

// ==================== AUTHENTICATION ====================
Route::post('/login', function (Request $request) {
    try {
        $email = $request->input('email');
        $password = $request->input('password');

        // Cari user - sesuaikan dengan kolom yang ada
        $user = DB::table('user')->where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 401);
        }

        // Check password 
        if ($user->password !== $password) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id_user ?? $user->id, // Coba kedua kemungkinan
                    'nama' => $user->nama ?? $user->name, // Coba kedua kemungkinan
                    'email' => $user->email,
                    'no_handphone' => $user->no_handphone ?? $user->phone ?? '',
                    'alamat' => $user->alamat ?? $user->address ?? ''
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

// ==================== ORDERS - VERSI AMAN ====================
// GET all orders - SIMPLE VERSION tanpa join dulu
Route::get('/orders', function () {
    try {
        $orders = DB::table('orders')->get();

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'count' => $orders->count(),
            'data' => $orders
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve orders',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET orders dengan join yang disesuaikan
Route::get('/orders-with-details', function () {
    try {
        // Cek kolom yang ada dulu
        $userColumns = DB::select("DESCRIBE user");
        $userColumnNames = array_column($userColumns, 'Field');
        
        $driverColumns = DB::select("DESCRIBE drivers"); 
        $driverColumnNames = array_column($driverColumns, 'Field');

        // Build query dinamis berdasarkan kolom yang ada
        $query = DB::table('orders');
        
        // Join user jika kolom nama ada
        if (in_array('nama', $userColumnNames)) {
            $query->leftJoin('user', 'orders.id_user', '=', 'user.id_user')
                ->addSelect('user.nama as customer_name');
        } elseif (in_array('name', $userColumnNames)) {
            $query->leftJoin('user', 'orders.id_user', '=', 'user.id')
                ->addSelect('user.name as customer_name');
        }
        
        // Join drivers jika kolom nama ada
        if (in_array('nama', $driverColumnNames)) {
            $query->leftJoin('drivers', 'orders.id_driver', '=', 'drivers.id_driver')
                ->addSelect('drivers.nama as driver_name');
        } elseif (in_array('name', $driverColumnNames)) {
            $query->leftJoin('drivers', 'orders.id_driver', '=', 'drivers.id')
                ->addSelect('drivers.name as driver_name');
        }
        
        $orders = $query->addSelect('orders.*')
                    ->orderBy('orders.tanggal_pesan', 'desc')
                    ->get();

        return response()->json([
            'success' => true,
            'message' => 'Orders with details retrieved',
            'count' => $orders->count(),
            'data' => $orders
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve orders with details',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET orders by user ID - SIMPLE VERSION
Route::get('/orders/user/{userId}', function ($userId) {
    try {
        $orders = DB::table('orders')
            ->where('id_user', $userId)
            ->orderBy('tanggal_pesan', 'desc')
            ->get();

        // Get items untuk setiap order
        foreach ($orders as $order) {
            $orderItems = DB::table('order_item')
                ->join('laundry_services', 'order_item.id_service', '=', 'laundry_services.id_service')
                ->where('order_item.id_order', $order->id_order)
                ->get();
            
            $order->items = $orderItems;

            // Get payment info
            $payment = DB::table('payments')
                ->where('id_order', $order->id_order)
                ->first();
            $order->payment = $payment;
        }

        return response()->json([
            'success' => true,
            'message' => 'User orders retrieved successfully',
            'count' => $orders->count(),
            'data' => $orders
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve user orders',
            'error' => $e->getMessage()
        ], 500);
    }
});

// GET single order by ID
Route::get('/orders/{id}', function ($id) {
    try {
        $order = DB::table('orders')->where('id_order', $id)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Get order items
        $orderItems = DB::table('order_item')
            ->join('laundry_services', 'order_item.id_service', '=', 'laundry_services.id_service')
            ->where('order_item.id_order', $id)
            ->get();
        
        $order->items = $orderItems;

        // Get payment info
        $payment = DB::table('payments')
            ->where('id_order', $id)
            ->first();
        $order->payment = $payment;

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data' => $order
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve order',
            'error' => $e->getMessage()
        ], 500);
    }
});

// CREATE new order
Route::post('/orders', function (Request $request) {
    try {
        DB::beginTransaction();

        $orderData = [
            'id_user' => $request->input('id_user'),
            'id_zone' => $request->input('id_zone', 1),
            'status' => 'menunggu',
            'tanggal_pesan' => now()
        ];

        // Insert order
        $orderId = DB::table('orders')->insertGetId($orderData);

        // Insert order items
        $items = $request->input('items', []);
        foreach ($items as $item) {
            DB::table('order_item')->insert([
                'id_order' => $orderId,
                'id_service' => $item['id_service'],
                'jumlah' => $item['jumlah'] ?? 0,
                'berat_kg' => $item['berat_kg'] ?? 0,
                'subtotal' => $item['subtotal']
            ]);
        }

        // Create payment record
        $totalAmount = collect($items)->sum('subtotal');
        
        DB::table('payments')->insert([
            'id_order' => $orderId,
            'metode' => $request->input('payment_method', 'cash'),
            'jumlah' => $totalAmount + 5000, // Default delivery fee
            'status' => 'ditunda'
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => ['id_order' => $orderId]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to create order',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== MASTER DATA ====================
Route::get('/services', function () {
    try {
        $services = DB::table('laundry_services')->get();
        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::get('/zones', function () {
    try {
        $zones = DB::table('delivery_zones')->get();
        return response()->json([
            'success' => true,
            'data' => $zones
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// ==================== QUICK DATA GENERATOR ====================
Route::get('/quick-generate', function () {
    try {
        // Cek dulu apakah ada data user
        $user = DB::table('user')->first();
        if (!$user) {
            // Buat user sample jika tidak ada
            $userId = DB::table('user')->insertGetId([
                'nama' => 'Test User',
                'email' => 'test@mail.com', 
                'password' => '12345',
                'no_handphone' => '08123456789',
                'alamat' => 'Jl. Test No. 1',
                'created_at' => now()
            ]);
        } else {
            $userId = $user->id_user ?? $user->id;
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
>>>>>>> f060a5238c9be001064f299807a527823f9b6ff1
