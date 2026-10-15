<x-admin-layout>
    <h1 class="text-xl font-semibold mb-5">Municipal dashboard</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-5 gap-3 mb-6">
        <x-stat-card label="Registered users" :value="number_format($stats['users'])" :sub="$stats['active_users'].' active'" />
        <x-stat-card label="Properties" :value="number_format($stats['properties'])" />
        <x-stat-card label="Accounts" :value="number_format($stats['accounts'])" />
        <x-stat-card label="Bills issued" :value="number_format($stats['bills_issued'])" />
        <x-stat-card label="Amount billed" :value="money($stats['amount_billed'])" />
        <x-stat-card label="Collected" :value="money($stats['amount_collected'])" tone="success" />
        <x-stat-card label="Outstanding" :value="money($stats['outstanding'])" :tone="$stats['outstanding'] > 0 ? 'warn' : 'default'" />
        <x-stat-card label="Arrears" :value="money($stats['arrears'])" :tone="$stats['arrears'] > 0 ? 'danger' : 'default'" />
        <x-stat-card label="Payments today" :value="$stats['payments_today']" />
        <x-stat-card label="Failed payments" :value="$stats['failed_payments']" :tone="$stats['failed_payments'] > 0 ? 'warn' : 'default'" />
        <x-stat-card label="Unallocated" :value="$stats['unallocated']" :tone="$stats['unallocated'] > 0 ? 'warn' : 'default'" />
        <x-stat-card label="Open requests" :value="$stats['open_requests']" />
        <x-stat-card label="Active interruptions" :value="$stats['active_interruptions']" />
        <x-stat-card label="Disconnected" :value="$stats['disconnections_active']" />
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Collections — last 14 days</h2>
            <canvas id="collectionsChart" height="160"></canvas>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Billing — last 6 months</h2>
            <canvas id="billingChart" height="160"></canvas>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Collections by channel</h2>
            <canvas id="channelsChart" height="160"></canvas>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-semibold text-sm mb-3">Service requests by status</h2>
            <canvas id="requestsChart" height="160"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const money = v => '$' + (v / 100).toFixed(2);
            new Chart(collectionsChart, {type: 'line', data: {labels: @json($collections->keys()), datasets: [{label: 'Collected', data: @json($collections->values()), borderColor: '#143E62', tension: .3, fill: true, backgroundColor: 'rgba(20,62,98,.08)'}]}, options: {plugins: {legend: {display: false}}, scales: {y: {ticks: {callback: money}}}}});
            new Chart(billingChart, {type: 'bar', data: {labels: @json($billing->keys()), datasets: [{label: 'Billed', data: @json($billing->values()), backgroundColor: '#143E62'}]}, options: {plugins: {legend: {display: false}}, scales: {y: {ticks: {callback: money}}}}});
            new Chart(channelsChart, {type: 'doughnut', data: {labels: @json($channels->keys()), datasets: [{data: @json($channels->values()), backgroundColor: ['#143E62','#d97706','#475569','#0284c7','#9333ea']} ]}, options: {plugins: {tooltip: {callbacks: {label: c => ' ' + c.label + ': ' + money(c.parsed)}}}}});
            new Chart(requestsChart, {type: 'bar', data: {labels: @json($requestsByStatus->keys()), datasets: [{data: @json($requestsByStatus->values()), backgroundColor: '#d97706'}]}, options: {indexAxis: 'y', plugins: {legend: {display: false}}}});
        });
    </script>
</x-admin-layout>
