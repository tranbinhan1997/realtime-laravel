let emojiPickerVisibleMes = false;
let chatLinkPreview;
let lastChatPreviewUrl;
let chattingUserId = null;

document.addEventListener('click', function (e) {
    const userItem = e.target.closest('.user-item');
    if (!userItem) return;
    const userId   = userItem.dataset.userId;
    const userName = userItem.dataset.userName;
    const avatar   = userItem.dataset.avatar;
    openChat(userId, userName, avatar);
});

document.addEventListener('click', (e) => {
    const picker = document.getElementById('emojiPickerMes');
    const emojiBtn = e.target.closest('button');

    if (!picker || picker.classList.contains('d-none')) return;

    if (picker.contains(e.target) || emojiBtn?.innerText === '😊') return;

    picker.classList.add('d-none');
    emojiPickerVisibleMes = false;
});

document.getElementById('chatInput').addEventListener('input', async function () {
    const text = this.value;
    const urlRegex = /(https?:\/\/[^\s]+)/;
    const match = text.match(urlRegex);
    if (!match) {
        chatLinkPreview = null;
        lastChatPreviewUrl = null;
        return;
    }
    const url = match[1];
    if (url === lastChatPreviewUrl) return;
    lastChatPreviewUrl = url;
    try {
        const res = await fetch('/api/preview-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Authorization: 'Bearer ' + token
            },
            body: JSON.stringify({ url })
        });
        chatLinkPreview = await res.json();
    } catch (e) {
        chatLinkPreview = null;
    }
});


function openChat(userId, name, avatar) {
    chattingUserId = userId;
    document.getElementById('chatUserName').innerText = name;
    document.getElementById('chatAvatar').src = avatar;
    document.getElementById('chatPopup').classList.remove('d-none');
    loadMessages(userId);

    if (users[userId]) {
        users[userId].unread = 0;
        renderUsers();
    }

    fetch(`/api/messages-read/${userId}`, {
        method: 'POST',
        headers: { Authorization: 'Bearer ' + token }
    });
}

async function loadMessages(userId) {
    const box = document.getElementById('chatMessages');
    box.innerHTML = `<div class="text-muted text-center">Đang tải...</div>`;
    const res = await fetch(`/api/message/${userId}`, {
        headers: {
            Authorization: 'Bearer ' + token
        }
    });
    const messages = await res.json();
    box.innerHTML = '';
    messages.forEach(m => {
        const type = m.from_user_id === currentUserId ? 'mine' : 'other';
        appendMessage(m, type);
    });

    const picker = new EmojiMart.Picker({
        onEmojiSelect: (emoji) => {
            insertEmojiMes(emoji.native);
        },
        theme: 'light',
        previewPosition: 'none',
        skinTonePosition: 'none'
    });

    document.getElementById('emojiPickerMes').appendChild(picker);
}

async function sendMessage() {
    const input = document.getElementById('chatInput');
    const text = input.value.trim();
    if (!text && !chatLinkPreview) return;
    const formData = new FormData();
    formData.append('to_user_id', chattingUserId);
    formData.append('content', text);
    if (chatLinkPreview) {
        formData.append('link[url]', chatLinkPreview.url ?? '');
        formData.append('link[title]', chatLinkPreview.title ?? '');
        formData.append('link[desc]', chatLinkPreview.desc ?? '');
        formData.append('link[image]', chatLinkPreview.image ?? '');
    }

    const res = await fetch('/api/message', {
        method: 'POST',
        headers: {
            Authorization: 'Bearer ' + token
        },
        body: formData
    });
    const msg = await res.json();
    appendMessage(msg, 'mine');
    input.value = '';
    chatLinkPreview = null;
    lastChatPreviewUrl = null;
}

