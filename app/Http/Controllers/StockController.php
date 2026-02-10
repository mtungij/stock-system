<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function showPdf(Request $request)
    {
        $user = Auth::user();
        $filterBranch = $request->get('branch');
        
        $query = Stock::with(['product.category', 'branch']);

        if ($user && $user->branch) {
            $companyId = $user->branch->company_id;
            $query->whereHas('branch', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        // Filter by branch if selected
        if ($filterBranch) {
            $query->where('branch_id', $filterBranch);
        }

        $stocks = $query->latest()->get();
        $branchName = $filterBranch ? Branch::find($filterBranch)->name : 'All Branches';
        $company_name = $user->branch ? $user->branch->company->name : 'Company';

        return view('exports.stocks-pdf', compact('stocks', 'branchName', 'company_name'));
    }

    public function downloadPdf(Request $request)
    {
        $user = Auth::user();
        $filterBranch = $request->get('branch');
        
        $query = Stock::with(['product.category', 'branch']);

        if ($user && $user->branch) {
            $companyId = $user->branch->company_id;
            $query->whereHas('branch', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        // Filter by branch if selected
        if ($filterBranch) {
            $query->where('branch_id', $filterBranch);
        }

        $stocks = $query->latest()->get();
        $branchName = $filterBranch ? Branch::find($filterBranch)->name : 'All Branches';
        $company_name = $user->branch ? $user->branch->company->name : 'Company';


        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $html = view('exports.stocks-pdf', compact('stocks', 'branchName', 'company_name'))->render();
        
        $mpdf->WriteHTML($html);
        
        return response()->streamDownload(function() use ($mpdf) {
            echo $mpdf->Output('', 'S');
        }, 'stocks-' . date('Y-m-d') . '.pdf');
    }
}
