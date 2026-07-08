/*
 * Product Carousel — arrow + mouse-drag scrolling. Each .gpwc-carousel is wired independently,
 * so multiple carousels can coexist on one page.
 *
 * Arrows scroll by one viewport width; CSS scroll-snap (x mandatory) then lands a product flush
 * at the left edge. Mouse drag scrolls freely with snap disabled, then smooth-scrolls to the
 * nearest card on release (JS-driven — CSS snap stays off until the animation settles).
 * A drag suppresses the click it would otherwise fire; a plain click anywhere on a card opens
 * the product, except on real controls (notably the ajax add-to-cart button).
 * Touch is left to native scrolling.
 */
(function () {
    var DRAG_THRESHOLD = 4; // px before a press counts as a drag (and suppresses the click)

    function initCarousel(carousel) {
        var track = carousel.querySelector('.gpwc-carousel__track');
        if (!track) {
            return;
        }

        var prev = carousel.querySelector('.gpwc-carousel__arrow--prev');
        var next = carousel.querySelector('.gpwc-carousel__arrow--next');

        // Reflect scroll position into classes so CSS can show only the usable arrow:
        // hide "prev" at the very start, hide "next" once the end is reached.
        function updateArrows() {
            var maxScroll = track.scrollWidth - track.clientWidth;
            var x = track.scrollLeft;
            carousel.classList.toggle('can-scroll-prev', x > 1);
            carousel.classList.toggle('can-scroll-next', x < maxScroll - 1);
        }

        track.addEventListener('scroll', updateArrows, { passive: true });
        window.addEventListener('resize', updateArrows);
        window.addEventListener('load', updateArrows); // images can change scrollWidth
        updateArrows();

        // Distance from one card's start to the next (card width + gap), measured from the DOM.
        function cardStep() {
            var items = track.querySelectorAll('ul.products > li.product, ul.products > li');
            if (items.length >= 2) {
                return Math.abs(
                    items[1].getBoundingClientRect().left - items[0].getBoundingClientRect().left
                );
            }
            return items.length === 1 ? items[0].getBoundingClientRect().width : track.clientWidth;
        }

        // Page by the number of *fully* visible cards, so the partially-visible (peeking) card
        // becomes the new first card — no scrolling one product too far.
        function page(direction) {
            var step = cardStep();
            if (step <= 0) {
                return;
            }
            var visible = Math.max(1, Math.floor(track.clientWidth / step));
            track.scrollBy({ left: direction * visible * step, behavior: 'smooth' });
        }

        if (prev) {
            prev.addEventListener('click', function () { page(-1); });
        }

        if (next) {
            next.addEventListener('click', function () { page(1); });
        }

        // --- Mouse drag-to-scroll ---
        var isDown = false;
        var moved = false;
        var startX = 0;
        var startScroll = 0;
        var settleTimer = null;

        // Links and images are natively draggable, so a drag started on a card's image,
        // title or button would turn into browser drag-and-drop instead of scrolling
        // (that's why dragging only "worked" on the empty parts of a card). The carousel
        // has no use for drag-and-drop — turn it off wholesale.
        track.addEventListener('dragstart', function (e) {
            e.preventDefault();
        });

        // Scroll offset of the card edge nearest to the current position (clamped to the
        // scrollable range, so the last cards don't produce an unreachable target).
        function nearestCardOffset() {
            var items = track.querySelectorAll('ul.products > li.product, ul.products > li');
            if (!items.length) {
                return null;
            }
            var trackLeft = track.getBoundingClientRect().left;
            var maxScroll = track.scrollWidth - track.clientWidth;
            var best = null;
            var bestDist = Infinity;
            for (var i = 0; i < items.length; i++) {
                var offset = items[i].getBoundingClientRect().left - trackLeft + track.scrollLeft;
                offset = Math.min(Math.max(offset, 0), maxScroll);
                var dist = Math.abs(offset - track.scrollLeft);
                if (dist < bestDist) {
                    bestDist = dist;
                    best = offset;
                }
            }
            return best;
        }

        function finishSettle() {
            if (settleTimer) {
                clearInterval(settleTimer);
                settleTimer = null;
            }
            track.removeEventListener('scrollend', finishSettle);
            track.classList.remove('is-settling');
        }

        // Re-enable snap once the release animation has come to rest. scrollend where the
        // browser has it; elsewhere, "no scroll movement for two ticks" is close enough.
        function watchSettle() {
            if ('onscrollend' in track) {
                track.addEventListener('scrollend', finishSettle, { once: true });
                return;
            }
            var last = track.scrollLeft;
            settleTimer = setInterval(function () {
                if (track.scrollLeft === last) {
                    finishSettle();
                    return;
                }
                last = track.scrollLeft;
            }, 100);
        }

        track.addEventListener('pointerdown', function (e) {
            // Mouse only (left button) — let touch/pen use native scrolling.
            if (e.pointerType && e.pointerType !== 'mouse') {
                return;
            }
            if (e.button !== 0) {
                return;
            }
            isDown = true;
            moved = false;
            startX = e.clientX;
            startScroll = track.scrollLeft;
            finishSettle(); // grabbing mid-animation starts a fresh drag from wherever we are
        });

        track.addEventListener('pointermove', function (e) {
            if (!isDown) {
                return;
            }
            var dx = e.clientX - startX;
            if (!moved) {
                // Below the threshold this is still a click — don't scroll yet. (Scrolling
                // on sub-threshold jitter shifted the page under the cursor and made
                // ordinary clicks land wrong or die.)
                if (Math.abs(dx) <= DRAG_THRESHOLD) {
                    return;
                }
                moved = true;
                track.classList.add('is-dragging');
                if (track.setPointerCapture) {
                    track.setPointerCapture(e.pointerId);
                }
            }
            track.scrollLeft = startScroll - dx;
        });

        function endDrag() {
            if (!isDown) {
                return;
            }
            isDown = false;
            if (!moved) {
                return; // plain click — snap was never disabled, nothing to restore
            }
            track.classList.remove('is-dragging');
            // Snap to the nearest card ourselves with a smooth scroll, keeping CSS snap
            // off (.is-settling) until we're done. Just re-enabling mandatory snap here
            // would make the browser jump instantly to whatever it considers nearest —
            // the "random teleport" feeling on release.
            track.classList.add('is-settling');
            var target = nearestCardOffset();
            if (target === null || Math.abs(target - track.scrollLeft) < 1) {
                finishSettle();
                return;
            }
            track.scrollTo({ left: target, behavior: 'smooth' });
            watchSettle();
        }

        track.addEventListener('pointerup', endDrag);
        track.addEventListener('pointercancel', endDrag);

        // Swallow the click that follows a drag, so dragging over a product link doesn't
        // navigate. Capture phase: it must win over every handler inside the card.
        track.addEventListener('click', function (e) {
            if (moved) {
                e.preventDefault();
                e.stopPropagation();
                moved = false;
            }
        }, true);

        // Whole-card click: a card is one big link to its product. Real interactive
        // elements keep their own behavior — most importantly the add-to-cart anchor,
        // which WooCommerce's ajax handler picks up natively.
        track.addEventListener('click', function (e) {
            if (e.target.closest('a, button, input, select, textarea, label')) {
                return;
            }
            var card = e.target.closest('li.product');
            if (!card || !track.contains(card)) {
                return;
            }
            var link = card.querySelector('a[href]:not(.add_to_cart_button)');
            if (link) {
                link.click(); // delegate to the real product link so target/rel are honored
            }
        });
    }

    function init() {
        document.querySelectorAll('.gpwc-carousel').forEach(initCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