function appendMessage(msg, type) {
    const box = document.getElementById('chatMessages');
    let imagesHtml = '';
    if (msg.images && msg.images.length) {
        imagesHtml = `
            <div class="chat-images">
                ${msg.images.map(src => `
                    <img src="${src}" class="chat-image">
                `).join('')}
            </div>
        `;
    }

    let videoHtml = '';
    if (msg.video) {
        videoHtml = `
            <div class="chat-video">
                <video controls class="chat-video-player">
                    <source src="${msg.video}">
                </video>
            </div>
        `;
    }

    let linkHtml = '';
    if (msg.link && msg.link.url) {
        linkHtml = `
            <div class="chat-link"
                onclick="window.open('${msg.link.url}', '_blank')">
                ${msg.link.image ? `<img src="${msg.link.image}" class="chat-link-image">` : ''}
                <div class="chat-link-content">
                    <div class="chat-link-title">
                        ${msg.link.title ?? msg.link.url}
                    </div>
                    <div class="chat-link-desc">
                        ${msg.link.desc ?? ''}
                    </div>
                    <div class="chat-link-domain">
                        ${new URL(msg.link.url).hostname}
                    </div>
                </div>
            </div>
        `;
    }

    const contentHtml = msg.content? `<div class="chat-text">${msg.content}</div>`: '';
    if (type === 'other') {
        box.insertAdjacentHTML('beforeend', `
            <div class="chat-message other">
                <img src="${msg.avatar || '/images/default-avatar.jpg'}"
                     class="chat-avatar-small"
                     onerror="this.src='/images/default-avatar.jpg'">

                <div class="chat-content">
                    <div class="chat-meta">
                        <strong class="chat-name">${msg.user}</strong>
                        <span class="chat-time">${msg.time}</span>
                    </div>
                    <div class="bubble">
                        ${contentHtml}
                        ${imagesHtml}
                        ${videoHtml}
                        ${linkHtml}
                    </div>
                </div>
            </div>
        `);
    } else {
        box.insertAdjacentHTML('beforeend', `
            <div class="chat-message mine">
                <div class="bubble">
                    ${contentHtml}
                    ${imagesHtml}
                    ${videoHtml}
                    ${linkHtml}
                </div>
            </div>
        `);
    }
    box.scrollTop = box.scrollHeight;
}

// Hàm chèn emoji vào trình soạn thảo
function openEmoji(e) {
    e.stopPropagation();
    const picker = document.getElementById('emojiPickerMes');
    emojiPickerVisibleMes = !emojiPickerVisibleMes;
    picker.classList.toggle('d-none', !emojiPickerVisibleMes);
}

function insertEmojiMes(emoji) {
    const input = document.getElementById('chatInput');
    if (!input) return;
    const start = input.selectionStart ?? input.value.length;
    const end   = input.selectionEnd   ?? input.value.length;
    input.value =input.value.slice(0, start) +emoji +input.value.slice(end);
    const pos = start + emoji.length;
    input.setSelectionRange(pos, pos);
    input.focus();
}

function chooseImageMes() {
    document.getElementById('imageInputMes').click();
}

function chooseVideoMes() {
    document.getElementById('videoInputMes').click();
}

async function uploadImageMes(e) {
    const files = [...e.target.files];
    if (!files.length) return;
    const formData = new FormData();
    formData.append('to_user_id', chattingUserId);
    files.forEach(file => {
        formData.append('images[]', file);
    });
    const res = await fetch('/api/message', {
        method: 'POST',
        headers: {
            Authorization: 'Bearer ' + token
        },
        body: formData
    });
    const msg = await res.json();
    appendMessage(msg, 'mine');
    e.target.value = '';
}

async function uploadVideoMes(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('to_user_id', chattingUserId);
    formData.append('video', file);

    const res = await fetch('/api/message', {
        method: 'POST',
        headers: {
            Authorization: 'Bearer ' + token
        },
        body: formData
    });

    const msg = await res.json();
    appendMessage(msg, 'mine');

    e.target.value = '';
}

function closeChat() {
    document.getElementById('chatPopup').classList.add('d-none');
    chattingUserId = null; 
}

socket.on("chat:new", (msg) => {
    if (Number(msg.from_user_id) === Number(chattingUserId)) {
        appendMessage(msg, 'other');
    } else {
        if (users[msg.from_user_id]) {
            users[msg.from_user_id].unread =
                (users[msg.from_user_id].unread || 0) + 1;

            renderUsers();
        }
    }
});