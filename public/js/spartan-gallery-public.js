/**
 * Spartan Gallery front-end.
 *
 * Vanilla JS, no jQuery. Initializes whichever libraries the rendered
 * galleries need (each is only enqueued when a gallery uses it):
 *   - GLightbox  (lightbox, all layouts except before/after)
 *   - Masonry    (masonry layout, shipped with WordPress core)
 *   - Swiper     (3D slider, carousel, slideshow)
 *
 * Runs on DOMContentLoaded, by which point every footer script has executed,
 * so the order in which WordPress printed the libraries doesn't matter.
 */
( function () {
	'use strict';

	var lightbox = null;

	/* ---------------------------------------------------------------- */
	/* Helpers                                                           */
	/* ---------------------------------------------------------------- */

	function each( selector, fn ) {
		var nodes = document.querySelectorAll( selector );
		for ( var i = 0; i < nodes.length; i++ ) {
			if ( ! nodes[ i ].hasAttribute( 'data-sg-init' ) ) {
				nodes[ i ].setAttribute( 'data-sg-init', '' );
				fn( nodes[ i ] );
			}
		}
	}

	function intAttr( el, name, fallback ) {
		var v = parseInt( el.getAttribute( name ), 10 );
		return isNaN( v ) ? fallback : v;
	}

	function autoplayOption( el ) {
		var delay = intAttr( el, 'data-sg-autoplay', 0 );
		return delay > 0 ? { delay: delay, disableOnInteraction: false, pauseOnMouseEnter: true } : false;
	}

	function reducedMotion() {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	/* ---------------------------------------------------------------- */
	/* Masonry                                                           */
	/* ---------------------------------------------------------------- */

	function initMasonry( el ) {
		if ( typeof window.Masonry !== 'function' ) {
			return;
		}
		// Filtered-out / not-yet-loaded tiles are excluded from the packing.
		var msnry = new window.Masonry( el, {
			itemSelector: '.sg-item:not(.sg-filtered-out):not(.sg-paged-out)',
			columnWidth: '.sg-masonry-sizer',
			gutter: intAttr( el, 'data-sg-gutter', 0 ),
			percentPosition: true,
			transitionDuration: 0
		} );
		el.classList.add( 'is-ready' );
		el.sgMasonry = msnry;

		// Images carry width/height attributes so the layout is right before
		// they load, but re-pack as they arrive in case a theme overrides that.
		if ( typeof window.imagesLoaded === 'function' ) {
			window.imagesLoaded( el ).on( 'progress', function () {
				msnry.layout();
			} );
		}
	}

	/* ---------------------------------------------------------------- */
	/* Swiper layouts                                                    */
	/* ---------------------------------------------------------------- */

	function navigation( el ) {
		return {
			nextEl: el.querySelector( ':scope > .swiper-button-next' ),
			prevEl: el.querySelector( ':scope > .swiper-button-prev' )
		};
	}

	/**
	 * Clicking a side slide brings it to the front; only the active one
	 * opens the lightbox. Capture phase runs before GLightbox's handler.
	 */
	function clickToFront( el, swiper ) {
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

	function initSlider( el ) {
		if ( typeof window.Swiper !== 'function' ) {
			return;
		}
		var count  = el.querySelectorAll( '.swiper-slide' ).length;
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
			autoplay: autoplayOption( el ),
			keyboard: { enabled: true, onlyInViewport: true },
			navigation: navigation( el ),
			pagination: {
				el: el.querySelector( '.swiper-pagination' ),
				clickable: true
			}
		} );
		clickToFront( el, swiper );
	}

	function initCarousel( el ) {
		if ( typeof window.Swiper !== 'function' ) {
			return;
		}
		var cols  = intAttr( el, 'data-sg-cols', 3 );
		var gap   = intAttr( el, 'data-sg-gutter', 10 );
		var count = el.querySelectorAll( '.swiper-slide' ).length;

		new window.Swiper( el, {
			slidesPerView: 1,
			spaceBetween: gap,
			grabCursor: true,
			// Looping needs more slides than are visible at once.
			loop: count > cols * 2,
			autoplay: autoplayOption( el ),
			keyboard: { enabled: true, onlyInViewport: true },
			navigation: navigation( el ),
			pagination: {
				el: el.querySelector( '.swiper-pagination' ),
				clickable: true,
				dynamicBullets: count > 10
			},
			breakpoints: {
				601: { slidesPerView: Math.min( cols, 2 ) },
				901: { slidesPerView: Math.min( cols, 3 ) },
				1101: { slidesPerView: cols }
			}
		} );
	}

	function initSlideshow( el ) {
		if ( typeof window.Swiper !== 'function' ) {
			return;
		}
		var main   = el.querySelector( '.sg-slideshow-main' );
		var thumbs = el.querySelector( '.sg-slideshow-thumbs' );

		var thumbSwiper = new window.Swiper( thumbs, {
			slidesPerView: 'auto',
			spaceBetween: 8,
			freeMode: true,
			watchSlidesProgress: true
		} );

		new window.Swiper( main, {
			spaceBetween: 0,
			speed: reducedMotion() ? 0 : 450,
			autoplay: autoplayOption( el ),
			keyboard: { enabled: true, onlyInViewport: true },
			navigation: navigation( main ),
			thumbs: { swiper: thumbSwiper }
		} );
	}

	/* ---------------------------------------------------------------- */
	/* Before / after                                                    */
	/* ---------------------------------------------------------------- */

	function initCompare( el ) {
		var range = el.querySelector( '.sg-compare-range' );
		if ( ! range ) {
			return;
		}
		function update() {
			el.style.setProperty( '--sg-pos', range.value + '%' );
		}
		range.addEventListener( 'input', update );
		update();
	}

	/* ---------------------------------------------------------------- */
	/* Filtering + load more                                             */
	/* ---------------------------------------------------------------- */

	function initCollection( gallery ) {
		var container = gallery.querySelector( '.sg-grid, .sg-masonry, .sg-justified, .sg-mosaic, .sg-polaroid' );
		if ( ! container ) {
			return;
		}
		var items    = Array.prototype.slice.call( container.querySelectorAll( ':scope > .sg-item' ) );
		var perPage  = intAttr( gallery, 'data-sg-per-page', 0 );
		var limit    = perPage || Infinity;
		var filter   = '*';
		var moreWrap = gallery.querySelector( '.sg-more-wrap' );
		var buttons  = gallery.querySelectorAll( '.sg-filter' );

		function matches( item ) {
			if ( '*' === filter ) {
				return true;
			}
			return ( ' ' + ( item.getAttribute( 'data-sg-tags' ) || '' ) + ' ' ).indexOf( ' ' + filter + ' ' ) !== -1;
		}

		function apply() {
			var matching = 0;
			items.forEach( function ( item ) {
				var match = matches( item );
				item.classList.toggle( 'sg-filtered-out', ! match );
				item.classList.toggle( 'sg-paged-out', match && matching >= limit );
				if ( match ) {
					matching++;
				}
				// Lightbox browses every image that matches the filter, including
				// ones not loaded into the grid yet.
				var link = item.querySelector( 'a.sg-link' );
				if ( link ) {
					link.classList.toggle( 'sg-lightbox', match );
				}
			} );

			if ( moreWrap ) {
				moreWrap.hidden = matching <= limit;
			}
			if ( container.sgMasonry ) {
				container.sgMasonry.reloadItems();
				container.sgMasonry.layout();
			}
			refreshLightbox();
		}

		Array.prototype.forEach.call( buttons, function ( button ) {
			button.addEventListener( 'click', function () {
				filter = button.getAttribute( 'data-filter' ) || '*';
				limit  = perPage || Infinity;
				Array.prototype.forEach.call( buttons, function ( b ) {
					b.classList.toggle( 'is-active', b === button );
					b.setAttribute( 'aria-pressed', b === button ? 'true' : 'false' );
				} );
				apply();
			} );
		} );

		function loadMore() {
			if ( moreWrap && ! moreWrap.hidden ) {
				limit += perPage;
				apply();
			}
		}

		if ( moreWrap ) {
			moreWrap.querySelector( '.sg-more' ).addEventListener( 'click', function () {
				var firstNew = null;
				loadMore();
				// Move focus to the first newly shown image for keyboard users.
				items.some( function ( item ) {
					if ( ! item.classList.contains( 'sg-paged-out' ) && ! item.classList.contains( 'sg-filtered-out' ) && ! item.hasAttribute( 'data-sg-seen' ) ) {
						firstNew = item;
						return true;
					}
					return false;
				} );
				markSeen();
				if ( firstNew ) {
					var link = firstNew.querySelector( '.sg-link' );
					if ( link && link.focus ) {
						link.focus( { preventScroll: true } );
					}
				}
			} );

			if ( 'scroll' === gallery.getAttribute( 'data-sg-paging' ) && 'IntersectionObserver' in window ) {
				new IntersectionObserver( function ( entries ) {
					if ( entries[ 0 ].isIntersecting ) {
						loadMore();
						markSeen();
					}
				}, { rootMargin: '300px 0px' } ).observe( moreWrap );
			}
		}

		function markSeen() {
			items.forEach( function ( item ) {
				if ( ! item.classList.contains( 'sg-paged-out' ) && ! item.classList.contains( 'sg-filtered-out' ) ) {
					item.setAttribute( 'data-sg-seen', '' );
				}
			} );
		}

		apply();
		markSeen();
	}

	/* ---------------------------------------------------------------- */
	/* Lightbox                                                          */
	/* ---------------------------------------------------------------- */

	var ICON_DOWNLOAD = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 3h2v9.6l3.3-3.3 1.4 1.4L12 16.4 6.3 10.7l1.4-1.4L11 12.6V3zM4 18h16v2H4z"/></svg>';
	var ICON_PLAY     = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
	var ICON_PAUSE    = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>';

	/** Lightbox extras for the gallery that was just opened. */
	var extras = null;

	function openExtras() {
		var items = lightbox.elements || [];
		if ( ! items.length || ! items[ 0 ].node ) {
			return;
		}
		var gallery = items[ 0 ].node.closest( '.sg-gallery' );
		if ( ! gallery ) {
			return;
		}
		var opts = {
			thumbs: gallery.getAttribute( 'data-sg-lb-thumbs' ) === '1' && items.length > 1,
			download: gallery.getAttribute( 'data-sg-lb-download' ) === '1',
			slideshow: gallery.getAttribute( 'data-sg-lb-slideshow' ) === '1' && items.length > 1,
			delay: intAttr( gallery, 'data-sg-delay', 4 ) * 1000
		};
		if ( ! opts.thumbs && ! opts.download && ! opts.slideshow ) {
			return;
		}

		extras = { nodes: [], timer: null, opts: opts, buttons: [], download: null, play: null };

		if ( opts.download || opts.slideshow ) {
			var tools = document.createElement( 'div' );
			tools.className = 'sg-lb-tools';

			if ( opts.download ) {
				var dl = document.createElement( 'a' );
				dl.setAttribute( 'download', '' );
				dl.setAttribute( 'aria-label', 'Download image' );
				dl.title = 'Download';
				dl.innerHTML = ICON_DOWNLOAD;
				tools.appendChild( dl );
				extras.download = dl;
			}
			if ( opts.slideshow ) {
				var play = document.createElement( 'button' );
				play.type = 'button';
				play.setAttribute( 'aria-label', 'Play slideshow' );
				play.innerHTML = ICON_PLAY;
				play.addEventListener( 'click', toggleSlideshow );
				tools.appendChild( play );
				extras.play = play;
			}
			document.body.appendChild( tools );
			extras.nodes.push( tools );
		}

		if ( opts.thumbs ) {
			var strip = document.createElement( 'div' );
			strip.className = 'sg-lb-thumbs';
			items.forEach( function ( item, i ) {
				var img = item.node.querySelector( 'img' );
				var src = item.node.getAttribute( 'data-sg-thumb' ) || ( img ? ( img.currentSrc || img.src ) : item.href );
				var b   = document.createElement( 'button' );
				b.type = 'button';
				b.setAttribute( 'aria-label', 'Image ' + ( i + 1 ) );
				b.style.backgroundImage = 'url("' + String( src ).replace( /"/g, '%22' ) + '")';
				b.addEventListener( 'click', function () {
					lightbox.goToSlide( i );
				} );
				strip.appendChild( b );
				extras.buttons.push( b );
			} );
			document.body.appendChild( strip );
			extras.nodes.push( strip );
			document.documentElement.classList.add( 'sg-lb-has-thumbs' );
		}

		syncExtras( lightbox.getActiveSlideIndex() );
	}

	/**
	 * Keep image + caption above the thumbnail strip. GLightbox writes an
	 * inline max-height of calc(100vh - caption height) on slides with a
	 * caption, which doesn't know about the strip, so extend it here (after
	 * each slide loads and after GLightbox's own resize handling).
	 */
	var THUMB_STRIP = 84;

	function fitThumbSpace() {
		if ( ! extras || ! extras.opts.thumbs || ! lightbox.modal || window.innerWidth < 769 ) {
			return;
		}
		Array.prototype.forEach.call( lightbox.modal.querySelectorAll( '.gslide' ), function ( slide ) {
			var img  = slide.querySelector( '.gslide-image img' );
			var desc = slide.querySelector( '.desc-bottom .gslide-description' );
			if ( img ) {
				img.style.setProperty( 'max-height', 'calc(97vh - ' + ( THUMB_STRIP + ( desc ? desc.offsetHeight : 0 ) ) + 'px)', 'important' );
				// GLightbox sized the caption box to the image's earlier width.
				if ( desc && img.offsetWidth ) {
					desc.style.maxWidth = img.offsetWidth + 'px';
				}
			}
		} );
	}

	window.addEventListener( 'resize', function () {
		if ( extras ) {
			window.requestAnimationFrame( fitThumbSpace );
		}
	} );

	function syncExtras( index ) {
		if ( ! extras ) {
			return;
		}
		fitThumbSpace();
		var items = lightbox.elements || [];
		if ( extras.download && items[ index ] ) {
			extras.download.href = items[ index ].href;
		}
		extras.buttons.forEach( function ( b, i ) {
			b.classList.toggle( 'is-active', i === index );
			if ( i === index && b.scrollIntoView ) {
				b.scrollIntoView( { block: 'nearest', inline: 'center' } );
			}
		} );
	}

	function toggleSlideshow() {
		if ( ! extras ) {
			return;
		}
		if ( extras.timer ) {
			clearInterval( extras.timer );
			extras.timer = null;
			extras.play.innerHTML = ICON_PLAY;
			extras.play.setAttribute( 'aria-label', 'Play slideshow' );
		} else {
			extras.timer = setInterval( function () {
				lightbox.nextSlide();
			}, extras.opts.delay );
			extras.play.innerHTML = ICON_PAUSE;
			extras.play.setAttribute( 'aria-label', 'Pause slideshow' );
		}
	}

	/**
	 * Re-collect lightbox entries after filtering or late-inserted galleries.
	 * GLightbox's own reload() adds another click handler every time it is
	 * called (so images would open twice), so only refresh the element list;
	 * its delegated click handler already matches by selector.
	 */
	function refreshLightbox() {
		if ( lightbox && ! lightbox.lightboxOpen ) {
			lightbox.elements = lightbox.getElements();
		}
	}

	function closeExtras() {
		if ( ! extras ) {
			return;
		}
		if ( extras.timer ) {
			clearInterval( extras.timer );
		}
		extras.nodes.forEach( function ( n ) {
			n.parentNode.removeChild( n );
		} );
		document.documentElement.classList.remove( 'sg-lb-has-thumbs' );
		extras = null;
	}

	function initLightbox() {
		if ( typeof window.GLightbox !== 'function' || ! document.querySelector( '.sg-lightbox' ) ) {
			return;
		}
		if ( lightbox ) {
			refreshLightbox();
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
		lightbox.on( 'open', openExtras );
		lightbox.on( 'slide_changed', function ( data ) {
			syncExtras( data && data.current ? data.current.index : lightbox.getActiveSlideIndex() );
		} );
		lightbox.on( 'slide_after_load', fitThumbSpace );
		lightbox.on( 'close', closeExtras );
	}

	/* ---------------------------------------------------------------- */

	function init() {
		each( '[data-sg-masonry]', initMasonry );
		each( '[data-sg-slider]', initSlider );
		each( '[data-sg-carousel]', initCarousel );
		each( '[data-sg-slideshow]', initSlideshow );
		each( '[data-sg-compare]', initCompare );
		initLightbox();
		each( '[data-sg-filterable], [data-sg-per-page]', initCollection );
	}

	// Allow themes/page builders that inject galleries later to re-run this.
	window.SpartanGallery = { init: init };

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
