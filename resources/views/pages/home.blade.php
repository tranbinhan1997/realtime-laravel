@extends('layouts.app')

@section('title', 'Bảng tin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
<div class="row vh-100">
    @include('partials.sidebar-left')
    <div class="col-md-8 p-4 overflow-auto">
        <div class="card mb-3">
            <div class="card-body">
                <input type="text" class="form-control input-post" placeholder="Bạn đang nghĩ gì thế?" readonly onclick="openPostModal()">
            </div>
        </div>
        <div id="feed"></div>
        <div id="loading" class="post-skeleton d-none">
            <div class="skeleton-header"></div>
            <div class="skeleton-body"></div>
        </div>
    </div>

    @include('partials.sidebar-right')
</div>

<div class="modal fade" id="postModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo bài viết</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <button class="btn btn-light border" onclick="chooseImage()">📷</button>
                    <button class="btn btn-light border" onclick="chooseVideo()">🎥</button>
                    <div class="emoji-wrapper position-relative">
                        <button type="button" class="btn btn-light border" onclick="toggleEmoji(event)">😊</button>
                        <div id="emojiPicker" class="emoji-picker d-none"></div>
                    </div>
                    <input type="file" id="imageInput" accept="image/*" multiple hidden onchange="uploadImage(this)">
                    <input type="file" id="videoInput" accept="video/*" hidden onchange="uploadVideo(this)">
                </div>
                <textarea id="postContent"></textarea>
                <div id="imagePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                <div id="videoPreview" class="d-none mt-2"></div>
                <div id="linkPreview" class="border rounded p-2 mt-2 d-none"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button class="btn btn-primary" id="postSubmitBtn" onclick="submitPost()">Đăng</button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
    <script src="{{ asset('js/home.js') }}"></script>
@endpush
