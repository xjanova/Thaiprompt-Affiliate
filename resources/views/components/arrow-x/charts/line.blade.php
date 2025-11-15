{{--
/**
 * Line Chart Component - Chart.js Line Chart พร้อม Glass Effect
 *
 * @props
 * @param string $id HTML ID สำหรับ canvas element (required, unique)
 * @param string $title หัวข้อของ chart (default: "Chart")
 * @param string $icon Font Awesome icon class (default: "fas fa-chart-line")
 * @param string $gradient Tailwind gradient classes (default: "from-blue-500 to-purple-600")
 * @param int $height ความสูงของ chart ใน pixels (default: 300)
 *
 * @example พื้นฐาน
 * <x-arrow-x.charts.line id="revenue-chart" title="สถิติรายได้" />
 *
 * @example ใช้ใน view พร้อม data
 * <x-arrow-x.charts.line
 *     id="sales-chart"
 *     title="ยอดขายรายเดือน"
 *     icon="fas fa-shopping-cart"
 *     gradient="from-green-500 to-emerald-600"
 *     :height="400"
 * />
 *
 * @section('scripts')
 * <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
 * <script>
 * const ctx = document.getElementById('sales-chart').getContext('2d');
 * new Chart(ctx, {
 *     type: 'line',
 *     data: {
 *         labels: @json($labels),
 *         datasets: [{
 *             label: 'ยอดขาย',
 *             data: @json($data),
 *             borderColor: 'rgb(59, 130, 246)',
 *             backgroundColor: 'rgba(59, 130, 246, 0.1)',
 *             tension: 0.4,
 *             fill: true
 *         }]
 *     }
 * });
 * </script>
 * @endsection
 *
 * @tip ต้อง include Chart.js library ใน scripts section
 * @tip ใช้ gradient ต่างๆ เพื่อแยกประเภท chart
 */
--}}

@props([
    'id',
    'title' => 'Chart',
    'icon' => 'fas fa-chart-line',
    'gradient' => 'from-blue-500 to-purple-600',
    'height' => 300,
])

<div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-white/30 bg-gradient-to-r {{ $gradient }}/20">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br {{ $gradient }} rounded-lg flex items-center justify-center shadow-lg">
                <i class="{{ $icon }} text-white drop-shadow"></i>
            </div>
            <h3 class="text-xl font-bold text-white drop-shadow">{{ $title }}</h3>
        </div>
    </div>

    {{-- Chart Canvas --}}
    <div class="p-6">
        <canvas id="{{ $id }}" style="height: {{ $height }}px; max-height: {{ $height }}px;"></canvas>
    </div>
</div>

<style>
/**
 * Glass Fusion Effect - ความโปร่งใสพร้อม backdrop blur
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
</style>
