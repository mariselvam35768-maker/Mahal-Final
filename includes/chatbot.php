<div class="chatbot-container">
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <div style="width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-robot"></i>
                </div>
                <div>
                    <h4 style="font-size:0.9rem;"><?php echo $brand_name; ?> Assistant</h4>
                    <div style="display:flex;align-items:center;gap:0.3rem;font-size:0.7rem;color:rgba(255,255,255,0.8);">
                        <span style="width:7px;height:7px;background:#4ade80;border-radius:50%;display:inline-block;"></span>
                        Online
                    </div>
                </div>
            </div>
            <button onclick="toggleChat()" style="background:rgba(255,255,255,0.15);border:none;color:white;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <div class="chat-input-wrap" id="chatInputWrap" style="display:none;">
            <input type="text" id="chatInput" placeholder="Ask about halls, availability..." onkeypress="if(event.key==='Enter')sendMessage()">
            <button class="chat-send" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <div class="chat-actions" id="chatActions" style="padding: 1rem; display: flex; gap: 0.75rem; background: #f8fafc; border-top: 1px solid var(--border);">
            <button onclick="handleChoice('yes')" class="btn btn-primary" style="flex:1; border-radius: 50px; font-weight: 600;">Yes</button>
            <button onclick="handleChoice('no')" class="btn btn-outline" style="flex:1; border-radius: 50px; font-weight: 600;">No</button>
        </div>
    </div>

    <button class="chat-toggle-btn" id="chatBtn" onclick="toggleChat()" style="position:relative;">
        <i class="fas fa-comments" id="chatIcon"></i>
        <div class="chat-badge" id="chatBadge">1</div>
    </button>
</div>

<script>
const chatWindow = document.getElementById('chatWindow');
const chatMessages = document.getElementById('chatMessages');
const chatInput = document.getElementById('chatInput');
const chatBadge = document.getElementById('chatBadge');

let chatOpen = false;

// Initial greeting & State Machine
let currentState = 'start';

const flow = {
    start: {
        text: "<i class='fa-solid fa-hand-wave'></i> Hi! I'm your assistant at <?php echo $brand_name; ?>. \n\n Would you like to check our available halls?",
        yes: 'check_halls',
        no: 'help_else'
    },
    check_halls: {
        text: "Great! Are you looking for a large marriage hall (for 500+ guests)?",
        yes: 'large_hall',
        no: 'small_hall'
    },
    large_hall: {
        text: "We have premium spacious halls perfect for grand weddings. Would you like to see the pricing for them?",
        yes: 'show_pricing',
        no: 'small_hall'
    },
    small_hall: {
        text: "I see. Are you looking for a mini hall for a smaller party or reception?",
        yes: 'mini_hall',
        no: 'help_else'
    },
    mini_hall: {
        text: "Our mini halls are elegant and cozy. Shall I show you the gallery?",
        yes: 'show_gallery',
        no: 'help_else'
    },
    show_pricing: {
        text: "You can find all pricing details on our Halls page. Would you like to go there now?",
        yes: 'go_halls',
        no: 'help_else'
    },
    show_gallery: {
        text: "Our gallery showcases all our beautiful venues. Want to take a look?",
        yes: 'go_gallery',
        no: 'help_else'
    },
    help_else: {
        text: "No problem! Do you have any other questions about our services?",
        yes: 'start',
        no: 'end'
    },
    go_halls: {
        text: "Redirecting you to our Halls & Pricing page...",
        action: () => window.location.href = 'halls.php'
    },
    go_gallery: {
        text: "Opening our Photo Gallery...",
        action: () => window.location.href = 'gallery.php'
    },
    end: {
        text: "Alright! Feel free to click me again if you need anything. Have a great day! ✨",
        action: () => setTimeout(toggleChat, 2000)
    }
};

setTimeout(() => {
    updateChat();
}, 1200);

function updateChat() {
    const stateData = flow[currentState];
    addMessage(stateData.text, 'bot');
    
    // Hide buttons if it's an end state or action state
    const actions = document.getElementById('chatActions');
    if (stateData.action || currentState === 'end') {
        actions.style.display = 'none';
        if (stateData.action) {
            stateData.action();
        }
    } else {
        actions.style.display = 'flex';
    }
}

function handleChoice(choice) {
    const stateData = flow[currentState];
    addMessage(choice.charAt(0).toUpperCase() + choice.slice(1), 'user');
    
    // Hide buttons during transition
    document.getElementById('chatActions').style.display = 'none';
    
    // Show typing dots briefly
    const typingId = addMessage('<div class="typing-dots"><span></span><span></span><span></span></div>', 'bot');
    
    setTimeout(() => {
        const el = document.getElementById(typingId);
        if (el) el.remove();
        
        currentState = stateData[choice] || 'help_else';
        updateChat();
    }, 800);
}

function toggleChat() {
    chatOpen = !chatOpen;
    if (chatOpen) {
        chatWindow.classList.add('open');
        chatInput.focus();
        chatBadge.style.display = 'none';
    } else {
        chatWindow.classList.remove('open');
    }
}

let msgCounter = 0;
function addMessage(text, type) {
    msgCounter++;
    const id = 'msg_' + msgCounter + '_' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'chat-msg ' + (type === 'bot' ? 'bot' : 'user');
    div.style.opacity = '0';
    div.style.transform = 'translateY(10px)';
    div.innerHTML = text.replace(/\n/g, '<br>');
    chatMessages.appendChild(div);

    requestAnimationFrame(() => {
        setTimeout(() => {
            div.style.transition = 'all 0.3s ease';
            div.style.opacity = '1';
            div.style.transform = 'translateY(0)';
        }, 10);
    });

    chatMessages.scrollTop = chatMessages.scrollHeight;
    return id;
}

async function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;

    addMessage(text, 'user');
    chatInput.value = '';

    // Show typing indicator
    const typingId = addMessage('<div class="typing-dots"><span></span><span></span><span></span></div>', 'bot');

    try {
        const res = await fetch('actions/ai_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        });
        const data = await res.json();

        setTimeout(() => {
            const el = document.getElementById(typingId);
            if (el) { el.innerHTML = (data.reply || "Sorry, I couldn't understand that. Please try again.").replace(/\n/g, '<br>'); }
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 500);
    } catch (e) {
        const el = document.getElementById(typingId);
        if (el) { el.textContent = "I'm having trouble connecting right now. Please try again!"; }
    }
}
</script>

<style>
.typing-dots { display: flex; gap: 5px; padding: 4px 0; }
.typing-dots span { width: 8px; height: 8px; background: var(--gray-light); border-radius: 50%; animation: dotBounce 1.4s infinite; }
.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }
</style>
