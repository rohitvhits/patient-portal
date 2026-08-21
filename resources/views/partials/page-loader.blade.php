{{-- Full-screen loading overlay, shared by the guest (login/OTP) and app (appointments)
     layouts. Every page here does a live, synchronous ERP call before it can render, so a
     plain click/submit with no feedback reads as "did that even work?" — this shows instantly
     and stays up until the browser actually finishes navigating to the new page. --}}
<div id="page-loader" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm" role="status" aria-live="polite" aria-label="Loading">
    <div class="flex flex-col items-center gap-4 rounded-2xl bg-white px-8 py-7 shadow-card">
        <span class="relative flex h-12 w-12 items-center justify-center">
            <span class="absolute inset-0 animate-spin rounded-full border-4 border-brand-100 border-t-brand-600"></span>
        </span>
        <p id="page-loader-text" class="text-sm font-semibold text-slate-700">Please wait&hellip;</p>
    </div>
</div>

<script>
    (function () {
        var overlay = document.getElementById('page-loader');
        var overlayText = document.getElementById('page-loader-text');
        if (!overlay) return;

        var hideTimer = null;
        var minVisibleTimer = null;
        var shownAt = 0;
        // On localhost the ERP round trip can come back in well under a blink (~100ms) —
        // without a floor, show()+hide() fire back-to-back so fast the spinner never
        // actually reaches the screen, which reads as "the loader doesn't work" even
        // though it fired correctly. Forcing it to stay up at least this long makes it
        // register as a real, professional loading state on every request, slow or fast.
        var MIN_VISIBLE_MS = 500;

        function actuallyHide() {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }

        window.NYPageLoader = {
            show: function (text) {
                overlayText.textContent = text || 'Please wait…';
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
                shownAt = Date.now();
                clearTimeout(minVisibleTimer);
                // Safety net: never let the overlay trap the user if a request stalls
                // (dropped connection, slow network) — auto-hide after 25s either way.
                clearTimeout(hideTimer);
                hideTimer = setTimeout(actuallyHide, 25000);
            },
            hide: function () {
                clearTimeout(hideTimer);
                var elapsed = Date.now() - shownAt;
                var remaining = MIN_VISIBLE_MS - elapsed;
                clearTimeout(minVisibleTimer);
                if (remaining > 0) {
                    minVisibleTimer = setTimeout(actuallyHide, remaining);
                } else {
                    actuallyHide();
                }
            }
        };

        // Forms: show the loader the instant a valid submit goes through — the appointment
        // filters round-trip through the live ERP call and the whole page changes anyway.
        // Forms marked data-button-loader (login/OTP) get a lighter in-button spinner
        // instead — the page itself doesn't change shape, so a full takeover overlay
        // would be overkill there.
        var SPINNER_HTML = '<span class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-current/30 border-t-current" aria-hidden="true"></span>';

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            if (form.hasAttribute('data-button-loader')) {
                var btn = form.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) {
                    btn.dataset.originalHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.classList.add('opacity-80', 'cursor-not-allowed');
                    btn.innerHTML = SPINNER_HTML + '<span>' + (btn.getAttribute('data-loading-text') || 'Please wait…') + '</span>';
                }
                return;
            }

            if (form.hasAttribute('data-no-loader')) return;
            window.NYPageLoader.show(form.getAttribute('data-loader-text'));
        });

        // Links: same treatment for ordinary same-tab navigations, skipping anything that
        // won't actually leave this page (file downloads, anchors, new tabs, etc).
        document.addEventListener('click', function (event) {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            var link = event.target.closest('a[href]');
            if (!link || link.hasAttribute('data-no-loader') || link.hasAttribute('download')) return;
            if (link.target && link.target !== '_self') return;
            var href = link.getAttribute('href') || '';
            if (href === '' || href.charAt(0) === '#' || /^(mailto|tel|javascript):/i.test(href)) return;
            if (link.origin !== window.location.origin) return;
            window.NYPageLoader.show(link.getAttribute('data-loader-text'));
        });

        // `pageshow` fires after EVERY page load, not just a bfcache back/forward
        // restore — event.persisted is what actually tells the two apart. Without that
        // check this fired on every plain navigation too, right after `load`, and
        // force-hid the overlay a beat after the page's own script had just shown it —
        // i.e. it cancelled the loader on basically every request, which is why it
        // never seemed to work. Only a real bfcache restore should force-clear it here;
        // everything else must be left alone to run its own show()/fetch()/hide() cycle.
        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            clearTimeout(hideTimer);
            clearTimeout(minVisibleTimer);
            actuallyHide();
            document.querySelectorAll('button[data-original-html]').forEach(function (btn) {
                btn.innerHTML = btn.dataset.originalHtml;
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
                delete btn.dataset.originalHtml;
            });
        });
    })();
</script>
