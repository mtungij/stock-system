<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a56db;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-box {
            background: #f3f4f6;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #1a56db;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #e5e7eb !important;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sales Report</h1>
        <p><strong>Branch:</strong> {{ $branchName }}</p>
        <p><strong>Generated:</strong> {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}</p>
    </div>

    @if($data['transactions']->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Subtotal</th>
                    <th>Seller</th>
                    <th>Branch</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalAmount = 0;
                    $totalQuantity = 0;
                @endphp
                @foreach($data['transactions'] as $index => $item)
                    @php
                        $totalAmount += $item['subtotal'];
                        $totalQuantity += $item['quantity'];
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['product_name'] }}</td>
                        <td class="text-right">{{ $item['quantity'] }}</td>
                        <td class="text-right">Tsh {{ number_format($item['price'], 2) }}</td>
                        <td class="text-right">Tsh {{ number_format($item['subtotal'], 2) }}</td>
                        <td>{{ $item['seller'] }}</td>
                        <td>{{ $item['branch_name'] }}</td>
                        <td>{{ $item['date'] }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>{{ $totalQuantity }}</strong></td>
                    <td></td>
                    <td class="text-right"><strong>Tsh {{ number_format($totalAmount, 2) }}</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="info-box">
            <p><strong>No sales data available for the selected period.</strong></p>
        </div>
    @endif

    <div class="footer">
        <p>{{ $data['transactions']->count() }} items - Generated on {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}</p>
    </div>
</body>
</html>
