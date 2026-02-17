// Mobile Menu Toggle
const menu = document.getElementById("menu-toggle");
menu.addEventListener("click", () => {
	document.getElementById("navbar").classList.toggle("is-mobile-active");
	menu.classList.toggle("is-active");
	document.getElementById(menu.dataset.target).classList.toggle("is-active");
});

// Scrolling
window.addEventListener("scroll", () => {
	var scrollPosition = window.scrollY;

	if (scrollPosition > 100) {
		document.querySelector("#navbar").classList.add("is-scrolled");
	} else {
		document.querySelector("#navbar").classList.remove("is-scrolled");
	}
});

Array.from(document.querySelectorAll("[data-scroll]")).forEach(element => {
	element.addEventListener("click", event => {
		event.preventDefault();
		scrollTo(
			document.documentElement,
			document.querySelector(event.currentTarget.getAttribute("href"))
				.offsetTop -
				(event.currentTarget.getAttribute("data-scroll-offset") || 0),
			event.currentTarget.getAttribute("data-scroll-duration") || 300
		);
	});
});

function scrollTo(element, destination, duration) {
	if (duration <= 0) {
		return;
	}

	var distance = destination - element.scrollTop;
	var loop = (distance / duration) * 10;

	setTimeout(() => {
		element.scrollTop = element.scrollTop + loop;

		if (element.scrollTop === destination) {
			return;
		}

		scrollTo(element, destination, duration - 10);
	}, 10);
}

// Rellax
window.addEventListener("load", initParallaxBackgrounds);
window.addEventListener("resize", resizeParallaxBackgrounds);

let Rellax = require("rellax");

function initParallaxBackgrounds() {
	resizeParallaxBackgrounds();
	if (document.querySelector(".parallax-background")) {
		new Rellax(".parallax-background", {
			speed: 5,
			center: true,
			callback: repositionParallaxBackgrounds
		});
	}
}

function resizeParallaxBackgrounds() {
	Array.from(document.querySelectorAll(".parallax-background")).forEach(
		image => {
			if (
				image.offsetHeight < window.innerHeight &&
				image.offsetWidth >= window.innerWidth
			) {
				image.classList.add("is-short");
			} else if (
				image.offsetHeight >= window.innerHeight &&
				image.offsetWidth < window.innerWidth
			) {
				image.classList.remove("is-short");
			}
		}
	);
}

function repositionParallaxBackgrounds() {
	Array.from(document.querySelectorAll(".parallax-background")).forEach(
		image => {
			var top = window.scrollY - image.parentElement.offsetTop;
			if (image.hasAttribute("data-parallax-position")) {
				top =
					top +
					(image.parentElement.offsetHeight - image.scrollHeight) *
						image.getAttribute("data-parallax-position") +
					(window.innerHeight - image.scrollHeight) /
						(4 + image.getAttribute("data-parallax-position"));
			} else {
				top = top + (window.innerHeight - image.scrollHeight);
			}
			image.style.top = top + "px";

			image.style.left =
				(window.innerWidth - image.scrollWidth) / 2 + "px";
		}
	);
}

// Glide
import Glide from '@glidejs/glide';

