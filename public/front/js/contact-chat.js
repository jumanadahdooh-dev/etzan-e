document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('chatForm');
  const messageInput = document.getElementById('messageInput');
  const subjectInput = document.getElementById('subjectInput');
  const chatMessages = document.getElementById('chatMessages');
  const typingRow = document.getElementById('typingRow');
  const quickChips = document.querySelectorAll('.quick-chip');

  if (!form || !messageInput || !chatMessages) return;

  form.noValidate = true;

  const resizeTextarea = () => {
    messageInput.style.height = '52px';
    messageInput.style.height = `${Math.min(messageInput.scrollHeight, 130)}px`;
  };

  const appendMessage = (text, type = 'user') => {
    const row = document.createElement('div');
    row.className = `message-row ${type === 'support' ? 'support-message' : 'user-message'}`;

    const bubble = document.createElement('div');
    bubble.className = `message-bubble ${type === 'support' ? 'support-bubble' : 'user-bubble'}`;
    bubble.innerHTML = String(text).replace(/\n/g, '<br>');

    row.appendChild(bubble);

    if (typingRow) {
      chatMessages.insertBefore(row, typingRow);
    } else {
      chatMessages.appendChild(row);
    }

    chatMessages.scrollTop = chatMessages.scrollHeight;
  };

  const setSending = (isSending) => {
    const button = form.querySelector('.send-button');

    if (button) {
      button.disabled = isSending;
      button.classList.toggle('is-loading', isSending);
    }

    messageInput.disabled = isSending;
  };

  const getFieldWrapper = (input) => {
    return input.closest('.guest-field')
      || input.closest('.contact-subject-row')
      || input.closest('.message-field')
      || input.parentElement;
  };

  const clearFieldError = (input) => {
    if (!input) return;

    input.classList.remove('contact-field-error', 'contact-shake');

    const wrapper = getFieldWrapper(input);
    const oldError = wrapper?.querySelector('.contact-field-error-text');

    if (oldError) {
      oldError.remove();
    }
  };

  const clearErrors = () => {
    form.querySelectorAll('.contact-field-error').forEach((input) => {
      input.classList.remove('contact-field-error', 'contact-shake');
    });

    form.querySelectorAll('.contact-field-error-text').forEach((error) => {
      error.remove();
    });
  };

  const showFieldError = (input, message) => {
    clearFieldError(input);

    input.classList.add('contact-field-error', 'contact-shake');

    const wrapper = getFieldWrapper(input);

    const errorText = document.createElement('small');
    errorText.className = 'contact-field-error-text';
    errorText.textContent = message;

    wrapper.appendChild(errorText);

    setTimeout(() => {
      input.classList.remove('contact-shake');
    }, 300);
  };

  const validateForm = () => {
    clearErrors();

    const guestNameInput = document.getElementById('guestName');
    const guestEmailInput = document.getElementById('guestEmail');
    const currentMessageInput = document.getElementById('messageInput');

    let isValid = true;
    let firstInvalidField = null;

    if (guestNameInput && !guestNameInput.value.trim()) {
      showFieldError(guestNameInput, 'اكتبي اسمك أولًا.');
      isValid = false;
      firstInvalidField = firstInvalidField || guestNameInput;
    }

    if (guestEmailInput && !guestEmailInput.value.trim()) {
      showFieldError(guestEmailInput, 'اكتبي بريدك الإلكتروني حتى نقدر نرد عليك.');
      isValid = false;
      firstInvalidField = firstInvalidField || guestEmailInput;
    } else if (
      guestEmailInput &&
      !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(guestEmailInput.value.trim())
    ) {
      showFieldError(guestEmailInput, 'صيغة البريد الإلكتروني غير صحيحة.');
      isValid = false;
      firstInvalidField = firstInvalidField || guestEmailInput;
    }

    if (currentMessageInput && !currentMessageInput.value.trim()) {
      showFieldError(currentMessageInput, 'اكتبي رسالتك أولًا.');
      isValid = false;
      firstInvalidField = firstInvalidField || currentMessageInput;
    }

    if (firstInvalidField) {
      firstInvalidField.focus();
    }

    return isValid;
  };

  document.querySelectorAll('#guestName, #guestEmail, #messageInput').forEach((input) => {
    input.addEventListener('input', () => {
      clearFieldError(input);
    });
  });

  quickChips.forEach((chip) => {
    chip.addEventListener('click', () => {
      const text = chip.dataset.message || chip.textContent.trim();

      messageInput.value = text;

      if (subjectInput && !subjectInput.value.trim()) {
        subjectInput.value = chip.textContent.trim();
      }

      clearFieldError(messageInput);

      resizeTextarea();
      messageInput.focus();
    });
  });

  messageInput.addEventListener('input', resizeTextarea);
  resizeTextarea();

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!validateForm()) {
      return;
    }

    const formData = new FormData(form);
    const url = form.dataset.storeUrl || form.action;
    const csrfToken = form.querySelector('input[name="_token"]')?.value || '';
    const message = messageInput.value.trim();

    setSending(true);
    appendMessage(message, 'user');

    if (typingRow) {
      typingRow.hidden = false;
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'تعذر إرسال الرسالة الآن.');
      }

      messageInput.value = '';
      resizeTextarea();

      appendMessage(
        data.message || 'تم إرسال رسالتك بنجاح، سيتم الرد عليك عبر البريد الإلكتروني.',
        'support'
      );
    } catch (error) {
      appendMessage(error.message || 'تعذر إرسال الرسالة الآن، جرّبي مرة أخرى.', 'support');
    } finally {
      if (typingRow) {
        typingRow.hidden = true;
      }

      setSending(false);
      messageInput.focus();
    }
  });
});
