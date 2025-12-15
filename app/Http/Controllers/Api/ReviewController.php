<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Models\Review; // Asumsi Anda memiliki model Review
use App\Models\Order; // Diperlukan untuk validasi relasi

class ReviewController extends Controller
{
    /**
     * Mendapatkan daftar semua reviews dengan detail order dan user terkait.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Menggunakan Eloquent dengan eager loading
            $reviews = Review::with([
                'order:id_order,id_user',
                'order.user:id_users,nama' // Chain loading ke User melalui Order
            ])
            ->get();

            // Transformasi data untuk presentasi yang lebih baik
            // PERBAIKAN: Menggunakan 'tanggal_review' karena timestamps non-aktif
            $formattedReviews = $reviews->map(function ($review) {
                return [
                    'id_review' => $review->id_review,
                    'id_order' => $review->id_order,
                    'rating' => $review->rating,
                    'komentar' => $review->komentar,
                    'customer_name' => $review->order->user->nama ?? 'N/A',
                    'tanggal_review' => $review->tanggal_review, // DIGANTI DARI created_at
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Daftar Review berhasil diambil',
                'data' => $formattedReviews
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar review',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu review.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            // Menggunakan Eloquent dengan eager loading
            $review = Review::with([
                'order:id_order,id_user,status',
                'order.user:id_users,nama'
            ])
            ->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail Review berhasil diambil',
                'data' => $review
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail review',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Membuat review baru (STORE).
     * Review biasanya terkait dengan Order yang sudah selesai.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'id_order' => [
                'required',
                'integer',
                // Pastikan Order ada di tabel orders
                'exists:orders,id_order',
                // Pastikan belum ada review untuk order ini
                'unique:reviews,id_order', 
            ],
            'rating' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Opsional: Cek apakah Order sudah berstatus 'selesai' sebelum memberikan review
        $order = Order::find($request->id_order);
        if ($order && $order->status !== 'selesai') {
             return response()->json([
                'success' => false,
                'message' => 'Review hanya dapat diberikan untuk order yang sudah selesai.'
            ], 400);
        }


        try {
            // 2. Buat Review
            // PERBAIKAN: Secara manual mengisi tanggal_review karena timestamps di Model non-aktif
            $review = Review::create([
                'id_order' => $request->id_order,
                'rating' => $request->rating,
                'komentar' => $request->komentar,
                'tanggal_review' => now(), // Tambahkan pengisian tanggal manual
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review berhasil ditambahkan',
                'data' => $review
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat review',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Memperbarui review yang sudah ada (UPDATE).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        // 1. Cari Review
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review tidak ditemukan'
            ], 404);
        }

        // 2. Validasi Input
        $validator = Validator::make($request->all(), [
            'rating' => 'nullable|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 3. Perbarui Review
            $review->update($request->only([
                'rating',
                'komentar',
            ]));
            
            // Catatan: tanggal_review tidak di-update di sini, hanya rating dan komentar

            return response()->json([
                'success' => true,
                'message' => 'Review berhasil diperbarui',
                'data' => $review
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui review',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menghapus review (DESTROY).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review tidak ditemukan'
                ], 404);
            }

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus review',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }
}