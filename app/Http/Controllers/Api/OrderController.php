<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Users; // Asumsi Model User Anda bernama Users
use App\Models\Driver;
use App\Models\DeliveryZone;
use App\Models\LaundryService;

class OrderController extends Controller
{
    /**
     * Mendapatkan daftar semua orders (Menggunakan Eloquent Relasi).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Menggunakan Eloquent with() untuk eager loading relasi
            $orders = Order::with('user:id_users,nama') // Hanya ambil id dan nama user
                ->select('id_order', 'id_user', 'status', 'tanggal_pesan', 'tanggal_selesai', 'created_at', 'updated_at')
                ->orderBy('tanggal_pesan', 'desc')
                ->get();

            // Transformasi data untuk menyesuaikan output
            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id_order' => $order->id_order,
                    'customer_name' => $order->user->nama ?? 'N/A',
                    'status' => $order->status,
                    'tanggal_pesan' => $order->tanggal_pesan,
                    'tanggal_selesai' => $order->tanggal_selesai,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Daftar Order berhasil diambil',
                'data' => $formattedOrders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar order',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Membuat Order baru (Create/Store Order) menggunakan Transaction dan Eloquent.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'id_user' => 'required|integer|exists:users,id_users', 
            'id_zone' => 'required|integer|exists:delivery_zones,id_zone',
            'catatan' => 'nullable|string',
            'tanggal_jemput' => 'nullable|date_format:Y-m-d H:i:s',
            
            // Validasi Items (minimum 1 item)
            'items' => 'required|array|min:1',
            'items.*.id_service' => 'required|integer|exists:laundry_services,id_service',
            // Harus ada 'jumlah' (untuk satuan) atau 'berat_kg' (untuk kiloan)
            'items.*.jumlah' => 'required_without:items.*.berat_kg|nullable|integer|min:1', 
            'items.*.berat_kg' => 'required_without:items.*.jumlah|nullable|numeric|min:0.1', 
            
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

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $totalBerat = 0; 
            $kategoriLaundry = [];
            $orderItemsData = []; // Untuk menyimpan data item sebelum bulk insert

            // 2. Hitung Total Harga dan Kumpulkan Item
            foreach ($request->items as $item) {
                $service = LaundryService::find($item['id_service']); 
                
                // Cek ketersediaan layanan sudah dilakukan di validasi, ini untuk memastikan harga
                $price = $service->harga; 
                $qty = $item['jumlah'] ?? 0;
                $weight = $item['berat_kg'] ?? 0.00;
                $subtotal = 0;
                
                if ($weight > 0) {
                    $subtotal = $price * $weight;
                    $totalBerat += $weight;
                    if (!in_array('kiloan', $kategoriLaundry)) { $kategoriLaundry[] = 'kiloan'; }
                } else if ($qty > 0) {
                    $subtotal = $price * $qty;
                    if (!in_array('satuan', $kategoriLaundry)) { $kategoriLaundry[] = 'satuan'; }
                } else {
                    // Walaupun sudah divalidasi, ini untuk double check (harusnya tidak tercapai)
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Item layanan harus memiliki jumlah atau berat_kg.'
                    ], 400);
                }

                $totalAmount += $subtotal;
                
                // Kumpulkan data order item
                $orderItemsData[] = [
                    'id_service' => $item['id_service'],
                    'jumlah' => $qty,
                    'berat_kg' => $weight,
                    'subtotal' => $subtotal,
                    'created_at' => now(), // Tambahkan timestamp untuk bulk insert
                    'updated_at' => now(), // Tambahkan timestamp untuk bulk insert
                ];
            }
            
            // 3. Ambil Ongkir
            $zone = DeliveryZone::find($request->id_zone);
            $ongkir = $zone->ongkir ?? 0;
            $finalTotalAmount = $totalAmount + $ongkir;

            // 4. Buat Entri Order
            $order = Order::create([
                'id_user' => $request->id_user,
                'id_zone' => $request->id_zone,
                'id_driver' => null, 
                'status' => 'menunggu', 
                'tanggal_pesan' => now(),
                'tanggal_jemput' => $request->tanggal_jemput ?? null,
                'catatan' => $request->catatan ?? null,
                'berat_total' => $totalBerat,
                'kategori_laundry' => $kategoriLaundry, 
            ]);
            
            $orderId = $order->id_order; 
            
            // 5. Buat Entri Order Item (Bulk Insert)
            $finalOrderItems = [];
            foreach ($orderItemsData as $item) {
                // Tambahkan foreign key id_order ke setiap item
                $finalOrderItems[] = array_merge($item, ['id_order' => $orderId]);
            }
            OrderItem::insert($finalOrderItems); 
            
            // 6. Buat Entri Payment
            Payment::create([
                'id_order' => $orderId,
                'metode' => $request->payment_method,
                'jumlah' => $finalTotalAmount, 
                'status' => 'belum_bayar', 
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data' => [
                    'id_order' => $orderId,
                    'total_pembayaran' => $finalTotalAmount, 
                    'ongkir' => $ongkir,
                    'status' => 'menunggu'
                ]
            ], 201); 

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order. Terjadi kesalahan pada server.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu order (Menggunakan Eloquent Eager Loading).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Ambil Order dan semua relasinya dalam satu query
            $order = Order::with([
                'user:id_users,nama,no_handphone,alamat', 
                'driver:id_driver,nama', 
                'zone:id_zone,nama_zone,ongkir',
                // Ambil Item dan layanan terkait (nama, harga)
                'items.service:id_service,nama_service,harga', 
                'payment',
            ])
            ->where('id_order', $id)
            ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail Order berhasil diambil',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail order',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Memperbarui status order dan menetapkan driver (UPDATE).
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
                'id_driver' => 'nullable|integer|exists:drivers,id_driver',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan'
                ], 404);
            }

            $updateData = [];
            $messageParts = [];

            // Logika Pembaruan Status
            if ($request->has('status')) {
                $status = $request->status;
                $updateData['status'] = $status;
                $messageParts[] = "Status diperbarui menjadi '{$status}'";

                if ($status == 'selesai') {
                    $updateData['tanggal_selesai'] = now();
                }
            }

            // Logika Penugasan Driver
            if ($request->has('id_driver')) {
                $driverId = $request->id_driver;

                // Cek apakah sudah ada driver (jika ingin menghindari re-assign)
                if ($order->id_driver !== null && $order->id_driver != $driverId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order sudah memiliki driver yang ditetapkan. Harap batalkan atau ganti secara eksplisit.'
                    ], 400);
                }
                
                $currentStatus = $request->status ?? $order->status;
                if ($currentStatus == 'menunggu') {
                    $updateData['id_driver'] = $driverId;

                    if (!isset($updateData['status'])) {
                        // Otomatis ubah status menjadi dijemput saat driver ditetapkan
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
            
            // Logika Pembatalan Penugasan Driver (set id_driver = null)
            if ($request->has('id_driver') && $request->id_driver === null) {
                if ($order->id_driver !== null) {
                    $updateData['id_driver'] = null;
                    $messageParts[] = "Penugasan Driver dibatalkan";
                }
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data status atau driver yang diberikan untuk diperbarui.'
                ], 400);
            }

            // Lakukan pembaruan
            $order->update($updateData);

            $finalMessage = "Order ID {$id} berhasil diperbarui. " . implode(' dan ', $messageParts);

            return response()->json([
                'success' => true,
                'message' => $finalMessage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui order',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menghapus order (DESTROY).
     * Pastikan Model Order memiliki cascading delete untuk OrderItem dan Payment.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan'
                ], 404);
            }

            // Jika relasi sudah diatur di Model Order menggunakan 'deleting' event atau
            // di database, maka delete ini akan menghapus semua item dan payment terkait.
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus order',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
}