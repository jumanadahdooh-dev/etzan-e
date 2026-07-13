document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('[data-time-value]');
    const selectedInput = document.querySelector('[data-selected-time]');
    const form = document.querySelector('[data-appt-form]');

    if (buttons.length && selectedInput) {
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                buttons.forEach(function (btn) {
                    btn.classList.remove('is-active');
                });

                button.classList.add('is-active');
                selectedInput.value = button.dataset.timeValue || '';
            });
        });
    }

    if (form && selectedInput) {
        form.addEventListener('submit', function (event) {
            if (!selectedInput.value) {
                event.preventDefault();

                const slotsBox = document.querySelector('.appt-slots-box');

                if (slotsBox) {
                    slotsBox.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    slotsBox.classList.add('is-shaking');

                    setTimeout(function () {
                        slotsBox.classList.remove('is-shaking');
                    }, 450);
                }

                if (window.showPatientToast) {
                    window.showPatientToast('اختر وقت الموعد أولًا');
                } else {
                    alert('اختر وقت الموعد أولًا');
                }
            }
        });
    }
});
