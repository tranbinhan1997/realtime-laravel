let editor;
let postModal;
let linkPreview;
let lastPreviewUrl;
let uploadedVideo;
let uploadedImages = [];
let emojiPickerVisible = false;
let editingPostId = null;


fetch("/api/posts", {
    headers: {
        Authorization: "Bearer " + token,
        Accept: "application/json"
    }
})
.then(r => r.json())
.then(posts => {
    posts.forEach(addPost);
});

document.addEventListener("DOMContentLoaded", () => {
    postModal = new bootstrap.Modal(
        document.getElementById("postModal")
    );

    ClassicEditor.create(document.querySelector('#postContent'), {
        placeholder: 'Bạn đang nghĩ gì thế?',
        toolbar: [
            'bold', 'italic', 'link',
            'bulletedList', 'numberedList',
            'blockQuote', 'undo', 'redo'
        ]
    })
        .then(e => {
            editor = e;
            editor.model.document.on('change:data', () => {
                const text = editor.getData().replace(/<[^>]+>/g, '');
                const urlRegex = /(https?:\/\/[^\s]+)/;
                const match = text.match(urlRegex);

                if (!match) {
                    clearLinkPreview();
                    return;
                }

                const url = match[1];

                if (url === lastPreviewUrl) return;

                lastPreviewUrl = url;

                fetch('/api/preview-link', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Authorization: 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        url: match[0]
                    })
                })
                    .then(r => r.json())
                    .then(data => {
                        linkPreview = data;
                        renderLinkPreview(data);
                    })
                    .catch(() => {
                        clearLinkPreview();
                    });
            });
        })
        .catch(console.error);

    const picker = new EmojiMart.Picker({
        onEmojiSelect: (emoji) => {
            insertEmoji(emoji.native);
        },
        theme: 'light',
        previewPosition: 'none',
        skinTonePosition: 'none'
    });

    document.getElementById('emojiPicker').appendChild(picker);

});

document.addEventListener('click', (e) => {
    const picker = document.getElementById('emojiPicker');
    const emojiBtn = e.target.closest('button');

    if (!picker || picker.classList.contains('d-none')) return;

    if (picker.contains(e.target) || emojiBtn?.innerText === '😊') return;

    picker.classList.add('d-none');
    emojiPickerVisible = false;
});

