let emojiPickerVisibleMes = false;

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

let chattingUserId = null;
function openChat(userId, name, avatar) {
    chattingUserId = userId;
    document.getElementById('chatUserName').innerText = name;
    document.getElementById('chatAvatar').src = avatar;
    document.getElementById('chatPopup').classList.remove('d-none');
    loadMessages(userId);
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
    if (!text) return;
    const res = await fetch('/api/message', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Authorization: 'Bearer ' + token
        },
        body: JSON.stringify({
            to_user_id: chattingUserId,
            content: text
        })
    });
    const msg = await res.json();
    appendMessage(msg, 'mine');
    input.value = '';
}

function appendMessage(msg, type) {
    const box = document.getElementById('chatMessages');
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
                    <div class="bubble">${msg.content}</div>
                </div>
            </div>
        `);
    } else {
        box.insertAdjacentHTML('beforeend', `
            <div class="chat-message mine">
                <div class="bubble">${msg.content}</div>
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

function closeChat() {
    document.getElementById('chatPopup').classList.add('d-none');
}

socket.on("chat:new", (msg) => {
    if (Number(msg.from_user_id) === Number(chattingUserId)) {
        appendMessage(msg, 'other');
    }
});