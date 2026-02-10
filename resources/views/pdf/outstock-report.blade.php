<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Out of Stock Report</title>
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
            color: #dc2626;
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
            background-color: #dc2626;
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
        .text-center {
            text-align: center;
        }
        .quantity-badge {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: bold;
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
        <h1>Out of Stock Report</h1>
        <p><strong>Branch:</strong> {{ $branchName }}</p>
        <p><strong>Generated:</strong> {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}</p>
    </div>

    @if($data['stocks']->count() > 0)
        <div class="info-box">
            <p><strong>Total Out of Stock Products:</strong> {{ $data['summary']['total_products'] }}</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Branch</th>
                    <th class="text-right">Buy Price</th>
                    <th class="text-right">Sell Price</th>
                    <th class="text-center">Quantity</th>
                    <th>Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['stocks'] as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $item['product_name'] }}</strong></td>
                        <td>{{ $item['category'] }}</td>
                        <td>{{ $item['branch_name'] }}</td>
                        <td class="text-right">Tsh {{ number_format($item['buy_price'], 2) }}</td>
                        <td class="text-right">Tsh {{ number_format($item['sell_price'], 2) }}</td>
                        <td class="text-center">
                            <span class="quantity-badge">{{ $item['quantity'] }}</span>
                        </td>
                        <td>{{ $item['unit'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="info-box">
            <p><strong>No products are currently out of stock.</strong></p>
        </div>
    @endif

    <div class="footer">
        <p>{{ $data['stocks']->count() }} out of stock products - Generated on {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}</p>
    </div>
</body>
</html>
