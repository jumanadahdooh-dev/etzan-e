document.addEventListener("DOMContentLoaded", function () {
    const page = document.querySelector("[data-ai-chat-page]");

    if (!page) {
        return;
    }

    const form = page.querySelector("[data-ai-chat-form]");
    const input = page.querySelector("[data-ai-chat-input]");
    const body = page.querySelector("[data-ai-chat-body]");
    const conversationInput = page.querySelector("[data-ai-conversation-id]");
    const sendUrl = page.getAttribute("data-send-url");
    const chatBase = page.getAttribute("data-chat-base");
    const list = page.querySelector("[data-ai-conversation-list]");
    const emptyConversations = page.querySelector("[data-ai-empty-conversations]");

    function csrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute("content") : "";
    }

    function scrollToBottom() {
        if (body) {
            body.scrollTop = body.scrollHeight;
        }
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function messageHtml(role, content, time) {
        const isUser = role === "user";
        const label = isUser ? "أنت" : "مساعد اتزان";
        const safeContent = escapeHtml(content).replaceAll("\n", "<br>");

        return `
            <div class="ai-message ${isUser ? "is-user" : "is-assistant"}">
                <div class="ai-message-bubble">
                    <div class="ai-message-meta">
                        <strong>${label}</strong>
                        <small>${escapeHtml(time || "")}</small>
                    </div>
                    <p>${safeContent}</p>
                </div>
            </div>
        `;
    }

    function typingHtml() {
        return `
            <div class="ai-message is-assistant is-typing" data-ai-typing>
                <div class="ai-message-bubble">
                    <div class="ai-message-meta">
                        <strong>مساعد اتزان</strong>
                        <small>يكتب...</small>
                    </div>
                    <p>
                        <span class="ai-typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </p>
                </div>
            </div>
        `;
    }

    function removeWelcome() {
        const welcome = body.querySelector(".ai-welcome-state");
        const suggestions = body.querySelector(".ai-suggestions");

        if (welcome) {
            welcome.remove();
        }

        if (suggestions) {
            suggestions.remove();
        }
    }

    function resizeTextarea() {
        if (!input) {
            return;
        }

        input.style.height = "auto";
        input.style.height = Math.min(input.scrollHeight, 140) + "px";
    }

    function ensureConversationInSidebar(conversation) {
        if (!conversation || !conversation.id || !list) {
            return;
        }

        const exists = list.querySelector(`[data-conversation-row="${conversation.id}"]`);

        if (exists) {
            return;
        }

        if (emptyConversations) {
            emptyConversations.remove();
        }

        const row = document.createElement("div");
        row.className = "ai-conversation-row is-active";
        row.setAttribute("data-conversation-row", conversation.id);

        row.innerHTML = `
            <a href="${chatBase}/${conversation.id}" class="ai-conversation-link">
                <span class="ai-conversation-icon">
                    <i data-lucide="message-circle"></i>
                </span>

                <span class="ai-conversation-copy">
                    <strong>${escapeHtml(conversation.title || "محادثة جديدة")}</strong>
                    <small>الآن</small>
                </span>
            </a>

            <form action="${chatBase}/${conversation.id}" method="POST" onsubmit="return confirm('هل تريد حذف هذه المحادثة؟');">
                <input type="hidden" name="_token" value="${csrfToken()}">
                <input type="hidden" name="_method" value="DELETE">

                <button type="submit" aria-label="حذف المحادثة">
                    <i data-lucide="trash-2"></i>
                </button>
            </form>
        `;

        list.prepend(row);

        if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
        }
    }

    if (input) {
        input.addEventListener("input", resizeTextarea);

        input.addEventListener("keydown", function (event) {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();

                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        });

        resizeTextarea();
    }

    page.querySelectorAll("[data-ai-suggestion]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!input) {
                return;
            }

            input.value = button.getAttribute("data-ai-suggestion") || "";
            input.focus();
            resizeTextarea();
        });
    });

    if (form) {
        form.addEventListener("submit", async function (event) {
            event.preventDefault();

            const message = input ? input.value.trim() : "";

            if (!message) {
                input?.focus();
                return;
            }

            const submitButton = form.querySelector("button[type='submit']");
            const conversationId = conversationInput ? conversationInput.value : "";

            removeWelcome();

            body.insertAdjacentHTML("beforeend", messageHtml("user", message, "الآن"));
            body.insertAdjacentHTML("beforeend", typingHtml());
            scrollToBottom();

            input.value = "";
            resizeTextarea();

            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const response = await fetch(sendUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken()
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: conversationId || null
                    })
                });

                const data = await response.json();

                const typing = body.querySelector("[data-ai-typing]");

                if (typing) {
                    typing.remove();
                }

                if (!response.ok || !data.ok) {
                    const errorText = data.message || "حدث خطأ أثناء إرسال الرسالة.";
                    body.insertAdjacentHTML("beforeend", messageHtml("assistant", errorText, "الآن"));
                    scrollToBottom();
                    return;
                }

                if (conversationInput && data.conversation && data.conversation.id) {
                    conversationInput.value = data.conversation.id;
                    ensureConversationInSidebar(data.conversation);
                }

                if (data.assistant_message) {
                    body.insertAdjacentHTML(
                        "beforeend",
                        messageHtml(
                            "assistant",
                            data.assistant_message.content,
                            data.assistant_message.time || "الآن"
                        )
                    );
                }

                scrollToBottom();

                if (window.lucide && typeof window.lucide.createIcons === "function") {
                    window.lucide.createIcons();
                }
            } catch (error) {
                const typing = body.querySelector("[data-ai-typing]");

                if (typing) {
                    typing.remove();
                }

                body.insertAdjacentHTML(
                    "beforeend",
                    messageHtml("assistant", "تعذر الاتصال بمساعد اتزان الآن. جرّب مرة أخرى بعد قليل.", "الآن")
                );

                scrollToBottom();
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }

                input?.focus();
            }
        });
    }

    scrollToBottom();

    if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
    }
});
