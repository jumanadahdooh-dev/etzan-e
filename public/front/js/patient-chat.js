document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('[data-chat-form]');

    forms.forEach(function (form) {
        const textarea = form.querySelector('[data-auto-grow]');
        const submitButton = form.querySelector('button[type="submit"]');

        function resizeTextarea() {
            if (!textarea) {
                return;
            }

            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 150) + 'px';
        }

        if (textarea) {
            resizeTextarea();

            textarea.addEventListener('input', resizeTextarea);

            textarea.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();

                    const messageValue = textarea.value.trim();

                    if (!messageValue) {
                        textarea.focus();
                        return;
                    }

                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            });
        }

        form.addEventListener('submit', function (event) {
            const messageValue = textarea ? textarea.value.trim() : '';

            if (!messageValue) {
                event.preventDefault();

                if (textarea) {
                    textarea.focus();
                }

                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('is-loading');
            }
        });
    });

    const chatBody = document.querySelector('[data-live-chat-body]');

    if (chatBody) {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
