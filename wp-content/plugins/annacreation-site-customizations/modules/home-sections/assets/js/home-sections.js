(function () {
	'use strict';

	function getVisibleCards(carousel) {
		return Array.prototype.slice.call(carousel.querySelectorAll('.ac-home-product'));
	}

	function updateArrows(carousel) {
		var viewport = carousel.querySelector('.ac-home-products__viewport');
		var prev = carousel.querySelector('[data-ac-carousel-prev]');
		var next = carousel.querySelector('[data-ac-carousel-next]');
		var visibleCards = getVisibleCards(carousel);

		if (!viewport || !prev || !next) {
			return;
		}

		var maxScroll = Math.max(0, viewport.scrollWidth - viewport.clientWidth - 2);
		var canScroll = maxScroll > 1 && visibleCards.length > 1;

		prev.disabled = !canScroll || viewport.scrollLeft <= 2;
		next.disabled = !canScroll || viewport.scrollLeft >= maxScroll;
	}

	function getScrollAmount(carousel) {
		var viewport = carousel.querySelector('.ac-home-products__viewport');
		var firstCard = getVisibleCards(carousel)[0];

		if (!viewport || !firstCard) {
			return 0;
		}

		var viewportStyles = window.getComputedStyle(carousel.querySelector('.ac-home-products__grid'));
		var gap = parseFloat(viewportStyles.columnGap || viewportStyles.gap || 0) || 0;

		return firstCard.getBoundingClientRect().width + gap;
	}

	function scrollCarousel(carousel, direction) {
		var viewport = carousel.querySelector('.ac-home-products__viewport');
		var amount = getScrollAmount(carousel);

		if (!viewport || !amount) {
			return;
		}

		viewport.scrollBy({
			left: amount * direction,
			behavior: 'smooth'
		});
	}

	function initCarousel(carousel) {
		var viewport = carousel.querySelector('.ac-home-products__viewport');
		var prev = carousel.querySelector('[data-ac-carousel-prev]');
		var next = carousel.querySelector('[data-ac-carousel-next]');

		if (!viewport || !prev || !next) {
			return;
		}

		prev.addEventListener('click', function () {
			scrollCarousel(carousel, -1);
		});

		next.addEventListener('click', function () {
			scrollCarousel(carousel, 1);
		});

		viewport.addEventListener('scroll', function () {
			window.requestAnimationFrame(function () {
				updateArrows(carousel);
			});
		});

		window.addEventListener('resize', function () {
			updateArrows(carousel);
		});

		updateArrows(carousel);
	}

	document.addEventListener('DOMContentLoaded', function () {
		Array.prototype.forEach.call(
			document.querySelectorAll('[data-ac-home-carousel]'),
			initCarousel
		);
	});
})();
