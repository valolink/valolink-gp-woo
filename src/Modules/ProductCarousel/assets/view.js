/*
 * Product Carousel — arrow scrolling. Each .gpwc-carousel is wired independently, so multiple
 * carousels can coexist on one page. Arrows scroll the track by one viewport width.
 */
(function () {
    function initCarousel(carousel) {
        var track = carousel.querySelector('.gpwc-carousel__track');
        if (!track) {
            return;
        }

        var prev = carousel.querySelector('.gpwc-carousel__arrow--prev');
        var next = carousel.querySelector('.gpwc-carousel__arrow--next');

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
