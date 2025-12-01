<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Models\Order; // Asumsi model Order sudah dibuat

class OrderController extends Controller
{
    /**
     * Mendapatkan daftar semua orders.
     * Termasuk join ke tabel user untuk mendapatkan nama pelanggan.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Mengambil daftar order dengan join ke tabel users
            $orders = DB::table('orders as o')
                ->select(
                    'o.id_order',
                    'u.nama as customer_name',
                    'o.status',
                    'o.tanggal_pesan',
                    'o.tanggal_selesai',
                    'o.created_at',
                    'o.updated_at'
                )
                // Pastikan nama tabel user Anda adalah 'users' dan PK-nya 'id'
                ->join('users as u', 'o.id_user', '=', 'u.id')
                ->orderBy('o.tanggal_pesan', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar Order berhasil diambil',
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Membuat Order baru (Create/Store Order).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'id_user' => 'required|integer|exists:users,id', // Ganti 'id' jika PK user berbeda
            'id_zone' => 'required|integer|exists:delivery_zones,id_zone',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            // Validasi untuk setiap item dalam array
            'items.*.id_service' => 'required|integer|exists:laundry_services,id_service',
            'items.*.jumlah' => 'required_without:items.*.berat_kg|nullable|integer|min:1', // Untuk satuan
            'items.*.berat_kg' => 'required_without:items.*.jumlah|nullable|numeric|min:0.1', // Untuk kiloan
            // Validasi Pembayaran
            'payment_method' => 'required|in:cash,transfer,ewallet',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mulai Transaksi Database
        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $orderItems = [];

            // 2. Hitung Total Harga dan Kumpulkan Item
            foreach ($request->items as $item) {
                $service = DB::table('laundry_services')
                    ->where('id_service', $item['id_service'])
                    ->first();

                if (!$service) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Layanan dengan ID ' . $item['id_service'] . ' tidak ditemukan.'
                    ], 404);
                }

                $qty = $item['jumlah'] ?? 0;
                $weight = $item['berat_kg'] ?? 0.00;
                $price = $service->harga;

                // Cek apakah layanan adalah kiloan (harga/kg) atau satuan (harga/item)
                // Asumsi: jika berat_kg > 0, maka kiloan. Jika jumlah > 0, maka satuan.
                if ($weight > 0) {
                    $subtotal = $price * $weight; // Hitung subtotal kiloan
                } else if ($qty > 0) {
                    $subtotal = $price * $qty; // Hitung subtotal satuan
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Item layanan ' . $service->nama_service . ' harus memiliki jumlah atau berat_kg.'
                    ], 400);
                }

                $totalAmount += $subtotal;

                $orderItems[] = [
                    'id_service' => $item['id_service'],
                    'jumlah' => $qty,
                    'berat_kg' => $weight,
                    'subtotal' => $subtotal,
                ];
            }

            // 3. Tambah Entri ke Tabel 'orders'
            $orderId = DB::table('orders')->insertGetId([
                'id_user' => $request->id_user,
                'id_zone' => $request->id_zone,
                'id_driver' => null, // Driver kosong saat order dibuat
                'status' => 'menunggu', // Status default
                'tanggal_pesan' => now(),
                'catatan' => $request->catatan ?? null,
                'created_at' => now(),
                // 'updated_at' akan otomatis terisi jika menggunakan model Eloquent,
                // tapi karena ini menggunakan Query Builder, kita isi manual
                'updated_at' => now(),
            ]);

            // 4. Tambah Entri ke Tabel 'order_item'
            $orderItemData = [];
            foreach ($orderItems as $item) {
                $orderItemData[] = array_merge($item, ['id_order' => $orderId]);
            }
            DB::table('order_item')->insert($orderItemData);

            // 5. Tambah Entri ke Tabel 'payments'
            DB::table('payments')->insert([
                'id_order' => $orderId,
                'metode' => $request->payment_method,
                'jumlah' => $totalAmount,
                'status' => 'ditunda', // Status default pembayaran
            ]);

            // Commit Transaksi
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data' => [
                    'id_order' => $orderId,
                    'total_pembayaran' => $totalAmount,
                    'status' => 'menunggu'
                ]
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // Rollback Transaksi jika ada error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order. Terjadi kesalahan pada server.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu order.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // 1. Ambil detail Order, User, Driver, dan Zone
            $order = DB::table('orders as o')
                ->where('o.id_order', $id)
                ->select(
                    'o.*',
                    'u.nama as customer_name',
                    'u.no_handphone as customer_phone',
                    'd.nama as driver_name',
                    'z.nama_zona as zone_name'
                )
                // Pastikan PK user di sini (asumsi: 'id')
                ->join('users as u', 'o.id_user', '=', 'u.id')
                ->leftJoin('drivers as d', 'o.id_driver', '=', 'd.id_driver')
                ->join('delivery_zones as z', 'o.id_zone', '=', 'z.id_zone')
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan'
                ], 404);
            }

            // 2. Ambil Item-item dalam Order
            $items = DB::table('order_item as oi')
                ->where('oi.id_order', $id)
                ->select(
                    'oi.*',
                    'ls.nama_service',
                    'ls.harga'
                )
                ->join('laundry_services as ls', 'oi.id_service', '=', 'ls.id_service')
                ->get();

            // 3. Ambil detail Pembayaran
            $payment = DB::table('payments')
                ->where('id_order', $id)
                ->first();

            $order->items = $items;
            $order->payment = $payment;

            return response()->json([
                'success' => true,
                'message' => 'Detail Order berhasil diambil',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memperbarui status order dan menetapkan driver (digunakan oleh Admin/Driver).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:menunggu,dijemput,diproses,selesai,dibatalkan',
                // Tambahkan validasi untuk id_driver: harus ada jika ingin menetapkan driver
                'id_driver' => 'nullable|integer|exists:drivers,id_driver',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ambil data order saat ini
            $order = DB::table('orders')->where('id_order', $id)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan'
                ], 404);
            }

            $updateData = [];
            $messageParts = [];

            // --- Logika Pembaruan Status ---
            if ($request->has('status')) {
                $status = $request->status;
                $updateData['status'] = $status;
                $messageParts[] = "Status diperbarui menjadi '{$status}'";

                // Jika status diubah menjadi 'selesai', set tanggal_selesai
                if ($status == 'selesai') {
                    $updateData['tanggal_selesai'] = now();
                }
            }

            // --- Logika Penugasan Driver ---
            if ($request->has('id_driver')) {
                $driverId = $request->id_driver;

                // Cek apakah order sudah memiliki driver
                if ($order->id_driver !== null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order sudah memiliki driver yang ditetapkan. Batalkan penugasan driver yang ada terlebih dahulu.'
                    ], 400);
                }

                // Hanya izinkan penetapan driver jika statusnya 'menunggu' (default)
                $currentStatus = $request->status ?? $order->status;
                if ($currentStatus == 'menunggu') {
                    $updateData['id_driver'] = $driverId;

                    // Otomatis ubah status menjadi 'dijemput' saat driver ditetapkan
                    // Ini adalah asumsi alur standar: Driver ditetapkan -> Proses penjemputan dimulai
                    if (!isset($updateData['status'])) {
                        $updateData['status'] = 'dijemput';
                        $messageParts[] = "Status otomatis diubah menjadi 'dijemput'";
                    }

                    $messageParts[] = "Driver ID {$driverId} telah ditetapkan";

                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "Driver hanya dapat ditetapkan pada order dengan status 'menunggu'. Status saat ini: {$currentStatus}"
                    ], 400);
                }
            }

            // Cek apakah ada data yang perlu diperbarui
            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data status atau driver yang diberikan untuk diperbarui.'
                ], 400);
            }

            // Lakukan pembaruan
            $affected = DB::table('orders')
                ->where('id_order', $id)
                ->update($updateData);

            $finalMessage = "Order ID {$id} berhasil diperbarui. " . implode(' dan ', $messageParts);

            return response()->json([
                'success' => true,
                'message' => $finalMessage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}