<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Branch Performance Report</title>
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
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
        }
        .summary-card {
            flex: 1;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px;
            text-align: center;
            border-radius: 4px;
        }
        .summary-card .label {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .summary-card .value {
            font-size: 14px;
            font-weight: bold;
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
        .text-green {
            color: #059669;
        }
        .text-orange {
            color: #ea580c;
        }
        .text-blue {
            color: #2563eb;
        }
        .text-indigo {
            color: #4f46e5;
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
        <h1>Branch Performance Report</h1>
        <p><strong>Period:</strong> {{ $startDate }} - {{ $endDate }}</p>
        <p><strong>Generated:</strong> {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="summary">
        <div class="summary-card">
            <div class="label">Total Sales</div>
            <div class="value text-green">Tsh {{ number_format($data['totals']['total_sales'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Purchases</div>
            <div class="value text-orange">Tsh {{ number_format($data['totals']['total_purchases'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Profit</div>
            <div class="value text-blue">Tsh {{ number_format($data['totals']['profit'], 2) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Products Sold</div>
            <div class="value">{{ number_format($data['totals']['products_sold'], 0) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Stock Value</div>
            <div class="value text-indigo">Tsh {{ number_format($data['totals']['current_stock_value'], 2) }}</div>
        </div>
    </div>

    @if($data['branches']->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Branch</th>
                    <th class="text-right">Total Sales</th>
                    <th class="text-right">Total Purchases</th>
                    <th class="text-right">Profit</th>
                    <th class="text-right">Products Sold</th>
                    <th class="text-right">Current Stock Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['branches'] as $branch)
                    <tr>
                        <td><strong>{{ $branch['branch_name'] }}</strong></td>
                        <td class="text-right text-green">Tsh {{ number_format($branch['total_sales'], 2) }}</td>
                        <td class="text-right text-orange">Tsh {{ number_format($branch['total_purchases'], 2) }}</td>
                        <td class="text-right text-blue">Tsh {{ number_format($branch['profit'], 2) }}</td>
                        <td class="text-right">{{ number_format($branch['products_sold'], 0) }}</td>
                        <td class="text-right text-indigo">Tsh {{ number_format($branch['current_stock_value'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>TOTAL</strong></td>
                    <td class="text-right text-green"><strong>Tsh {{ number_format($data['totals']['total_sales'], 2) }}</strong></td>
                    <td class="text-right text-orange"><strong>Tsh {{ number_format($data['totals']['total_purchases'], 2) }}</strong></td>
                    <td class="text-right text-blue"><strong>Tsh {{ number_format($data['totals']['profit'], 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($data['totals']['products_sold'], 0) }}</strong></td>
                    <td class="text-right text-indigo"><strong>Tsh {{ number_format($data['totals']['current_stock_value'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="info-box">
            <p><strong>No branch data available for the selected period.</strong></p>
        </div>
    @endif

    <div class="footer">
        <p>{{ $data['branches']->count() }} branches - Generated on {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}</p>
    </div>
</body>
</html>
