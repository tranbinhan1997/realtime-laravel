<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Lara JS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #adb5bd;
            display: inline-block;
        }

        .dot.online {
            background: #28a745;
        }

        .ck-editor__editable {
            min-height: 100px;
        }

        .post-content figure.image {
            max-width: 100%;
            margin: 8px auto;
            text-align: center;
        }

        .post-content figure.image img {
            max-width: 100%;
            max-height: 500px;
            width: auto;
            height: auto;
            border-radius: 8px;
            object-fit: contain;
            display: inline-block;
        }

        .input-post {
            cursor: pointer;
            width: 70%;
            margin: 0 auto;
            border-radius: 50px;
        }

        .link-preview img {
            display: block;
        }

        .link-preview:hover {
            background: #f8f9fa;
        }

    </style>
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
                        <input type="file" id="imageInput" accept="image/*"hidden onchange="uploadImage(this)">
                    </div>
                    <textarea id="postContent"></textarea>
                    <div id="linkPreview" class="border rounded p-2 mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary" onclick="createPost()">Đăng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.3.0/classic/ckeditor.js"></script>
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>

    <script>
        const token = localStorage.getItem("token");
        if (!token) location.href = "/login";

        fetch("/api/auth/me", {
            headers: {
                "Authorization": "Bearer " + token,
                "Accept": "application/json"
            }
        }).then(r => r.json()).then(u => username.innerText = u.name);

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

        const socket = io("http://localhost:3000", { auth: { token } });

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
            feed.innerHTML =
                `<div class="card mb-3">
                    <div class="card-body">
                        <strong>${post.user}</strong>
                        <small class="text-muted"> · ${post.time}</small>
                        <div class="mt-2 post-content">${post.content}</div>
                        ${renderLink(post.link)}
                    </div>
                </div>` + feed.innerHTML;
        }

        // Hàm tạo bài viết mới
        async function createPost() {
            const content = editor.getData().trim();
            if (!content) return;

            await fetch("/api/posts", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: "Bearer " + token,
                    Accept: "application/json"
                },
                body: JSON.stringify({ content, link: linkPreview })
            });

            editor.setData("");
            postModal.hide();
        }

        socket.on("post:new", addPost);

    </script>

    <script>
        let editor;
        let postModal;
        let linkPreview;
        let lastPreviewUrl;

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

            postModal._element.addEventListener('hidden.bs.modal', () => {
                linkPreview = null;
                document.getElementById('linkPreview').classList.add('d-none');
            });
        });

        // Hàm mở modal tạo bài viết
        function openPostModal() {
            postModal.show();
            setTimeout(() => editor?.editing.view.focus(), 300);
        }

        // Hàm chọn ảnh từ máy tính
        function chooseImage() {
            document.getElementById('imageInput').click();
        }

        // Hàm upload ảnh lên server
        async function uploadImage(input) {
            const file = input.files[0];
            if (!file) return;

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

            editor.model.change(writer => {
                const imageElement = writer.createElement('imageBlock', {
                    src: data.url
                });

                editor.model.insertContent(
                    imageElement,
                    editor.model.document.selection
                );
            });

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

        // Hàm Xóa preview link trên modal tạo bài viết
        function clearLinkPreview() {
            linkPreview = null;
            lastPreviewUrl = null;

            const box = document.getElementById('linkPreview');
            if (!box) return;

            box.classList.add('d-none');
            box.innerHTML = '';
        }

    </script>

</body>
</html>