window.addEventListener('load', function () {
	[].forEach.call(document.querySelectorAll('#brides .glide'), glider => {
		new Glide(glider).mount();
	});

	[].forEach.call(document.querySelectorAll('.hotel-main-carousel'), mainCarousel => {
		let hotelId = mainCarousel.getAttribute('data-hotel-id');
		let thumbCarousel = document.querySelector('.hotel-thumb-carousel[data-hotel-id="' + hotelId + '"]');
		let slides = mainCarousel.querySelectorAll('.glide__slide').length;

		if (slides) {
			let mainGlide = new Glide(mainCarousel, {
				type: 'carousel',
				perView: 1,
				gap: 0,
				autoplay: 3000,
				keyboard: false,
				animationDuration: 500,
			});

			mainGlide.mount();

			if (thumbCarousel && slides > 1) {
				let thumbGlide = new Glide(thumbCarousel, {
					type: 'carousel',
					perView: (slides < 6 ? slides : 6),
					gap: 8,
					autoplay: false,
					keyboard: false,
					animationDuration: 300,
					breakpoints: {
						1024: {
							perView: (slides < 5 ? slides : 5)
						},
						768: {
							perView: (slides < 4 ? slides : 4)
						}
					}
				});

				thumbGlide.mount();

				thumbCarousel.querySelectorAll('.hotel-thumbnail').forEach((thumb) => {
					thumb.addEventListener('click', function() {
						let index = parseInt(this.getAttribute('data-index'));
						mainGlide.go('=' + index);
					});
				});

				mainGlide.on(['mount.after', 'run'], () => {
					let activeIndex = mainGlide.index;
					thumbCarousel.querySelectorAll('.glide__slide').forEach((slide, index) => {
						if (index === activeIndex) {
							slide.classList.add('is-active');
						} else {
							slide.classList.remove('is-active');
						}
					});
				});
			}
		}
	});

	[].forEach.call(document.querySelectorAll('.rooms .glide'), glider => {
		let slides = glider.querySelectorAll('.glide__slide').length;
		if (slides) {
			let glide = new Glide(glider, {
				type: 'carousel',
				perView: 1,
				gap: 24,
				autoplay: false,
				keyboard: false,
				animationDuration: 1000,
			});

			glide.mount();
		}
	});

	[].forEach.call(document.querySelectorAll('.room-carousel'), carousel => {
		let slides = carousel.querySelectorAll('.glide__slide').length;
		if (slides) {
			let glide = new Glide(carousel, {
				type: 'carousel',
				perView: 1,
				gap: 0,
				autoplay: false,
				keyboard: true,
				animationDuration: 500,
			});

			glide.mount();
		}
	});

    let banner = document.querySelector('.top-notification');
    let navbar = document.querySelector('#navbar');

    if (banner !== null) {
        let height = banner.getBoundingClientRect().height;

        navbar.style.top = height + 'px';

        document.querySelector('.top-notification > #close').addEventListener('click', () => {
            banner.classList.add('removed');
            banner.addEventListener('transitionend', () => {
                banner.remove();
                navbar.style.top = 0;
            });
        });
    }
});

function toggleGlider(glide, slides) {
	let width = window.innerWidth
		|| document.documentElement.clientWidth
		|| document.body.clientWidth;

	if (
		(width <= 768 && slides <= 1) ||
		(width > 768 && width <= 1024 && slides <= 2) ||
		(width > 1024 && slides <= 4)
	) {
		glide.disable();
	} else if (glide.disabled) {
		glide.enable();
	}
}

// Accordion
document.querySelectorAll('.accordion').forEach(accordion => {
	accordion.querySelector('.accordion-link').addEventListener('click', event => {
		event.preventDefault();

		accordion.classList.toggle('is-active');

		let accordionBody = accordion.querySelector('.accordion-body');

		if (accordion.classList.contains('is-active')) {
			accordionBody.style.height = accordionBody.querySelector('.content').offsetHeight + 'px';
		} else {
			accordionBody.style.removeProperty('height');
		}
	});
});

function initFadeInOnScroll() {
	const fadeElements = document.querySelectorAll('.fade-in, .fade-in-fast, .fade-in-slow');

	if (fadeElements.length === 0) {
		return;
	}

	const observerOptions = {
		root: null,
		rootMargin: '0px',
		threshold: 0.1
	};

	const observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			}
		});
	}, observerOptions);

	fadeElements.forEach(element => {
		observer.observe(element);
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initFadeInOnScroll);
} else {
	initFadeInOnScroll();
}

function initModalStateSync() {
	const root = document.documentElement;

	function updateModalState() {
		const hasActiveModal = document.querySelector('.modal-container.is-active');
		if (hasActiveModal) {
			root.classList.add('has-active-modal');
		} else {
			root.classList.remove('has-active-modal');
		}
	}

	updateModalState();

	const observer = new MutationObserver(mutations => {
		let shouldUpdate = false;

		for (const mutation of mutations) {
			if (
				(mutation.type === 'attributes' &&
					mutation.attributeName === 'class' &&
					mutation.target.classList &&
					mutation.target.classList.contains('modal-container')) ||
				(mutation.type === 'childList' &&
					[...mutation.addedNodes, ...mutation.removedNodes].some(
						node =>
							node.nodeType === 1 &&
							node.classList &&
							node.classList.contains('modal-container')
					))
			) {
				shouldUpdate = true;
				break;
			}
		}

		if (shouldUpdate) {
			updateModalState();
		}
	});

	observer.observe(document.body, {
		attributes: true,
		attributeFilter: ['class'],
		subtree: true,
		childList: true
	});

	window.addEventListener('modal-state:refresh', updateModalState);
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initModalStateSync);
} else {
	initModalStateSync();
}
