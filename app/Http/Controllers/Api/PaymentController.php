<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Models\Payment;
use App\Models\Order; // Diperlukan untuk relasi

class PaymentController extends Controller
{
    /**
     * Mendapatkan daftar semua payments dengan detail order terkait.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Eager loading: Pastikan kolom foreign key di 'order' ('id_order', 'id_user') disertakan.
            // Pastikan 'order.user' juga di-load dengan kolom yang dibutuhkan ('id_users', 'nama').
            $payments = Payment::with([
                'order:id_order,id_user,status,tanggal_pesan', // Pastikan id_user ada di tabel orders
                'order.user:id_users,nama' // Pastikan id_users dan nama ada di tabel users
            ])
            ->get();

            // Transformasi data untuk presentasi yang lebih baik
            $formattedPayments = $payments->map(function ($payment) {
                
                $orderData = $payment->order;
                $customerName = 'N/A';
                $tanggalOrder = null;

                // FIX: Menggunakan operator Nullsafe (?) yang lebih modern 
                // (tersedia di PHP 8.0+) untuk mencegah error jika order atau user null.
                if ($orderData) {
                    $tanggalOrder = $orderData->tanggal_pesan;
                    // Mengakses user melalui order, menggunakan Nullsafe operator (?)
                    $customerName = $orderData->user?->nama ?? 'N/A (User Dihapus)';
                }
                
                return [
                    'id_payment' => $payment->id_payment,
                    'id_order' => $payment->id_order,
                    'customer_name' => $customerName, 
                    'jumlah' => $payment->jumlah,
                    'metode' => $payment->metode,
                    'status' => $payment->status,
                    'tanggal_order' => $tanggalOrder,
                    'tanggal_bayar' => $payment->tanggal_bayar,
                    'created_at' => $payment->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Daftar Pembayaran berhasil diambil',
                'data' => $formattedPayments
            ]);

        } catch (\Exception $e) {
            // Log error untuk debugging di server
            \Illuminate\Support\Facades\Log::error("Payment Index Error: " . $e->getMessage(), ['exception' => $e]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar pembayaran',
                // Tampilkan pesan error detail hanya jika APP_DEBUG=true
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu payment.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Menggunakan Eloquent dengan eager loading
            $payment = Payment::with([
                'order:id_order,id_user,status,tanggal_pesan', 
                'order.user:id_users,nama,alamat'
            ])
            ->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail Pembayaran berhasil diambil',
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Payment Show Error: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pembayaran',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Memperbarui status pembayaran. Khusus untuk konfirmasi 'dibayar'.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:dibayar,gagal', // Biasanya status ini diubah oleh Admin/Sistem
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
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran tidak ditemukan'
                ], 404);
            }

            $newStatus = $request->status;

            if ($payment->status === 'dibayar') {
                 DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran sudah lunas.'
                ], 400);
            }

            // 2. Update Status Pembayaran
            $payment->status = $newStatus;
            
            if ($newStatus === 'dibayar') {
                $payment->tanggal_bayar = now();
            }
            
            $payment->save();
            
            // 3. (Opsional) Sinkronisasi Status Order
            // Jika pembayaran dibayar, status Order mungkin perlu diperbarui juga,
            // tergantung alur bisnis Anda. Contoh: jika order sedang diproses
            // dan pembayaran belum lunas, setelah lunas, status tetap 'diproses'.
            // Di sini kita asumsikan status Order tidak otomatis berubah hanya karena Payment Lunas.
            // Jika Anda ingin status Order otomatis menjadi 'Lunas' atau 'Siap Ambil',
            // Anda dapat menambahkannya di sini.

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Status Pembayaran ID {$id} berhasil diperbarui menjadi '{$newStatus}'",
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Payment Update Error: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status pembayaran',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

}