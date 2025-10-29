@extends('layouts.admin')

@section('title', 'จัดการ SEO')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">จัดการ SEO Meta Tags</h2>
                    <a href="{{ route('admin.seo.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>เพิ่ม SEO Meta
                    </a>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หน้า</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ภาษา</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($seoMetas as $seo)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $seo->page_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                            {{ $seo->language }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $seo->meta_title }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 truncate max-w-xs">{{ $seo->meta_description }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($seo->index && $seo->follow)
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Index, Follow
                                            </span>
                                        @else
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                {{ $seo->robots }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.seo.edit', $seo) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </a>
                                        <form action="{{ route('admin.seo.destroy', $seo) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('ยืนยันการลบ?')">
                                                <i class="fas fa-trash"></i> ลบ
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        ยังไม่มีข้อมูล SEO Meta
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($seoMetas->hasPages())
                    <div class="mt-6">
                        {{ $seoMetas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
