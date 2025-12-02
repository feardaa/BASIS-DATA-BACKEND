<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User; // Tambahkan Model User
use App\Models\OrderItem;
use App\Models\LaundryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // Tambahkan Validator
use Illuminate\Support\Facades\Hash; // Tambahkan Hash untuk password
use Illuminate\Http\JsonResponse;
use Carbon\Carbon; // Untuk manajemen tanggal

class UsersOrderController extends Controller
{
    // =========================================================================
    // I. AUTENTIKASI DAN PROFIL (Sesuai rute POST api/register, api/login, api/user/profile)
    // =========================================================================

    /**
     * Pendaftaran Pengguna Baru (POST api/register)
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'no_handphone' => 'required|string|max:15|unique:users,no_handphone',
                'alamat' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi pendaftaran gagal', 'errors' => $validator->errors()], 422);
            }

            $user = User::create([
                'nama' => $request->nama,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'no_handphone' => $request->no_handphone,
                'alamat' => $request->alamat,
            ]);

            // Asumsi: Menggunakan Laravel Sanctum
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran user berhasil!',
                'data' => ['user' => $user->only(['id', 'nama', 'email']), 'token' => $token]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Pendaftaran gagal.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Login Pengguna (POST api/login)
     * CATATAN: Anda mungkin perlu mengaitkan rute 'api/login' ke method ini.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => ['user' => $user->only(['id', 'nama', 'email']), 'token' => $token]
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'Email atau Password salah'], 401);
    }
    
    /**
     * Menampilkan profil user (GET api/user/profile/{id})
     */
    public function showProfile($id): JsonResponse
    {
        try {
            $user = User::select('id', 'nama', 'email', 'no_handphone', 'alamat', 'created_at')->find($id);

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
            }
            
            // Opsional: Cek Otorisasi (Pastikan user hanya bisa melihat profilnya sendiri atau admin)
            // if (Auth::id() != $id) { 
            //     return response()->json(['message' => 'Akses ditolak'], 403);
            // }

            return response()->json(['success' => true, 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data profil', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Mengupdate profil user (PUT api/user/profile/{id})
     */
    public function updateProfile(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
            }
            
            $validator = Validator::make($request->all(), [
                'nama' => 'sometimes|string|max:255',
                // Pengecualian ID saat validasi unique
                'email' => 'sometimes|email|unique:users,email,' . $id, 
                'no_handphone' => 'sometimes|string|max:15|unique:users,no_handphone,' . $id,
                'alamat' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
            }

            $user->update($request->only(['nama', 'email', 'no_handphone', 'alamat']));

            return response()->json(['success' => true, 'message' => 'Profil user berhasil diupdate', 'data' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal update profil', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Mengupdate password user (PUT api/user/password/{id})
     */
    public function updatePassword(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password' => 'required|string|min:6|confirmed', 
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['success' => false, 'message' => 'Password lama tidak cocok'], 401);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json(['success' => true, 'message' => 'Password berhasil diubah']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal update password', 'error' => $e->getMessage()], 500);
        }
    }


    // =========================================================================
    // II. MANAJEMEN ORDER (Sesuai rute GET/POST/PUT api/orders, api/orders/user)
    // =========================================================================

    /**
     * MENGAMBIL DAFTAR ORDER PENGGUNA (RIWAYAT) (GET api/orders/user/{id_user})
     * Diambil dari fungsi index() di snippet Anda, disesuaikan ke getUserOrders.
     *
     * @param Request $request
     * @param int $id_user
     * @return JsonResponse
     */
    public function getUserOrders(Request $request, $id_user): JsonResponse
    {
        try {
            // Cek otorisasi: Pastikan Auth::id() sama dengan $id_user, atau admin
            // $userId = Auth::id(); // Jika menggunakan Auth::id()

            $orders = Order::where('id_user', $id_user)
                ->orderBy('tanggal_pesan', 'desc')
                ->with('items.service', 'zone', 'driver') // Sertakan relasi tambahan
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
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Menampilkan detail order (GET api/orders/{id})
     *
     * @param int $id ID Order
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $order = Order::with('items.service', 'user', 'zone', 'driver')->find($id);

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order tidak ditemukan'], 404);
            }

            // Otorisasi: Pastikan user hanya melihat order miliknya
            // if (Auth::id() != $order->id_user) {
            //     return response()->json(['message' => 'Akses ditolak'], 403);
            // }

            return response()->json(['success' => true, 'data' => $order]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil detail order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * MEMBUAT ORDER BARU (POST api/orders)
     * Diambil langsung dari fungsi store() di snippet Anda, disesuaikan ID User.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // PENTING: Jika menggunakan Auth:sanctum, gunakan Auth::id()
        $userId = Auth::check() ? Auth::id() : ($request->input('id_user') ?? 1); // Fallback ke ID 1 jika tidak login
        
        try {
            // 1. VALIDASI INPUT
            $request->validate([
                'id_zone' => 'required|exists:delivery_zones,id_zone',
                'catatan' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.id_service' => 'required|exists:laundry_services,id_service',
                'items.*.quantity' => 'nullable|numeric|min:0',
                'items.*.weight' => 'nullable|numeric|min:0.1',
            ]);

            // 2. INTI LOGIKA - TRANSAKSI DATABASE
            $newOrder = DB::transaction(function () use ($request, $userId) {
                $totalHarga = 0;
                $orderItemsData = [];

                foreach ($request->items as $item) {
                    $serviceId = $item['id_service'];
                    $quantity = $item['quantity'] ?? 0;
                    $weight = $item['weight'] ?? 0.00;

                    $service = LaundryService::find($serviceId);

                    if (!$service) {
                        throw new \Exception("Layanan dengan ID {$serviceId} tidak ditemukan.");
                    }

                    $pricePerUnit = $service->price;
                    $useValue = 0;

                    if ($service->unit == 'kg' && $weight > 0) {
                        $useValue = $weight;
                    } elseif ($service->unit == 'pcs' && $quantity > 0) {
                        $useValue = $quantity;
                    } else {
                        $useValue = ($weight > 0) ? $weight : $quantity;
                    }

                    if ($useValue <= 0) {
                        throw new \Exception("Kuantitas/berat harus lebih dari nol untuk layanan {$service->name}.");
                    }

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
                    'total_harga' => $totalHarga, // Asumsi: Tambahkan total_harga jika ada di model/tabel Order
                    'status' => 'menunggu', 
                    'catatan' => $request->catatan,
                ]);

                // 4. BUAT ORDER ITEMS
                foreach ($orderItemsData as $itemData) {
                    $itemData['id_order'] = $order->id_order;
                    OrderItem::create($itemData);
                }

                return $order;
            });

            $newOrder->load('items.service', 'user', 'zone');

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat dan menunggu konfirmasi',
                'data' => $newOrder
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi input gagal', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order. Pastikan semua data item dan zona benar.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Membatalkan order (PUT api/orders/{id}/cancel)
     *
     * @param int $id ID Order
     * @return JsonResponse
     */
    public function cancelOrder($id): JsonResponse
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order tidak ditemukan'], 404);
            }
            
            // Otorisasi: Pastikan user hanya membatalkan order miliknya
            // if (Auth::id() != $order->id_user) {
            //     return response()->json(['message' => 'Akses ditolak'], 403);
            // }

            // Logika Pembatalan: Hanya order dengan status 'menunggu' atau 'diproses' yang bisa dibatalkan
            if (!in_array($order->status, ['menunggu', 'diproses'])) {
                 return response()->json([
                    'success' => false, 
                    'message' => "Order tidak dapat dibatalkan karena status saat ini adalah '{$order->status}'."
                ], 400);
            }

            $order->status = 'dibatalkan';
            $order->tanggal_batal = Carbon::now();
            $order->save();

            return response()->json(['success' => true, 'message' => 'Order berhasil dibatalkan', 'data' => $order]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membatalkan order', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Mengupdate status order (PUT api/orders/{id}/status)
     * Digunakan oleh user untuk konfirmasi penerimaan/pembayaran, atau oleh Driver/Admin.
     * Untuk User, ini biasanya digunakan untuk mengkonfirmasi 'selesai' setelah driver mengirim.
     *
     * @param Request $request
     * @param int $id ID Order
     * @return JsonResponse
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $order = Order::find($id);
            $newStatus = $request->input('status');
            
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order tidak ditemukan'], 404);
            }
            
            // Otorisasi (Contoh Sederhana):
            // Admin/Driver: Bisa ubah semua status
            // User: Hanya bisa mengkonfirmasi 'selesai'
            $allowedStatuses = ['selesai']; // Status yang boleh diubah oleh user

            // Jika Anda ingin ini hanya untuk User/OrderController:
            if (!in_array($newStatus, $allowedStatuses)) {
                return response()->json(['success' => false, 'message' => 'Perubahan status ini memerlukan hak akses Admin/Driver.'], 403);
            }

            if ($order->status == $newStatus) {
                return response()->json(['success' => true, 'message' => "Status order sudah {$newStatus}"], 200);
            }

            $order->status = $newStatus;
            
            if ($newStatus == 'selesai') {
                $order->tanggal_selesai = Carbon::now();
            }
            
            $order->save();

            return response()->json(['success' => true, 'message' => "Status order berhasil diubah menjadi {$newStatus}", 'data' => $order]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengupdate status order', 'error' => $e->getMessage()], 500);
        }
    }
}