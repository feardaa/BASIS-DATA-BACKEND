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
     * Endpoint: GET /api/orders/all
     * Middleware: auth:sanctum (Hanya untuk pengguna terotentikasi, idealnya Admin/Staff)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Menggunakan Eloquent with() untuk eager loading relasi
            // Sesuaikan relasi (user, driver, items.service) dengan yang ada di Model Order Anda.
            $orders = Order::with([
                'user:id_users,nama,email', // Pilih kolom user yang ingin ditampilkan
                'driver:id_driver,nama,no_handphone', // Pilih kolom driver
                'items.service' // Ambil OrderItem dan detail LaundryService
            ])
                ->latest() // Mengambil yang terbaru lebih dulu
                ->get();

            // Cek apakah ada orders
            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada data order ditemukan.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Daftar semua orders berhasil diambil.',
                'total' => $orders->count(),
                'data' => $orders
            ], 200);

        } catch (\Exception $e) {
            // Error 500 (Internal Server Error)
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar orders.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    // ... method store(), show(), indexByUser(), updateStatus(), dan method lain di sini

    /**
     * Membuat Order Baru (STORE).
     * ... (Lanjutan kode Anda)
     */
    public function store(Request $request): JsonResponse
    {
        // ... (Isi dari method store Anda yang sudah ada)
        try {
            // Validasi input
            $validatedData = Validator::make($request->all(), [
                'id_user' => 'required|exists:users,id_users',
                'id_driver' => 'nullable|exists:drivers,id_driver',
                'id_zone' => 'required|exists:delivery_zones,id_zone',
                'tanggal_pesan' => 'required|date',
                'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_pesan',
                'metode_pembayaran' => 'required|in:tunai,transfer',
                'is_pickup_delivery' => 'required|boolean',
                'alamat_detail' => 'nullable|string',
                'total_berat' => 'nullable|numeric',
                'catatan' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.id_service' => 'required|exists:laundry_services,id_service',
                'items.*.kuantitas' => 'required|integer|min:1',
            ])->validate();

            // Mulai transaksi database
            DB::beginTransaction();

            // 1. Hitung total harga item
            $subtotal = 0;
            $itemsData = [];

            // Ambil semua service yang dibutuhkan
            $serviceIds = collect($validatedData['items'])->pluck('id_service')->toArray();
            $services = LaundryService::whereIn('id_service', $serviceIds)->get()->keyBy('id_service');

            foreach ($validatedData['items'] as $item) {
                $service = $services->get($item['id_service']);
                if (!$service) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Layanan laundry tidak ditemukan.'
                    ], 404);
                }

                $hargaItem = $service->harga * $item['kuantitas'];
                $subtotal += $hargaItem;

                $itemsData[] = [
                    'id_service' => $item['id_service'],
                    'kuantitas' => $item['kuantitas'],
                    'harga_per_unit' => $service->harga,
                    'subtotal_item' => $hargaItem,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 2. Ambil biaya delivery
            $deliveryZone = DeliveryZone::find($validatedData['id_zone']);
            $deliveryFee = $deliveryZone->biaya;

            // 3. Hitung total order
            $totalOrder = $subtotal + $deliveryFee;

            // 4. Buat Order Baru
            $order = Order::create([
                'id_user' => $validatedData['id_user'],
                'id_driver' => $validatedData['id_driver'] ?? null,
                'id_zone' => $validatedData['id_zone'],
                'tanggal_pesan' => $validatedData['tanggal_pesan'],
                'tanggal_selesai' => $validatedData['tanggal_selesai'] ?? null,
                'status' => 'pending', // Default status saat order dibuat
                'metode_pembayaran' => $validatedData['metode_pembayaran'],
                'is_pickup_delivery' => $validatedData['is_pickup_delivery'],
                'alamat_detail' => $validatedData['alamat_detail'] ?? null,
                'total_berat' => $validatedData['total_berat'] ?? null,
                'catatan' => $validatedData['catatan'] ?? null,
                'subtotal' => $subtotal,
                'biaya_delivery' => $deliveryFee,
                'total_order' => $totalOrder,
            ]);

            // 5. Simpan Item Order
            $order->items()->createMany($itemsData);

            // 6. Buat entri pembayaran
            Payment::create([
                'id_order' => $order->id_order,
                'tanggal_pembayaran' => null, // Pembayaran belum dilakukan
                'jumlah_pembayaran' => $totalOrder,
                'metode' => $validatedData['metode_pembayaran'],
                'status_pembayaran' => 'belum_dibayar',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat!',
                'data' => $order->load(['user', 'driver', 'items.service', 'payment'])
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
                'message' => 'Order gagal dibuat',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }


    /**
     * Mendapatkan detail Order (SHOW).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Menggunakan Eloquent find() dan with() untuk eager loading relasi
            $order = Order::with(['user', 'driver', 'items.service', 'payment'])
                ->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail order berhasil diambil',
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
     * Mendapatkan daftar Order untuk user tertentu (indexByUser).
     *
     * @param int $id_user
     * @return JsonResponse
     */
    public function indexByUser(int $id_user): JsonResponse
    {
        try {
            // Ambil semua orders untuk user tertentu, urutkan berdasarkan tanggal terbaru
            $orders = Order::where('id_user', $id_user)
                ->with(['items.service']) // Muat relasi item dan service
                ->latest() // Urutkan dari yang terbaru
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada order ditemukan untuk user ini.',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Daftar order user berhasil diambil.',
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar order user.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }


    /**
     * Mengubah status Order (updateStatus).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan'
                ], 404);
            }

            // Validasi status baru
            $validatedData = Validator::make($request->all(), [
                'status' => 'required|in:pending,diterima,diproses,siap_diambil,perjalanan,selesai,dibatalkan',
                'tanggal_selesai' => 'nullable|date', // Opsional, hanya diisi jika status = selesai
            ])->validate();

            // Update status dan tanggal selesai (jika ada)
            $order->status = $validatedData['status'];
            if ($validatedData['status'] === 'selesai' && !empty($validatedData['tanggal_selesai'])) {
                $order->tanggal_selesai = $validatedData['tanggal_selesai'];
            }

            $order->save();

            // Pesan notifikasi
            $finalMessage = 'Status order berhasil diperbarui menjadi ' . $order->status;
            if ($order->status === 'selesai') {
                $finalMessage .= ' dan order telah selesai.';
            }

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