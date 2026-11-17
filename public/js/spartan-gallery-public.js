/**
 * Spartan Gallery front-end.
 *
 * Vanilla JS, no jQuery. Initializes whichever libraries the rendered
 * galleries need (each is only enqueued when a gallery uses it):
 *   - GLightbox  (lightbox, all layouts)
 *   - Masonry    (masonry layout, shipped with WordPress core)
 *   - Swiper     (3D slider layout)
 *
 * Runs on DOMContentLoaded, by which point every footer script has executed,
 * so the order in which WordPress printed the libraries doesn't matter.
 */
( function () {
	'use strict';

	var lightbox = null;

	function initLightbox() {
		if ( typeof window.GLightbox !== 'function' || ! document.querySelector( '.sg-lightbox' ) ) {
			return;
		}
		if ( lightbox ) {
			lightbox.reload();
			return;
		}
		// One instance for the whole page; data-gallery keeps each gallery's
		// images in their own group.
		lightbox = window.GLightbox( {
			selector: '.sg-lightbox',
			touchNavigation: true,
			loop: true,
			descPosition: 'bottom'
		} );
	}

	function initMasonry( el ) {
		if ( typeof window.Masonry !== 'function' ) {
			return;
		}
		var msnry = new window.Masonry( el, {
			itemSelector: '.sg-item',
			columnWidth: '.sg-masonry-sizer',
			gutter: parseInt( el.getAttribute( 'data-sg-gutter' ), 10 ) || 0,
			percentPosition: true,
			transitionDuration: 0
		} );
		el.classList.add( 'is-ready' );

		// Images carry width/height attributes so the layout is right before
		// they load, but re-pack as they arrive in case a theme overrides that.
		if ( typeof window.imagesLoaded === 'function' ) {
			window.imagesLoaded( el ).on( 'progress', function () {
				msnry.layout();
			} );
		}
	}

	function initSlider( el ) {
		if ( typeof window.Swiper !== 'function' ) {
			return;
		}
		var count = el.querySelectorAll( '.swiper-slide' ).length;

		var swiper = new window.Swiper( el, {
			effect: 'coverflow',
			centeredSlides: true,
			slidesPerView: 'auto',
			initialSlide: Math.floor( count / 2 ),
			grabCursor: true,
			coverflowEffect: {
				rotate: 30,
				stretch: 0,
				depth: 160,
				modifier: 1,
				slideShadows: true
			},
			keyboard: { enabled: true, onlyInViewport: true },
			navigation: {
				nextEl: el.querySelector( '.swiper-button-next' ),
				prevEl: el.querySelector( '.swiper-button-prev' )
			},
			pagination: {
				el: el.querySelector( '.swiper-pagination' ),
				clickable: true
			}
		} );

		// Clicking a side slide brings it to the front; only the centered one
		// opens the lightbox. Capture phase runs before GLightbox's handler.
		el.addEventListener( 'click', function ( e ) {
			var slide = e.target.closest( '.swiper-slide' );
			if ( ! slide || slide.classList.contains( 'swiper-slide-active' ) ) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			swiper.slideTo( Array.prototype.indexOf.call( swiper.slides, slide ) );
		}, true );
	}

	function each( selector, fn ) {
		var nodes = document.querySelectorAll( selector );
		for ( var i = 0; i < nodes.length; i++ ) {
			if ( ! nodes[ i ].hasAttribute( 'data-sg-init' ) ) {
				nodes[ i ].setAttribute( 'data-sg-init', '' );
				fn( nodes[ i ] );
			}
		}
	}

	function init() {
		each( '[data-sg-masonry]', initMasonry );
		each( '[data-sg-slider]', initSlider );
		initLightbox();
	}

	// Allow themes/page builders that inject galleries later to re-run this.
	window.SpartanGallery = { init: init };

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
