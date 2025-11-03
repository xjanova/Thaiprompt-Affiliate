@extends('layouts.admin')
@section('title', 'แก้ไข ' . $bot->display_name)
@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header"><h5>แก้ไข AI Bot</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.ai-bots.update', $bot->id) }}">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>ชื่อ Bot</label>
                        <input type="text" name="name" class="form-control" value="{{ $bot->name }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>ชื่อแสดง</label>
                        <input type="text" name="display_name" class="form-control" value="{{ $bot->display_name }}" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label>System Prompt</label>
                        <textarea name="system_prompt" class="form-control" rows="4">{{ $bot->system_prompt }}</textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Temperature</label>
                        <input type="number" name="temperature" class="form-control" step="0.1" value="{{ $bot->temperature }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Max Tokens</label>
                        <input type="number" name="max_tokens" class="form-control" value="{{ $bot->max_tokens }}">
                    </div>
                    <div class="col-12 mt-3 mb-3">
                        <hr>
                        <h6>เชื่อมต่อกับ LINE OA (ถ้ามี)</h6>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>LINE Channel ID</label>
                        <input type="text" name="line_oa_channel_id" class="form-control" value="{{ $bot->line_oa_channel_id }}" placeholder="ใส่ Channel ID ของ LINE OA">
                        <small class="text-muted">ใส่ Channel ID เพื่อเชื่อมต่อ Bot นี้กับ LINE OA</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>LINE Channel Secret</label>
                        <input type="text" name="line_oa_channel_secret" class="form-control" value="{{ $bot->line_oa_channel_secret }}" placeholder="Channel Secret">
                    </div>
                    <div class="col-12 mb-3">
                        <label>LINE Channel Access Token</label>
                        <input type="text" name="line_oa_access_token" class="form-control" value="{{ $bot->line_oa_access_token }}" placeholder="Long-lived Channel Access Token">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">บันทึก</button>
                <a href="{{ route('admin.ai-bots.show', $bot->id) }}" class="btn btn-secondary">ยกเลิก</a>
            </form>
        </div>
    </div>
</div>
@endsection
