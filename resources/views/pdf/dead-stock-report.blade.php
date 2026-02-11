<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dead Stock Report</title>
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
            color: #7c3aed;
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
            background-color: #7c3aed;
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
        <h1>Dead Stock Report</h1>
        <p><strong>Branch:</strong> {{ $branchName }}</p>
        <p><strong>Period:</strong> {{ $startDate }} to {{ $endDate }}</p>
        <p><strong>Generated:</strong> {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}</p>
    </div>

    @if($data['products']->count() > 0)
        <div class="info-box">
            <p><strong>Total Products:</strong> {{ $data['summary']['total_products'] }}</p>
            <p><strong>Total Quantity:</strong> {{ $data['summary']['total_quantity'] }}</p>
            <p><strong>Total Buy Value:</strong> Tsh {{ number_format($data['summary']['total_buy_value'], 2) }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Buy Value</th>
                    <th>Unit</th>
                    <th>Branches</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['products'] as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $item['product_name'] }}</strong></td>
                        <td>{{ $item['category'] }}</td>
                        <td class="text-right">{{ $item['total_quantity'] }}</td>
                        <td class="text-right">Tsh {{ number_format($item['total_buy_value'], 2) }}</td>
                        <td>{{ $item['unit'] }}</td>
                        <td>{{ $item['branches'] ?: 'N/A' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>{{ $data['summary']['total_quantity'] }}</strong></td>
                    <td class="text-right"><strong>Tsh {{ number_format($data['summary']['total_buy_value'], 2) }}</strong></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="info-box">
            <p><strong>No dead stock found for the selected period.</strong></p>
        </div>
    @endif

    <div class="footer">
        <p>{{ $data['products']->count() }} products - Generated on {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}</p>
    </div>
</body>
</html>
