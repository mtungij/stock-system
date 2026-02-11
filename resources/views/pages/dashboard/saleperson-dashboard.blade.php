{{-- Livewire component view: expects $salesToday, $topProducts, $stockMovement --}}

<div class="w-full px-2 py-4 max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Card: Sales Total Today -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700 flex flex-col items-center">
        <div class="flex items-center mb-2">
            <svg class="w-8 h-8 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
            </svg>
            <span class="text-lg font-semibold">Sales Today</span>
        </div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white">Tsh {{ number_format($salesToday, 0) }}</div>
    </div>

    <!-- Card: Top Selling Products Today -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700 flex flex-col">
        <div class="flex items-center mb-2">
            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
            </svg>
            <span class="text-lg font-semibold">Top Products</span>
        </div>
        <ul class="mt-2">
            @forelse($topProducts as $item)
                <li class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-700 last:border-none">
                    <span class="font-medium text-gray-900 dark:text-white">{{ $item->product->name ?? 'N/A' }}</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $item->total_quantity }} sold</span>
                </li>
            @empty
                <li class="text-gray-500 dark:text-gray-400">No sales yet today.</li>
            @endforelse
        </ul>
    </div>

    <!-- Card: Stock Movement Graph Today -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700 flex flex-col">
        <div class="flex items-center mb-2">
            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
            </svg>
            <span class="text-lg font-semibold">Stock Movement</span>
        </div>
        <div class="mt-2">
            <canvas id="stockMovementChart" height="120"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('stockMovementChart');
    if (ctx) {
        const hours = {!! json_encode($stockMovement->pluck('hour')) !!};
        const totals = {!! json_encode($stockMovement->pluck('total')) !!};
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: hours,
                datasets: [{
                    label: 'Sales (Tsh)',
                    data: totals,
                    backgroundColor: 'rgba(99,102,241,0.7)',
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: { title: { display: true, text: 'Hour' } },
                    y: { title: { display: true, text: 'Sales' } }
                }
            }
        });
    }
});
</script>
