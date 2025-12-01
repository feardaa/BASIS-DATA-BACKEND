<!DOCTYPE html>
<html>

<head>
    <title>Orders Management - Laundry App</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #34495e;
            color: white;
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

        .dibatalkan {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>📦 Orders Management</h1>
        <a href="/admin" style="color: white; text-decoration: none;">← Back to Dashboard</a>
    </div>

    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Completed Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>#{{ $order->id_order }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->driver_name ?? 'Not Assigned' }}</td>
                        <td>
                            <span class="status {{ $order->status }}">{{ $order->status }}</span>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($order->tanggal_pesan)->format('M d, Y H:i') }}</td>
                        <td>{{ $order->tanggal_selesai ? \Carbon\Carbon::parse($order->tanggal_selesai)->format('M d, Y H:i') : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>