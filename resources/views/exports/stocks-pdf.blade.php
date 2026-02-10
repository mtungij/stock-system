<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock Report - {{ $branchName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            padding: 20px;
            background: linear-gradient(135deg, #cffafe 0%, #e0f2fe 100%);
            color: #164e63;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(6, 182, 212, 0.2);
            border-radius: 16px;
            border: 2px solid #67e8f9;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 4px solid #06b6d4;
            background: linear-gradient(135deg, #ecfeff 0%, #f0fdfa 100%);
            padding: 30px;
            border-radius: 12px;
        }
        
        .header h1 {
            color: #0891b2;
            font-size: 38px;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header .info {
            color: #155e75;
            font-size: 15px;
            margin-top: 15px;
            line-height: 1.8;
            font-weight: 500;
        }
        
        .header .info strong {
            color: #0e7490;
            font-weight: 600;
        }
        
        .actions {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .btn-download {
            display: inline-block;
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        .btn-download:hover {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.1);
        }
        
        th {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #cffafe;
            font-size: 14px;
            color: #164e63;
        }
        
        tr:nth-child(even) {
            background-color: #ecfeff;
        }
        
        tr:hover {
            background-color: #cffafe;
            transition: background-color 0.2s ease;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
            font-weight: 500;
        }
        
        .text-uppercase {
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            padding-top: 25px;
            border-top: 3px solid #67e8f9;
            color: #0e7490;
            font-size: 14px;
            background: linear-gradient(135deg, #ecfeff 0%, #f0fdfa 100%);
            padding: 20px;
            border-radius: 8px;
        }
        
        .footer strong {
            color: #0891b2;
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #22d3ee;
            font-style: italic;
            font-size: 16px;
        }
        
        .totals-row {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white;
            font-weight: 600;
            font-size: 15px;
        }
        
        .totals-row td {
            padding: 16px 12px;
            border-bottom: none;
            color: white;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                padding: 20px;
                border: none;
            }
            
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Stock Report</h1>
            <div class="info">
                <strong></strong> {{ $company_name }}<br>
                <strong>Branch:</strong> {{ $branchName }}<br>
                <strong>Generated:</strong> {{ date('F d, Y H:i:s') }}
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('stock.pdf.download', ['branch' => request('branch')]) }}" class="btn-download">
                📥 Download PDF
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Branch</th>
                    <th class="text-right">Buy Price</th>
                    <th class="text-right">Sell Price</th>
                    <th class="text-right">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalQuantity = 0;
                    $totalBuyValue = 0;
                    $totalSellValue = 0;
                @endphp
                @forelse($stocks as $index => $stock)
                @php
                    $totalQuantity += $stock->quantity;
                    $totalBuyValue += $stock->buy_price * $stock->quantity;
                    $totalSellValue += $stock->sell_price * $stock->quantity;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-uppercase">{{ $stock->product->name }}</td>
                    <td class="text-uppercase">{{ $stock->product->category->name }}</td>
                    <td class="text-uppercase">{{ $stock->branch->name }}</td>
                    <td class="text-right">{{ number_format($stock->buy_price, 2) }}</td>
                    <td class="text-right">{{ number_format($stock->sell_price, 2) }}</td>
                    <td class="text-right">{{ number_format($stock->quantity) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="no-data">No stock records found</td>
                </tr>
                @endforelse
                
                @if(count($stocks) > 0)
                <tr class="totals-row">
                    <td colspan="4" style="text-align: right; text-transform: uppercase; letter-spacing: 1px;">TOTALS:</td>
                    <td class="text-right">{{ number_format($totalBuyValue, 2) }}</td>
                    <td class="text-right">{{ number_format($totalSellValue, 2) }}</td>
                    <td class="text-right">{{ number_format($totalQuantity) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="footer">
            <p><strong>Total Records:</strong> {{ count($stocks) }}</p>
        </div>
    </div>
</body>
</html>
