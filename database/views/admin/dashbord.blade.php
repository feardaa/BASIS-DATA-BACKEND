<!DOCTYPE html>
<html>

<head>
    <title>Admin Dashboard - Laundry App</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f5f5f5;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
        }

        .recent-orders {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f8f9fa;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }

        .menunggu {
            background: #fff3cd;
            color: #856404;
        }

        .diproses {
            background: #cce7ff;
            color: #004085;
        }

        .selesai {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🏪 Laundry App - Admin Dashboard</h1>
    </div>

    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number">{{ $stats['total_orders'] }}</div>
                <div>Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['total_users'] }}</div>
                <div>Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['pending_orders'] }}</div>
                <div>Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['completed_orders'] }}</div>
                <div>Completed Orders</div>
            </div>
        </div>

        <div class="recent-orders">
            <h2>Recent Orders</h2>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Order Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recent_orders as $order)
                        <tr>
                            <td>#{{ $order->id_order }}</td>
                            <td>{{ $order->customer_name }}</td>
                            <td>
                                <span class="status {{ $order->status }}">{{ $order->status }}</span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($order->tanggal_pesan)->format('M d, Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>