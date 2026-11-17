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
	var CFG      = window.spartanGalleryConfig || {};
	var T        = CFG.i18n || {};
	// Set by the block editor glue: render layouts but stay non-interactive.
	var EDITOR   = !! window.spartanGalleryEditor;

	function t( key, fallback ) {
		return T[ key ] || fallback;
	}

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

	function initCards( el ) {
		if ( typeof window.Swiper !== 'function' ) {
			return;
		}
		var wrap = el.parentNode;
		var swiper = new window.Swiper( el, {
			effect: 'cards',
			grabCursor: true,
			autoplay: autoplayOption( el ),
			keyboard: { enabled: true, onlyInViewport: true },
			navigation: {
				nextEl: el.querySelector( '.swiper-button-next' ),
				prevEl: el.querySelector( '.swiper-button-prev' )
			}
		} );
		// Arrows sit outside the narrow card stack.
		wrap.appendChild( el.querySelector( '.swiper-button-prev' ) );
		wrap.appendChild( el.querySelector( '.swiper-button-next' ) );
		clickToFront( el, swiper );
	}

	function initCube( el ) {
		if ( typeof window.Swiper !== 'function' ) {
			return;
		}
		new window.Swiper( el, {
			effect: 'cube',
			grabCursor: true,
			loop: el.querySelectorAll( '.swiper-slide' ).length > 2,
			cubeEffect: { shadow: true, slideShadows: true, shadowOffset: 24, shadowScale: 0.9 },
			autoplay: autoplayOption( el ),
			keyboard: { enabled: true, onlyInViewport: true },
			navigation: navigation( el ),
			pagination: { el: el.querySelector( '.swiper-pagination' ), clickable: true }
		} );
	}

	function initHero( el ) {
		if ( typeof window.Swiper !== 'function' ) {
			return;
		}
		var count = el.querySelectorAll( '.swiper-slide' ).length;
		new window.Swiper( el, {
			effect: 'fade',
			fadeEffect: { crossFade: true },
			speed: reducedMotion() ? 0 : 1200,
			loop: count > 1,
			autoplay: autoplayOption( el ),
			keyboard: { enabled: true, onlyInViewport: true },
			navigation: navigation( el ),
			pagination: { el: el.querySelector( '.swiper-pagination' ), clickable: true, dynamicBullets: count > 10 }
		} );
	}

	/* ---------------------------------------------------------------- */
	/* Film strip + spotlight                                            */
	/* ---------------------------------------------------------------- */

	function initFilmstrip( el ) {
		var track = el.querySelector( '.sg-strip-track' );
		var prev  = el.querySelector( '.sg-strip-prev' );
		var next  = el.querySelector( '.sg-strip-next' );

		function update() {
			var max = track.scrollWidth - track.clientWidth - 2;
			prev.disabled = track.scrollLeft <= 2;
			next.disabled = track.scrollLeft >= max;
			el.classList.toggle( 'is-scrollable', max > 0 );
		}
		function go( dir ) {
			track.scrollBy( { left: dir * track.clientWidth * 0.8, behavior: reducedMotion() ? 'auto' : 'smooth' } );
		}
		prev.addEventListener( 'click', function () {
			go( -1 );
		} );
		next.addEventListener( 'click', function () {
			go( 1 );
		} );
		track.addEventListener( 'scroll', update, { passive: true } );
		window.addEventListener( 'resize', update );
		update();
		// Images without loaded dimensions may change the width once they arrive.
		Array.prototype.forEach.call( track.querySelectorAll( 'img' ), function ( img ) {
			img.addEventListener( 'load', update );
		} );
	}

	function initSpotlight( el ) {
		var panels = el.querySelectorAll( '.sg-spotlight-panel' );
		var thumbs = el.querySelectorAll( '.sg-spotlight-thumb' );
		Array.prototype.forEach.call( thumbs, function ( thumb ) {
			thumb.addEventListener( 'click', function () {
				var index = parseInt( thumb.getAttribute( 'data-index' ), 10 );
				Array.prototype.forEach.call( panels, function ( panel, i ) {
					panel.hidden = i !== index;
				} );
				Array.prototype.forEach.call( thumbs, function ( t2 ) {
					t2.classList.toggle( 'is-active', t2 === thumb );
					t2.setAttribute( 'aria-pressed', t2 === thumb ? 'true' : 'false' );
				} );
			} );
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
	var ICON_SHARE    = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 16a3 3 0 0 0-2.4 1.2l-6.7-3.4a3 3 0 0 0 0-1.6l6.7-3.4A3 3 0 1 0 15 7l-6.7 3.4a3 3 0 1 0 0 3.2L15 17a3 3 0 1 0 3-1z"/></svg>';

	/** Page URL that opens this gallery's lightbox at the given image. */
	function deepLink( galleryId, index ) {
		return location.href.split( '#' )[ 0 ] + '#' + galleryId + '/' + ( index + 1 );
	}

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
		var protect = gallery.hasAttribute( 'data-sg-protect' );
		var opts = {
			thumbs: gallery.getAttribute( 'data-sg-lb-thumbs' ) === '1' && items.length > 1,
			// Offering a download button on a protected gallery makes no sense.
			download: gallery.getAttribute( 'data-sg-lb-download' ) === '1' && ! protect,
			slideshow: gallery.getAttribute( 'data-sg-lb-slideshow' ) === '1' && items.length > 1,
			share: gallery.getAttribute( 'data-sg-lb-share' ) === '1',
			deeplink: gallery.getAttribute( 'data-sg-deeplink' ) === '1' && !! gallery.id,
			watermark: gallery.getAttribute( 'data-sg-watermark' ) || '',
			protect: protect,
			galleryId: gallery.id,
			delay: intAttr( gallery, 'data-sg-delay', 4 ) * 1000
		};

		extras = { nodes: [], timer: null, opts: opts, buttons: [], download: null, play: null, share: null };

		if ( opts.protect ) {
			document.documentElement.classList.add( 'sg-lb-protect' );
		}

		if ( opts.watermark ) {
			var mark = document.createElement( 'div' );
			mark.className = 'sg-lb-watermark';
			mark.setAttribute( 'aria-hidden', 'true' );
			mark.textContent = opts.watermark;
			document.body.appendChild( mark );
			extras.nodes.push( mark );
		}

		if ( opts.download || opts.slideshow || opts.share ) {
			var tools = document.createElement( 'div' );
			tools.className = 'sg-lb-tools';

			if ( opts.download ) {
				var dl = document.createElement( 'a' );
				dl.setAttribute( 'download', '' );
				dl.setAttribute( 'aria-label', t( 'download', 'Download image' ) );
				dl.title = t( 'download', 'Download image' );
				dl.innerHTML = ICON_DOWNLOAD;
				tools.appendChild( dl );
				extras.download = dl;
			}
			if ( opts.slideshow ) {
				var play = document.createElement( 'button' );
				play.type = 'button';
				play.setAttribute( 'aria-label', t( 'play', 'Play slideshow' ) );
				play.title = t( 'play', 'Play slideshow' );
				play.innerHTML = ICON_PLAY;
				play.addEventListener( 'click', toggleSlideshow );
				tools.appendChild( play );
				extras.play = play;
			}
			if ( opts.share ) {
				tools.appendChild( buildShare() );
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
				b.setAttribute( 'aria-label', t( 'image', 'Image %d' ).replace( '%d', i + 1 ) );
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

	function buildShare() {
		var wrap = document.createElement( 'div' );
		wrap.className = 'sg-lb-share';

		var toggle = document.createElement( 'button' );
		toggle.type = 'button';
		toggle.setAttribute( 'aria-label', t( 'share', 'Share' ) );
		toggle.setAttribute( 'aria-expanded', 'false' );
		toggle.title = t( 'share', 'Share' );
		toggle.innerHTML = ICON_SHARE;

		var menu = document.createElement( 'div' );
		menu.className = 'sg-lb-share-menu';
		menu.hidden = true;

		var targets = [
			[ 'facebook', 'Facebook' ],
			[ 'x', 'X' ],
			[ 'pinterest', 'Pinterest' ],
			[ 'copy', t( 'copyLink', 'Copy link' ) ]
		];
		var links = {};
		targets.forEach( function ( target ) {
			var node = document.createElement( 'copy' === target[ 0 ] ? 'button' : 'a' );
			node.textContent = target[ 1 ];
			if ( 'copy' === target[ 0 ] ) {
				node.type = 'button';
				node.addEventListener( 'click', function () {
					var url = extras && extras.shareUrl;
					if ( ! url ) {
						return;
					}
					var done = function () {
						node.textContent = t( 'copied', 'Link copied' );
						setTimeout( function () {
							node.textContent = t( 'copyLink', 'Copy link' );
						}, 1500 );
					};
					if ( navigator.clipboard && window.isSecureContext ) {
						navigator.clipboard.writeText( url ).then( done );
					} else {
						window.prompt( t( 'copyLink', 'Copy link' ), url ); // eslint-disable-line no-alert
					}
				} );
			} else {
				node.target = '_blank';
				node.rel = 'noopener noreferrer';
			}
			links[ target[ 0 ] ] = node;
			menu.appendChild( node );
		} );

		toggle.addEventListener( 'click', function () {
			menu.hidden = ! menu.hidden;
			toggle.setAttribute( 'aria-expanded', menu.hidden ? 'false' : 'true' );
		} );

		wrap.appendChild( toggle );
		wrap.appendChild( menu );
		extras.share = links;
		return wrap;
	}

	function currentUrl( index ) {
		var o = extras.opts;
		return o.galleryId ? deepLink( o.galleryId, index ) : location.href.split( '#' )[ 0 ];
	}

	function syncExtras( index ) {
		if ( ! extras ) {
			return;
		}
		fitThumbSpace();
		var items = lightbox.elements || [];
		var item  = items[ index ];
		var isVideo = item && item.node && item.node.classList.contains( 'sg-has-video' );

		if ( extras.download ) {
			extras.download.hidden = ! item || isVideo;
			if ( item && ! isVideo ) {
				extras.download.href = item.href;
			}
		}

		if ( extras.opts.deeplink && window.history && history.replaceState ) {
			history.replaceState( null, '', '#' + extras.opts.galleryId + '/' + ( index + 1 ) );
		}

		if ( extras.share && item ) {
			var url   = currentUrl( index );
			var image = isVideo ? ( item.node.getAttribute( 'data-sg-thumb' ) || '' ) : new URL( item.href, location.href ).href;
			var title = document.title;
			extras.shareUrl = url;
			extras.share.facebook.href  = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent( url );
			extras.share.x.href         = 'https://x.com/intent/post?url=' + encodeURIComponent( url ) + '&text=' + encodeURIComponent( title );
			extras.share.pinterest.href = 'https://www.pinterest.com/pin/create/button/?url=' + encodeURIComponent( url ) + '&media=' + encodeURIComponent( image ) + '&description=' + encodeURIComponent( title );
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
			extras.play.setAttribute( 'aria-label', t( 'play', 'Play slideshow' ) );
			extras.play.title = t( 'play', 'Play slideshow' );
		} else {
			extras.timer = setInterval( function () {
				lightbox.nextSlide();
			}, extras.opts.delay );
			extras.play.innerHTML = ICON_PAUSE;
			extras.play.setAttribute( 'aria-label', t( 'pause', 'Pause slideshow' ) );
			extras.play.title = t( 'pause', 'Pause slideshow' );
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
		document.documentElement.classList.remove( 'sg-lb-has-thumbs', 'sg-lb-protect' );
		if ( extras.opts.deeplink && window.history && history.replaceState && /^#sg-/.test( location.hash ) ) {
			history.replaceState( null, '', location.href.split( '#' )[ 0 ] );
		}
		extras = null;
	}

	/** #sg-59-1/4 opens gallery sg-59-1 at its 4th image. */
	function openFromHash() {
		var m = /^#(sg-\d+-\d+)\/(\d+)$/.exec( location.hash );
		if ( ! m || ! lightbox ) {
			return;
		}
		var gallery = document.getElementById( m[ 1 ] );
		if ( ! gallery || gallery.getAttribute( 'data-sg-deeplink' ) !== '1' ) {
			return;
		}
		var links = gallery.querySelectorAll( 'a.sg-lightbox' );
		var link  = links[ parseInt( m[ 2 ], 10 ) - 1 ];
		if ( link ) {
			lightbox.open( link );
		}
	}

	/** Right-click / drag protection for protected galleries and their lightbox. */
	function protectHandler( e ) {
		var target = e.target;
		if ( ! target || ! target.closest ) {
			return;
		}
		if ( target.closest( '.sg-protect img, .sg-protect .sg-link' ) || ( document.documentElement.classList.contains( 'sg-lb-protect' ) && target.closest( '.glightbox-container .gslide-media' ) ) ) {
			e.preventDefault();
		}
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
		var plyr = CFG.plyr || {};
		lightbox = window.GLightbox( {
			selector: '.sg-lightbox',
			touchNavigation: true,
			loop: true,
			descPosition: 'bottom',
			autoplayVideos: true,
			// Bundled copy of Plyr (no third-party CDN); loaded only when a video opens.
			plyr: {
				css: plyr.css || '',
				js: plyr.js || '',
				config: {
					ratio: '16:9',
					iconUrl: plyr.icon || '',
					blankVideo: '',
					fullscreen: { enabled: true, iosNative: true },
					youtube: { noCookie: true, rel: 0, showinfo: 0, iv_load_policy: 3 },
					vimeo: { byline: false, portrait: false, title: false, transparent: false }
				}
			}
		} );
		lightbox.on( 'open', openExtras );
		lightbox.on( 'slide_changed', function ( data ) {
			syncExtras( data && data.current ? data.current.index : lightbox.getActiveSlideIndex() );
		} );
		lightbox.on( 'slide_after_load', fitThumbSpace );
		lightbox.on( 'close', closeExtras );
	}

	/* ---------------------------------------------------------------- */

	var booted = false;

	function init() {
		each( '[data-sg-masonry]', initMasonry );
		each( '[data-sg-slider]', initSlider );
		each( '[data-sg-carousel]', initCarousel );
		each( '[data-sg-slideshow]', initSlideshow );
		each( '[data-sg-cards]', initCards );
		each( '[data-sg-cube]', initCube );
		each( '[data-sg-hero]', initHero );
		each( '[data-sg-filmstrip]', initFilmstrip );
		each( '[data-sg-spotlight]', initSpotlight );
		each( '[data-sg-compare]', initCompare );
		if ( EDITOR ) {
			return;
		}
		initLightbox();
		each( '[data-sg-filterable], [data-sg-per-page]', initCollection );

		if ( ! booted ) {
			booted = true;
			document.addEventListener( 'contextmenu', protectHandler );
			document.addEventListener( 'dragstart', protectHandler );
			openFromHash();
		}
	}

	// Allow themes/page builders that inject galleries later to re-run this.
	window.SpartanGallery = { init: init };

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