// Hàm thêm bài viết vào feed
function addPost(post) {
    const isOwner = post.author_id === currentUserId;

    feed.innerHTML =
        `<div class="card mb-3" id="post-${post.id}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${post.user}</strong>
                                <small class="text-muted"> · ${post.time}</small>
                            </div>

                            ${isOwner ? `
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light"
                                        data-bs-toggle="dropdown">⋯</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button class="dropdown-item"
                                                onclick="openEditPost(${post.id})">
                                                Chỉnh sửa
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger"
                                                onclick="deletePost(${post.id})">
                                                Xóa bài viết
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                        <div class="mt-2 post-content">${post.content}</div>
                        ${renderImages(post.images)}
                        ${renderLink(post.link)}
                        ${renderVideo(post.video)}
                    </div>
                </div>` + feed.innerHTML;
}

// Hàm lưu bài viết
async function submitPost() {
    const content = editor.getData().trim();
    if (!content && !uploadedImages.length && !uploadedVideo) return;

    const payload = {
        content,
        images: uploadedImages.map(i => i.path),
        link: linkPreview,
        video: uploadedVideo
    };

    const url = editingPostId ?
        `/api/posts/${editingPostId}` :
        `/api/posts`;

    const method = editingPostId ? 'PUT' : 'POST';

    const res = await fetch(url, {
        method,
        headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer " + token,
            Accept: "application/json"
        },
        body: JSON.stringify(payload)
    });

    const post = await res.json();

    document.getElementById(`post-${post.id}`)?.remove();
    addPost(post);
    resetPostModal();
    postModal.hide();
}

// Hàm xóa bài viết
async function deletePost(id) {
    const result = await Swal.fire({
        title: 'Xóa bài viết?',
        text: 'Bạn sẽ không thể khôi phục lại bài viết này.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    });

    if (!result.isConfirmed) return;

    await fetch(`/api/posts/${id}`, {
        method: 'DELETE',
        headers: {
            Authorization: 'Bearer ' + token
        }
    });

    Swal.fire({
        icon: 'success',
        title: 'Đã xóa',
        timer: 1200,
        showConfirmButton: false
    });
}

// hàm load data edit
function openEditPost(id) {
    const postEl = document.getElementById(`post-${id}`);
    if (!postEl) return;

    editingPostId = id;

    document.querySelector('#postModal .modal-title').innerText = 'Chỉnh sửa bài viết';
    document.getElementById('postSubmitBtn').innerText = 'Lưu';

    const content = postEl.querySelector('.post-content').innerHTML;
    editor.setData(content);

    uploadedImages = [];
    uploadedVideo = null;
    clearLinkPreview();

    postEl.querySelectorAll('.post-images img').forEach(img => {
        uploadedImages.push({
            url: img.src,
            path: img.src.replace(
                window.location.origin + '/storage/',
                ''
            )
        });
    });

    const videoEl = postEl.querySelector('.post-video video');

    if (videoEl) {
        uploadedVideo = {
            url: videoEl.querySelector('source').src,
            path: videoEl.querySelector('source').src.replace(
                window.location.origin + '/storage/',
                ''
            )
        };

        const box = document.getElementById('videoPreview');
        box.classList.remove('d-none');
        box.innerHTML = `
                    <video controls class="w-100 rounded">
                        <source src="${uploadedVideo.url}">
                    </video>
                `;
    } else {
        uploadedVideo = null;
        document.getElementById('videoPreview').classList.add('d-none');
    }


    renderImagePreview();
    postModal.show();
}

// Hàm reset modal
function resetPostModal() {
    editingPostId = null;

    editor.setData('');
    uploadedImages = [];
    uploadedVideo = null;
    clearLinkPreview();

    document.getElementById('imagePreview').innerHTML = '';
    document.getElementById('videoPreview').classList.add('d-none');

    document.querySelector('#postModal .modal-title').innerText = 'Tạo bài viết';
    document.getElementById('postSubmitBtn').innerText = 'Đăng';
}

// Hàm mở modal tạo bài viết và chỉnh sửa
function openPostModal() {
    editingPostId = null;

    editor.setData('');
    uploadedImages = [];
    uploadedVideo = null;
    clearLinkPreview();

    document.getElementById('imagePreview').innerHTML = '';
    document.getElementById('videoPreview').classList.add('d-none');

    document.querySelector('#postModal .modal-title').innerText = 'Tạo bài viết';
    document.getElementById('postSubmitBtn').innerText = 'Đăng';

    postModal.show();
    setTimeout(() => editor?.editing.view.focus(), 300);
}

// Hàm chọn ảnh từ máy tính
function chooseImage() {
    document.getElementById('imageInput').click();
}

// Hàm chọn video từ máy tính
function chooseVideo() {
    document.getElementById('videoInput').click();
}

// Hàm upload ảnh lên server
async function uploadImage(input) {
    const files = [...input.files];
    if (!files.length) return;

    for (const file of files) {
        const form = new FormData();
        form.append('upload', file);

        const res = await fetch('/api/upload-image', {
            method: 'POST',
            headers: {
                Authorization: 'Bearer ' + token
            },
            body: form
        });

        const data = await res.json();

        uploadedImages.push(data);
    }

    renderImagePreview();
    input.value = '';
}

// Hàm render preview ảnh trên modal tạo bài viết
function renderImagePreview() {
    const box = document.getElementById('imagePreview');
    box.innerHTML = '';

    uploadedImages.forEach((img, index) => {
        box.innerHTML += `
                    <div class="position-relative">
                        <img src="${img.url}"
                            style="width:120px;height:120px;object-fit:cover"
                            class="rounded">

                        <button class="btn btn-sm btn-danger position-absolute top-0 end-0"
                                onclick="removeImage(${index})">×</button>
                    </div>
                `;
    });
}

// Hàm Xóa preview ảnh trên modal tạo bài viết
function removeImage(index) {
    uploadedImages.splice(index, 1);
    renderImagePreview();
}

// Hàm upload video lên server
async function uploadVideo(input) {
    const file = input.files[0];
    if (!file) return;

    const form = new FormData();
    form.append('video', file);

    const res = await fetch('/api/upload-video', {
        method: 'POST',
        headers: {
            Authorization: 'Bearer ' + token
        },
        body: form
    });

    const data = await res.json();
    uploadedVideo = data;

    const box = document.getElementById('videoPreview');
    box.classList.remove('d-none');
    box.innerHTML = `
        <video controls class="w-100 rounded">
            <source src="${data.url}">
        </video>
        `;

    input.value = '';
}

// Hàm render preview link trên modal tạo bài viết
function renderLinkPreview(data) {
    const box = document.getElementById('linkPreview');
    box.classList.remove('d-none');

    box.innerHTML = `
        <div class="d-flex gap-2">
            ${data.image ? `<img src="${data.image}" style="width:120px;object-fit:cover">` : ''}
            <div>
                <div class="fw-bold">${data.title}</div>
                <div class="text-muted small">${data.desc}</div>
                <div class="text-secondary small">${new URL(data.url).hostname}</div>
            </div>
        </div>
        `;
}

// Hàm render link trong bài viết
function renderLink(link) {
    if (!link || !link.url) return '';
    return `
        <div class="border rounded mt-2 overflow-hidden link-preview"
            onclick="window.open('${link.url}', '_blank')"
            style="cursor:pointer">

            ${link.image ? `
                        <img src="${link.image}"
                            class="w-100"
                            style="max-height:300px;object-fit:cover">
                    ` : ''}

            <div class="p-2 bg-white">
                <div class="fw-bold">${link.title ?? ''}</div>
                <div class="text-muted small">${link.desc ?? ''}</div>
                <div class="text-secondary small">
                    ${new URL(link.url).hostname}
                </div>
            </div>
        </div>
        `;
}

// Hàm render hình ảnh trong bài viết
function renderImages(images = []) {
    if (!images.length) return '';

    return `
        <div class="post-images count-${images.length}">
            ${images.map(src => `<img src="${src}">`).join('')}
        </div>
    `;
}

// Hàm render video trong bài viết
function renderVideo(video) {
    if (!video) return '';

    return `
        <div class="post-video mt-2">
            <video controls class="w-100 rounded" style="max-height:400px">
                <source src="${video}">
            </video>
        </div>
        `;
}

// Hàm Xóa preview link trên modal tạo bài viết
function clearLinkPreview() {
    linkPreview = null;
    lastPreviewUrl = null;

    const box = document.getElementById('linkPreview');
    if (!box) return;

    box.classList.add('d-none');
    box.innerHTML = '';
}

// Hàm chèn emoji vào trình soạn thảo
function insertEmoji(emoji) {
    editor.model.change(writer => {
        const textNode = writer.createText(emoji);
        editor.model.insertContent(
            textNode,
            editor.model.document.selection
        );
    });
}

// Hàm chèn emoji vào trình soạn thảo
function toggleEmoji(e) {
    e.stopPropagation();
    const picker = document.getElementById('emojiPicker');
    emojiPickerVisible = !emojiPickerVisible;
    picker.classList.toggle('d-none', !emojiPickerVisible);
}

// Websocket
socket.on('post:update', post => {
    const el = document.getElementById(`post-${post.id}`);
    if (!el) return;
    el.remove();
    addPost(post);
});

socket.on("post:new", addPost);

socket.on('post:delete', data => {
    document.getElementById(`post-${data.id}`)?.remove();
});