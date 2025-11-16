{{--
/**
 * Line Chart Component - ApexCharts Area/Line Chart พร้อม Glass Effect
 *
 * @props
 * @param string $id HTML ID สำหรับ chart container (required, unique)
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
 * <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
 * <script>
 * const options = {
 *     series: [{
 *         name: 'ยอดขาย',
 *         data: @json($data)
 *     }],
 *     chart: {
 *         type: 'area',
 *         height: 350
 *     },
 *     xaxis: {
 *         categories: @json($labels)
 *     }
 * };
 * const chart = new ApexCharts(document.querySelector('#sales-chart'), options);
 * chart.render();
 * </script>
 * @endsection
 *
 * @tip ต้อง include ApexCharts library ใน scripts section
 * @tip รองรับ responsive แบบ automatic
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

    {{-- Chart Container สำหรับ ApexCharts --}}
    <div class="p-6" style="min-height: {{ $height + 48 }}px;">
        <div id="{{ $id }}" style="width: 100%;"></div>
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
