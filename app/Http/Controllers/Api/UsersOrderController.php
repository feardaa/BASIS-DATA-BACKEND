<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\LaundryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Digunakan untuk mendapatkan ID User yang sedang login

class UsersOrderController extends Controller
{
    /**
     * MENGAMBIL DAFTAR ORDER PENGGUNA (RIWAYAT)
     * Endpoint: GET /api/user/orders
     */
    public function index(Request $request)
    {
        // PENTING: Gunakan mekanisme autentikasi Anda (misalnya Auth::id())
        // Karena sistem Auth belum sepenuhnya diimplementasikan, kita gunakan ID dummy/input untuk debugging
        $userId = $request->input('id_user', 1);

        try {
            $orders = Order::where('id_user', $userId)
                // Urutkan dari yang terbaru
                ->orderBy('tanggal_pesan', 'desc')
                // Ambil juga detail Order Items dan Service (Eager Loading)
                ->with('items.service')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Riwayat order berhasil diambil',
                'data' => $orders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * MEMBUAT ORDER BARU
     * Endpoint: POST /api/user/order
     */
    public function store(Request $request)
    {
        // PENTING: Gunakan mekanisme autentikasi Anda (misalnya Auth::id())
        // Karena sistem Auth belum sepenuhnya diimplementasikan, kita gunakan ID dummy/input untuk debugging
        $userId = $request->input('id_user', 1);

        try {
            // 1. VALIDASI INPUT
            $request->validate([
                'id_zone' => 'required|exists:delivery_zones,id_zone', // Pastikan zona valid
                'catatan' => 'nullable|string',
                // items harus berupa array dan setiap item harus valid
                'items' => 'required|array|min:1',
                // id_service harus ada di tabel laundry_services
                'items.*.id_service' => 'required|exists:laundry_services,id_service',
                // salah satu dari ini harus ada, tergantung jenis layanannya nanti
                'items.*.quantity' => 'nullable|numeric|min:0', // untuk jumlah (pcs)
                'items.*.weight' => 'nullable|numeric|min:0.1', // untuk berat (kg)
            ]);

            // 2. INTI LOGIKA - TRANSAKSI DATABASE
            $newOrder = DB::transaction(function () use ($request, $userId) {
                $totalHarga = 0;
                $orderItemsData = [];

                // Hitung total harga dan siapkan data item
                foreach ($request->items as $item) {
                    $serviceId = $item['id_service'];
                    $quantity = $item['quantity'] ?? 0;
                    $weight = $item['weight'] ?? 0.00;

                    // Ambil detail layanan (termasuk harga dan satuan)
                    $service = LaundryService::find($serviceId);

                    if (!$service) {
                        throw new \Exception("Layanan dengan ID {$serviceId} tidak ditemukan.");
                    }

                    $pricePerUnit = $service->harga_per_satuan;

                    // Tentukan nilai yang akan digunakan (berat atau jumlah)
                    $useValue = 0;
                    if ($service->satuan == 'per_kg' && $weight > 0) {
                        $useValue = $weight;
                    } elseif ($service->satuan == 'per_pcs' && $quantity > 0) {
                        $useValue = $quantity;
                    }

                    if ($useValue <= 0) {
                        throw new \Exception("Kuantitas/berat harus lebih dari nol untuk layanan {$service->nama_service}.");
                    }

                    // Hitung subtotal untuk item ini: Harga per satuan * useValue
                    $subtotal = $pricePerUnit * $useValue;
                    $totalHarga += $subtotal;

                    $orderItemsData[] = [
                        'id_service' => $serviceId,
                        'jumlah' => $quantity,
                        'berat_kg' => $weight,
                        'subtotal' => $subtotal,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // 3. BUAT DATA ORDER UTAMA
                $order = Order::create([
                    'id_user' => $userId,
                    'id_zone' => $request->id_zone,
                    'tanggal_pesan' => now(),
                    'status' => 'menunggu', // Status awal
                    'catatan' => $request->catatan,
                    // total_harga di sini akan diabaikan karena tidak ada di kolom orders di migrasi Anda,
                    // Tapi biasanya transaksi yang benar memiliki kolom 'total_harga' di tabel orders.
                    // Jika Anda ingin menyimpan total, Anda harus tambahkan $table->decimal('total_harga', 10, 2)->nullable(); di migrasi orders.
                ]);

                // 4. BUAT ORDER ITEMS
                foreach ($orderItemsData as $itemData) {
                    $itemData['id_order'] = $order->id_order;
                    OrderItem::create($itemData);
                }

                return $order;
            });

            // 5. RESPONSE BERHASIL
            // Muat ulang dengan detail relasi untuk response
            $newOrder->load('items.service', 'user', 'zone');

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat dan menunggu konfirmasi',
                'data' => $newOrder
            ], 201); // 201 Created

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi input gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Ini akan menangkap kegagalan transaksi atau error lainnya
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order. Pastikan semua data item dan zona benar.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}