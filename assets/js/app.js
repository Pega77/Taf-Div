document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-live-search-form]').forEach(function (form) {
        const input = form.querySelector('[data-live-search-input]');
        const targetSelector = form.getAttribute('data-target');
        const target = targetSelector ? document.querySelector(targetSelector) : null;
        const url = form.getAttribute('data-live-url');
        if (!input || !target || !url) return;

        let timeoutId = null;
        const submitAjax = function () {
            const params = new URLSearchParams(new FormData(form));
            fetch(url + '?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.text(); })
                .then(function (html) { target.innerHTML = html; })
                .catch(function () {});
        };

        input.addEventListener('input', function () {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(submitAjax, 250);
        });
    });
});
