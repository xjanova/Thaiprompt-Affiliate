@extends('layouts.landing')

@section('title', 'แคมเปญดูฟรีรายเดือนยังไม่เปิด — แม่หมอจันทรา')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 flex items-center justify-center px-4 pt-16 lg:pt-20">
    <div class="max-w-md w-full bg-purple-900/40 backdrop-blur-xl rounded-3xl border border-purple-500/30 p-8 text-center">
        <div class="text-6xl mb-4">🌙</div>
        <h1 class="text-2xl font-bold text-white mb-3">
            แคมเปญยังไม่เปิดให้บริการ
        </h1>
        <p class="text-purple-200 mb-6">
            แม่หมอจันทรากำลังเตรียมความพร้อม โปรดติดตามในเร็วๆ นี้นะคะ
        </p>
        <a href="/" class="inline-block bg-purple-600 hover:bg-purple-500 text-white px-6 py-3 rounded-xl transition">
            กลับหน้าแรก
        </a>
    </div>
</div>
@endsection
