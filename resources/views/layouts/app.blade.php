<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Lara JS')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/emoji-mart.css">
    <link rel="stylesheet" href="css/app.css">
    <link rel="stylesheet" href="css/chat.css">
    @stack('styles')
</head>

<body class="bg-light">
    @include('partials.header')

    <div class="container-fluid">
        @yield('content')
    </div>

    @include('partials.footer')

    @include('modal.chat')

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

            const avatar = u.avatar && u.avatar !== '' ? u.avatar: '';

            document.getElementById('nav-avatar').src = avatar;
            renderUsers();
        });

        const users = {};

        // Hàm render danh sách người dùng online/offline
        function renderUsers() {
            const online = document.getElementById('online');
            if (!online) return;
            online.innerHTML = "";
            Object.values(users).forEach(u => {
                if (String(u.id) === String(currentUserId)) return;
                const avatar = u.avatar && u.avatar !== '' ? u.avatar: '/images/default-avatar.jpg';
                online.innerHTML += `
                    <li class="d-flex align-items-center gap-2 mb-2 user-item"
                        data-user-id="${u.id}"
                        data-user-name="${u.name}"
                        data-avatar="${avatar}">
                        <span class="dot ${u.online ? 'online' : ''}"></span>
                        <img src="${avatar}" class="user-avatar" alt="" onerror="this.onerror=null;this.src='/images/default-avatar.jpg';">
                        <span class="user-name">${u.name}</span>
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

    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/chat.js') }}"></script>
    @stack('scripts')
</body>
</html>