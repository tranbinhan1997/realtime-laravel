@extends('layouts.app')

@section('title', 'Thông tin cá nhân')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-md-2"></div>
    <div class="col-md-8">
        <div class="profile-cover">
            <img src="https://hoanghamobile.com/tin-tuc/wp-content/uploads/2024/11/tai-hinh-nen-dep-mien-phi.jpg" class="cover-img">
            <div class="profile-info">
                <img src="https://cdn2.vectorstock.com/i/1000x1000/23/81/default-avatar-profile-icon-vector-18942381.jpg" class="avatar-img">
                <div class="ms-3">
                    <h4 class="mb-0">Trần Bình An</h4>
                    <small class="text-muted">LARA JS</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2"></div>
</div>

<div class="row profile-offset vh-100">
    <div class="col-md-2 p-4"></div>
    <div class="col-md-2 border-start bg-white p-3">
        <h6 class="fw-bold">Thông tin cá nhận</h6>
        <ul class="list-unstyled"></ul>
    </div>
    <div class="col-md-6 p-4 overflow-auto">
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
    <div class="col-md-4 p-4"></div>
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

<div id="imageViewer" class="image-viewer d-none">
    <span class="close-btn" onclick="closeImageViewer()">×</span>

    <span class="nav prev" onclick="prevImage()">❮</span>
    <img id="viewerImage" src="">
    <span class="nav next" onclick="nextImage()">❯</span>
</div>

@endsection
@push('scripts')
    <script src="{{ asset('js/app.js') }}"></script>
@endpush
