document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('form[data-confirm]');

    forms.forEach((form) => {
        form.addEventListener('submit', function (event) {
            const message = form.dataset.confirm;

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    const dismissButtons = document.querySelectorAll('[data-dismiss-alert]');

    dismissButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const alertBox = button.closest('.flash-alert');
            if (alertBox) {
                alertBox.style.display = 'none';
            }
        });
    });
});
