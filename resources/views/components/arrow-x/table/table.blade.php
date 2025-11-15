{{--
/**
 * Table V3 Component - ตารางข้อมูลแบบ V3 Design
 *
 * @props
 * @param array $headers คอลัมน์หัวตาราง
 * @param bool $hover เอฟเฟกต์ hover (default: true)
 * @param bool $striped แถบสลับสี (default: false)
 *
 * @slot default เนื้อหาตาราง (tbody)
 *
 * @example
 * <x-arrow-x.table.table :headers="['ชื่อ', 'อีเมล', 'สถานะ']">
 *     <tr>
 *         <td>John Doe</td>
 *         <td>john@example.com</td>
 *         <td>Active</td>
 *     </tr>
 * </x-arrow-x.table.table>
 *
 * @tip รองรับ dark mode และ responsive
 */
--}}

@props([
    'headers' => [],
    'hover' => true,
    'striped' => false,
])

<table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-white/20']) }}>
    {{-- Table Header --}}
    @if(count($headers) > 0)
        <thead class="bg-white/10 backdrop-blur-sm">
            <tr>
                @foreach($headers as $header)
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-white/90 uppercase tracking-wider drop-shadow">
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
        </thead>
    @endif

    {{-- Table Body --}}
    <tbody class="divide-y divide-white/10 {{ $striped ? 'divide-y divide-white/10' : '' }}">
        {{ $slot }}
    </tbody>
</table>

<style>
/**
 * Table Row Hover Effect
 */
@if($hover)
tbody tr {
    transition: all 0.2s ease;
}

tbody tr:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.01);
}
@endif

/**
 * Striped Rows
 */
@if($striped)
tbody tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.05);
}
@endif

/**
 * Table Cells
 */
tbody td {
    padding: 1rem 1.5rem;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.95);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}
</style>
