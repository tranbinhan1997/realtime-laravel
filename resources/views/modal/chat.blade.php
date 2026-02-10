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
        <input id="chatInput" type="text" placeholder="Nhập tin nhắn...">
        <button onclick="sendMessage()">Gửi</button>
    </div>
</div>