/*
 * Product Carousel — arrow + mouse-drag scrolling. Each .gpwc-carousel is wired independently,
 * so multiple carousels can coexist on one page.
 *
 * Arrows scroll by one viewport width; CSS scroll-snap (x mandatory) then lands a product flush
 * at the left edge. Mouse drag scrolls freely with snap temporarily disabled, snapping on release.
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
            track.classList.add('is-dragging');
            if (track.setPointerCapture) {
                track.setPointerCapture(e.pointerId);
            }
        });

        track.addEventListener('pointermove', function (e) {
            if (!isDown) {
                return;
            }
            var dx = e.clientX - startX;
            if (Math.abs(dx) > DRAG_THRESHOLD) {
                moved = true;
            }
            track.scrollLeft = startScroll - dx;
        });

        function endDrag() {
            if (!isDown) {
                return;
            }
            isDown = false;
            // Removing the class re-enables scroll-snap, which snaps to the nearest product.
            track.classList.remove('is-dragging');
        }

        track.addEventListener('pointerup', endDrag);
        track.addEventListener('pointercancel', endDrag);

        // Swallow the click that follows a drag, so dragging over a product link doesn't navigate.
        track.addEventListener('click', function (e) {
            if (moved) {
                e.preventDefault();
                e.stopPropagation();
                moved = false;
            }
        }, true);
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
