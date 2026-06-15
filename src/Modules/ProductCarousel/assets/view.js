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

        if (prev) {
            prev.addEventListener('click', function () {
                track.scrollBy({ left: -track.clientWidth, behavior: 'smooth' });
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                track.scrollBy({ left: track.clientWidth, behavior: 'smooth' });
            });
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
