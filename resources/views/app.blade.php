<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Lara JS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/emoji-mart.css">
    <link rel="stylesheet" href="css/app.css">
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary px-3">
        <span class="navbar-brand fw-bold">LARA JS</span>
        <div class="d-flex align-items-center gap-3 text-white">
            <span id="username"></span>
            <button class="btn btn-light btn-sm" onclick="logout()">Đăng xuất</button>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row vh-100">
            <div class="col-md-10 p-4 overflow-auto">
                <div class="card mb-3">
                    <div class="card-body">
                        <input type="text" class="form-control input-post" placeholder="Bạn đang nghĩ gì thế?" readonly onclick="openPostModal()">
                    </div>
                </div>
                <div id="feed"></div>
            </div>

            <div class="col-md-2 border-start bg-white p-3">
                <h6 class="fw-bold">DANH SÁCH NGƯỜI DÙNG</h6>
                <ul class="list-unstyled" id="online"></ul>
            </div>
        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.3.0/classic/ckeditor.js"></script>
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/browser.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let socket;
        let currentUserId = null;
        let token = localStorage.getItem("token");

        if (!token) location.href = "/login";

        fetch("/api/auth/me", {
            headers: {
                Authorization: "Bearer " + token,
                Accept: "application/json"
            }
        })
        .then(r => r.json())
        .then(u => {
            username.innerText = u.name;
            currentUserId = u.id;
        });

        const users = {};

        // Hàm render danh sách người dùng online/offline
        function renderUsers() {
            online.innerHTML = "";
            Object.values(users).forEach(u => {
                online.innerHTML += `
                    <li class="d-flex align-items-center gap-2 mb-2">
                        <span class="dot ${u.online ? 'online' : ''}"></span>
                        ${u.name}
                    </li>
                `;
            });
        }

        socket = io("http://localhost:3000", { auth: { token } });

        // danh sách online ban đầu
        socket.on("presence:list", list => {
            list.forEach(u => users[u.id] = { ...u, online: true });
            renderUsers();
        });

        // user online
        socket.on("presence:online", u => {
            users[u.id] = { ...u, online: true };
            renderUsers();
        });

        // user offline
        socket.on("presence:offline", u => {
            if (users[u.id]) {
                users[u.id].online = false;
                renderUsers();
            }
        });

        async function logout() {
            await fetch("/api/auth/logout", {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + token,
                    "Accept": "application/json"
                }
            });

            localStorage.removeItem("token");
            window.location.href = "/login";
        }
    </script>

    <script>
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

        async function submitPost() {
            const content = editor.getData().trim();
            if (!content && !uploadedImages.length && !uploadedVideo) return;

            const payload = {
                content,
                images: uploadedImages.map(i => i.path),
                link: linkPreview,
                video: uploadedVideo
            };

            const url = editingPostId
                ? `/api/posts/${editingPostId}`
                : `/api/posts`;

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

            renderImagePreview();
            postModal.show();
        }

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

    </script>

    <script>
        let editor;
        let postModal;
        let linkPreview;
        let lastPreviewUrl;
        let uploadedVideo;
        let uploadedImages = [];
        let emojiPickerVisible = false;
        let editingPostId = null;

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
                    const text = editor.getData();
                    const match = text.match(/(https?:\/\/[^\s<]+\.[^\s<]+)/);

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
                        body: JSON.stringify({ url: match[0] })
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
                <div class="post-images mt-2 d-grid gap-2"
                    style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));">
                    ${images.map(img => `
                        <img src="${typeof img === 'string' ? img : img.url}"
                            class="rounded"
                            style="width:100%;object-fit:cover;max-height:300px">
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

    </script>

</body>
</html>