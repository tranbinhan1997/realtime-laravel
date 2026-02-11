<div id="chatPopup" class="chat-popup d-none">
    <div class="chat-header">
        <div class="d-flex align-items-center gap-2">
            <img id="chatAvatar" class="chat-avatar">
            <strong id="chatUserName"></strong>
        </div>
        <button class="btn-close" onclick="closeChat()"></button>
    </div>

    <div id="chatMessages" class="chat-body"></div>

    <div class="chat-footer">
        <div class="chat-tools">
            <button type="button" onclick="openEmoji(event)">😀</button>
            <button type="button" onclick="triggerImage()">📷</button>
            <button type="button" onclick="triggerVideo()">🎥</button>
        </div>

        <textarea id="chatInput" placeholder="Nhập tin nhắn..." rows="1"></textarea>
        <button class="btn-send" onclick="sendMessage()">➤</button>
        <input type="file" id="imageInput" accept="image/*" hidden>
        <input type="file" id="videoInput" accept="video/*" hidden>
    </div>

    <div id="emojiPickerMes" class="emoji-picker-mes d-none"></div>
</div>