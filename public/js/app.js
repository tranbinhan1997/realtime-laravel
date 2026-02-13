let editor;
let postModal;
let linkPreview;
let lastPreviewUrl;
let uploadedVideo;
let uploadedImages = [];
let emojiPickerVisible = false;
let editingPostId = null;
let nextPageUrl = '/api/posts';
let isLoading = false;
let viewerImages = [];
let viewerIndex = 0;
let commentEmojiPickers = {};
let replyEmojiPickers = {};
let commentUploads = {};
let replyUploads = {};

const reactionIcons = {
    like: '👍',
    love: '❤️',
    haha: '😂',
    wow:  '😮',
    sad:  '😢',
    angry:'😡'
};

async function loadPosts() {
    if (!nextPageUrl || isLoading) return;

    isLoading = true;
    setLoading(true);

    try {
        const res = await fetch(nextPageUrl, {
            headers: {
                Authorization: 'Bearer ' + token,
                Accept: 'application/json'
            }
        });

        const json = await res.json();

        json.data.forEach(addPost);
        nextPageUrl = json.next_page;
    } catch (e) {
        console.error(e);
    } finally {
        isLoading = false;
        setLoading(false);
    }
}

loadPosts();

document.addEventListener('scroll', () => {
    const nearBottom =
        window.innerHeight + window.scrollY >=
        document.body.offsetHeight - 300;

    if (nearBottom) {
        loadPosts();
    }
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

document.getElementById('imageViewer').addEventListener('click', e => {
    if (e.target.id === 'imageViewer') {
        closeImageViewer();
    }
});

document.addEventListener('mouseover', e => {
    const wrapper = e.target.closest('.reaction-wrapper');
    if (!wrapper) return;

    const picker = wrapper.querySelector('.reaction-picker');
    if (picker) picker.classList.remove('d-none');
});

document.addEventListener('mouseout', e => {
    const wrapper = e.target.closest('.reaction-wrapper');
    if (!wrapper) return;
    if (!wrapper.contains(e.relatedTarget)) {
        const picker = wrapper.querySelector('.reaction-picker');
        if (picker) picker.classList.add('d-none');
    }
});

function setLoading(show) {
    document.getElementById('loading').classList.toggle('d-none', !show);
}

// Hàm thêm bài viết vào feed
function addPost(post, { prepend = false } = {}) {
    if (document.getElementById(`post-${post.id}`)) return;

    const isOwner = post.author_id === currentUserId;

    const avatar = post.avatar ? post.avatar: 'https://cdn2.vectorstock.com/i/1000x1000/23/81/default-avatar-profile-icon-vector-18942381.jpg';

    const reactionSummary = renderReactionSummary(post);
    const userReactionIcon = post.user_reaction ? reactionIcons[post.user_reaction] : '👍';

    const html = `
    <div class="card mb-3 post-item" id="post-${post.id}">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3">
                <img 
                    src="${avatar}"
                    class="post-avatar"
                    alt="avatar"
                    onerror="this.onerror=null;this.src='https://cdn2.vectorstock.com/i/1000x1000/23/81/default-avatar-profile-icon-vector-18942381.jpg';"
                >
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>${post.user}</strong>
                            <small class="text-muted"> · ${post.time}</small>
                        </div>
                        ${isOwner ? `
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">⋯</button>
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
                    <div class="mt-2 post-content">${post.content ?? ''}</div>
                    ${renderImages(post.images)}
                    ${renderLink(post.link)}
                    ${renderVideo(post.video)}

                    <div id="reaction-summary-${post.id}"
                        class="mt-2 d-flex gap-2">
                        ${reactionSummary}
                    </div>
                    <div class="mt-2 position-relative reaction-wrapper d-inline-block">
                        <button class="btn btn-light"
                            onclick="react(${post.id}, 'like')"
                            id="react-btn-${post.id}">
                            ${userReactionIcon}
                        </button>
                        <div class="reaction-picker d-none"
                            id="reaction-picker-${post.id}">
                            ${Object.keys(reactionIcons).map(type => `
                                <span onclick="react(${post.id}, '${type}')">
                                    ${reactionIcons[type]}
                                </span>
                            `).join('')}
                        </div>
                    </div>

                    <button class="btn btn-sm btn-light"
                        id="comment-btn-${post.id}"
                        onclick="toggleCommentBox(${post.id})">
                        💬 ${post.comment_count ?? 0}
                    </button>

                    <div id="comment-section-${post.id}" class="mt-2 d-none">
                        <div id="comment-list-${post.id}">
                            ${renderComments(post.comments, post.id)}
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button class="btn btn-light btn-sm" onclick="toggleCommentEmoji(${post.id})">😊</button>
                            <button class="btn btn-light btn-sm" type="button" onclick="chooseImageComment(${post.id})">📷</button>
                            <button class="btn btn-light btn-sm" type="button" onclick="chooseVideoComment(${post.id})">🎥</button>
                            <input type="text" id="comment-input-${post.id}" class="form-control form-control-sm" placeholder="Viết bình luận...">
                            <button class="btn btn-primary btn-sm" onclick="sendComment(${post.id})"> ➤ </button>
                            <input type="file" id="imageInputComment-${post.id}" onchange="handleCommentImage(event, ${post.id})" multiple accept="image/*" hidden>
                            <input type="file" id="videoInputReplyComment" onchange="uploadVideoComment(event, ${post.id})" accept="video/*" hidden>
                        </div>
                    </div>
                    <div id="comment-emoji-${post.id}" class="d-none position-relative"></div>

                </div>
            </div>
        </div>
    </div>
    `;

    feed.insertAdjacentHTML('afterbegin', html); // bài mới
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
    addPost(post, { prepend: true });
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
            ${images.map((src, index) => `
                <img src="${src}"
                     onclick='openImageViewer(${JSON.stringify(images)}, ${index})'>
            `).join('')}
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

function openImageViewer(images, index) {
    viewerImages = images;
    viewerIndex = index;

    const viewer = document.getElementById('imageViewer');
    const img = document.getElementById('viewerImage');

    img.src = viewerImages[viewerIndex];
    viewer.classList.remove('d-none');
    document.body.style.overflow = 'hidden';
}

function closeImageViewer() {
    const viewer = document.getElementById('imageViewer');
    const img = document.getElementById('viewerImage');

    img.src = '';
    viewer.classList.add('d-none');
    document.body.style.overflow = '';
}

function nextImage() {
    viewerIndex = (viewerIndex + 1) % viewerImages.length;
    document.getElementById('viewerImage').src = viewerImages[viewerIndex];
}

function prevImage() {
    viewerIndex =
        (viewerIndex - 1 + viewerImages.length) % viewerImages.length;
    document.getElementById('viewerImage').src = viewerImages[viewerIndex];
}

function renderReactionSummary(post) {
    if (!post.reactions) return '';
    return Object.entries(post.reactions)
        .map(([type, total]) => `
            <span class="reaction-item">
                ${reactionIcons[type]} ${total}
            </span>
        `).join('');
}

async function react(postId, type) {
    const res = await fetch(`/api/posts/${postId}/react`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Authorization: 'Bearer ' + token
        },
        body: JSON.stringify({ type })
    });
    const data = await res.json();
    updateReactionUI(data);
}

function updateReactionUI(data) {
    const btn = document.getElementById(`react-btn-${data.post_id}`);
    const summary = document.getElementById(`reaction-summary-${data.post_id}`);
    btn.innerText = data.user_reaction? reactionIcons[data.user_reaction]: '👍';
    summary.innerHTML = Object.entries(data.summary).map(([type, total]) =>
        `<span>${reactionIcons[type]} ${total}</span>`
    ).join('');
}

function toggleCommentBox(postId) {
    document.getElementById(`comment-section-${postId}`).classList.toggle('d-none');
}

function showReplyBox(commentId, username = '') {
    let box = document.getElementById(`reply-box-${commentId}`);
    if (!box) {
        const replyEl = document.getElementById(`comment-${commentId}`);
        if (!replyEl) return;

        const parentComment = replyEl.closest('.comment-item');
        if (!parentComment) return;

        const parentId = parentComment.id.replace('comment-', '');
        box = document.getElementById(`reply-box-${parentId}`);
    }
    if (!box) return;
    box.classList.remove('d-none');
    if (username) {
        const input = box.querySelector('input');
        input.value = `@${username} `;
        input.focus();
    }
}

async function sendComment(postId) {
    const input = document.getElementById(`comment-input-${postId}`);
    const content = input.value.trim();
    const formData = new FormData();
    formData.append('content', content);
    if (commentUploads[postId]) {
        commentUploads[postId].forEach(file => {
            formData.append('images[]', file);
        });
    }
    const res = await fetch(`/api/posts/${postId}/comment`, {
        method: 'POST',
        headers: {
            Authorization: 'Bearer ' + token
        },
        body: formData
    });
    input.value = '';
    commentUploads[postId] = [];
    const emojiContainer = document.getElementById(`comment-emoji-${postId}`);
    if (emojiContainer) emojiContainer.classList.add('d-none');
}

async function sendReply(parentId, postId, input) {
    const content = input.value.trim();
    const formData = new FormData();
    formData.append('content', content);
    formData.append('parent_id', parentId);

    if (replyUploads[parentId]) {
        replyUploads[parentId].forEach(file => {
            formData.append('images[]', file);
        });
    }
    await fetch(`/api/posts/${postId}/comment`, {
        method: 'POST',
        headers: {
            Authorization: 'Bearer ' + token
        },
        body: formData
    });
    input.value = '';
    replyUploads[parentId] = [];
    const replyBox = document.getElementById(`reply-box-${parentId}`);
    if (replyBox) replyBox.classList.add('d-none');
    const emojiContainer = document.getElementById(`reply-emoji-${parentId}`);
    if (emojiContainer) emojiContainer.classList.add('d-none');
}

function updateCommentCount(postId) {
    const btn = document.getElementById(`comment-btn-${postId}`);
    if (!btn) return;

    const current = parseInt(btn.innerText.replace(/\D/g,'')) || 0;
    btn.innerText = `💬 ${current + 1}`;
}

function addCommentToUI(data) {
    if (data.parent_id) {
        const parent = document.getElementById(`comment-${data.parent_id}`);
        if (!parent) return;
        const repliesContainer = parent.querySelector('.replies-container');
        repliesContainer.insertAdjacentHTML(
            'beforeend',
            renderReply(data)
        );
        return;
    }
    const box = document.getElementById(`comment-list-${data.post_id}`);
    if (!box) return;
    box.insertAdjacentHTML(
        'beforeend',
        renderSingleComment(data, data.post_id)
    );
    updateCommentCount(data.post_id);
}

function renderComments(comments = [], postId) {
    return comments.map(c => renderSingleComment(c, postId)).join('');
}

function renderReply(r, postId) {
    let imagesHtml = '';
    if (r.images && r.images.length) {
        imagesHtml = `
            <div class="chat-images">
                ${r.images.map(src => `
                    <img src="${src}" class="chat-image">
                `).join('')}
            </div>
        `;
    }

    return `
        <div class="d-flex gap-2 mb-2" id="comment-${r.id}">
            <img src="${r.avatar}" width="24" height="24" class="rounded-circle">
            <div>
                <div class="fw-bold small">${r.user}</div>
                <div class="small">${formatContent(r.content)}</div>
                ${imagesHtml}
                <div class="small text-muted"
                    onclick="showReplyBox(${r.id}, '${r.user}')"
                    style="cursor:pointer">
                    Trả lời
                </div>
            </div>
        </div>
    `;
}

function renderSingleComment(c, postId) {
    let imagesHtml = '';
    if (c.images && c.images.length) {
        imagesHtml = `
            <div class="chat-images">
                ${c.images.map(src => `
                    <img src="${src}" class="chat-image">
                `).join('')}
            </div>
        `;
    }

    return `
        <div class="comment-item mb-2" id="comment-${c.id}">
            <div class="d-flex gap-2">
                <img src="${c.avatar}" width="28" height="28" class="rounded-circle">
                <div>
                    <div class="fw-bold small">${c.user}</div>
                    <div class="small">${formatContent(c.content)}</div>
                    ${imagesHtml}
                    <div class="small text-muted"
                        onclick="showReplyBox(${c.id}, '${c.user}')"
                        style="cursor:pointer">
                        Trả lời
                    </div>
                </div>
            </div>

            <div class="ms-4 mt-2 replies-container">
                ${
                    c.replies
                        ? c.replies.map(r => renderReply(r)).join('')
                        : ''
                }
            </div>

            <div id="reply-box-${c.id}" class="d-none ms-4 mt-2">
                <div class="d-flex gap-2">
                    <button class="btn btn-light btn-sm" onclick="toggleReplyEmoji(${c.id})">😊</button>
                    <button class="btn btn-light btn-sm" type="button" onclick="chooseImageReply(${c.id})">📷</button>
                    <button class="btn btn-light btn-sm" type="button" onclick="chooseVideoReply(${c.id})">🎥</button>
                    <input type="text" class="form-control form-control-sm" placeholder="Viết trả lời..." id="reply-input-${c.id}">
                    <button class="btn btn-sm btn-primary"
                        onclick="sendReply(${c.id}, ${postId}, document.getElementById('reply-input-${c.id}'))">➤
                    </button>
                    <input type="file" id="imageInputReply-${c.id}" onchange="handleReplyImage(event, ${c.id}, ${postId})" multiple accept="image/*" hidden>
                    <input type="file" id="videoInputReply" onchange="uploadVideoReply(event, ${c.id}, ${postId}))" accept="video/*" hidden>
                </div>
            </div>
            <div id="reply-emoji-${c.id}" class="d-none"></div>
        </div>
    `;
}

function toggleCommentEmoji(postId) {
    const container = document.getElementById(`comment-emoji-${postId}`);
    if (!commentEmojiPickers[postId]) {
        const picker = new EmojiMart.Picker({
            onEmojiSelect: (emoji) => {
                insertEmojiToInput(`comment-input-${postId}`, emoji.native);
            },
            theme: 'light',
            previewPosition: 'none',
            skinTonePosition: 'none'
        });
        container.appendChild(picker);
        commentEmojiPickers[postId] = picker;
    }
    container.classList.toggle('d-none');
}

function toggleReplyEmoji(commentId) {
    const container = document.getElementById(`reply-emoji-${commentId}`);
    if (!container) return;
    if (!replyEmojiPickers[commentId]) {
        const picker = new EmojiMart.Picker({
            onEmojiSelect: (emoji) => {
                insertEmojiToInput(`reply-input-${commentId}`, emoji.native);
            },
            theme: 'light',
            previewPosition: 'none',
            skinTonePosition: 'none'
        });
        container.appendChild(picker);
        replyEmojiPickers[commentId] = picker;
    }
    container.classList.toggle('d-none');
}


function insertEmojiToInput(inputId, emoji) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const start = input.selectionStart;
    const end = input.selectionEnd;
    const text = input.value;

    input.value =
        text.substring(0, start) +
        emoji +
        text.substring(end);

    input.focus();
    input.selectionStart = input.selectionEnd = start + emoji.length;
}

function chooseImageComment(postId) {
    document.getElementById(`imageInputComment-${postId}`).click();
}

function chooseVideoComment() {
    document.getElementById('videoInputComment').click();
}

function chooseImageReply(commentId) {
    document.getElementById(`imageInputReply-${commentId}`).click();
}

function chooseVideoReply() {
    document.getElementById('videoInputReply').click();
}

async function handleCommentImage(e, postId) {
    const files = [...e.target.files];
    if (!files.length) return;

    const formData = new FormData();
    files.forEach(file => {
        formData.append('images[]', file);
    });

    await fetch(`/api/posts/${postId}/comment`, {
        method: 'POST',
        headers: {
            Authorization: 'Bearer ' + token
        },
        body: formData
    });

    e.target.value = '';
}

// async function uploadVideoComment(e, postId) {
//     const file = e.target.files[0];
//     if (!file) return;

//     const formData = new FormData();
//     formData.append('video', file);

//     const res = await fetch(`/api/posts/${postId}/comment`, {
//         method: 'POST',
//         headers: {
//             Authorization: 'Bearer ' + token
//         },
//         body: formData
//     });

//     const msg = await res.json();
//     appendMessage(msg, 'mine');

//     e.target.value = '';
// }

async function handleReplyImage(e, commentId, postId) {
    const files = [...e.target.files];
    if (!files.length) return;

    const formData = new FormData();
    formData.append('parent_id', commentId);
    files.forEach(file => {
        formData.append('images[]', file);
    });

    await fetch(`/api/posts/${postId}/comment`, {
        method: 'POST',
        headers: {
            Authorization: 'Bearer ' + token
        },
        body: formData
    });

    e.target.value = '';
}

// async function uploadVideoReply(e) {
//     const file = e.target.files[0];
//     if (!file) return;

//     const formData = new FormData();
//     formData.append('video', file);

//     const res = await fetch('`/api/posts/${postId}/comment`', {
//         method: 'POST',
//         headers: {
//             Authorization: 'Bearer ' + token
//         },
//         body: formData
//     });

//     const msg = await res.json();
//     appendMessage(msg, 'mine');

//     e.target.value = '';
// }





function formatContent(text) {
    return (text || '').replace(
        /@(\w+)/g,
        '<span class="mention">@$1</span>'
    );
}

// Websocket
socket.on('post:update', post => {
    const el = document.getElementById(`post-${post.id}`);
    if (!el) return;
    el.remove();
    addPost(post);
});

socket.on('post:new', post => {
    addPost(post, { prepend: true });
});

socket.on('post:delete', data => {
    document.getElementById(`post-${data.id}`)?.remove();
});

socket.on('post:react', data => {
    updateReactionUI(data);
});

socket.on('post:comment', data => {
    addCommentToUI(data);
});