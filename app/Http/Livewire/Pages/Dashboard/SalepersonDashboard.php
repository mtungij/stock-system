<?php

namespace App\Http\Livewire\Pages\Dashboard;

use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalepersonDashboard extends Component
{
    public $salesToday;
    public $topProducts;
    public $stockMovement;

    public function mount()
    {
        $user = Auth::user();
        $today = now()->toDateString();

        // Get today's sales for this salesperson
        $this->salesToday = Sale::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->sum('total');

        // Get top products sold today by this salesperson
        $this->topProducts = SaleItem::whereHas('sale', function ($query) use ($user, $today) {
                $query->where('user_id', $user->id)
                    ->whereDate('created_at', $today);
            })
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->with('product')
            ->take(5)
            ->get();

        // Get stock movement for this salesperson today
        $this->stockMovement = Stock::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->get();
    }

    public function render()
    {
        return view('pages.dashboard.saleperson-dashboard');
    }
}
