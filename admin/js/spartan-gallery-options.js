( function ( $ ) {
	'use strict';

	$( function () {
		var data = window.sgOptionsData || {};
		var $wrap = $( '.sg-options-wrap' );
		if ( ! $wrap.length ) {
			return;
		}

		/* ------------------------------------------------------------ */
		/* Tabs                                                          */
		/* ------------------------------------------------------------ */

		$( '.sg-options-nav-item' ).on( 'click', function () {
			var panelId = $( this ).data( 'panel' );
			$( '.sg-options-nav-item' ).removeClass( 'active' );
			$( this ).addClass( 'active' );
			$( '.sg-options-panel' ).removeClass( 'active' );
			$( '.sg-options-panel[data-panel="' + panelId + '"]' ).addClass( 'active' );
			if ( codeEditor && 'css' === panelId ) {
				codeEditor.codemirror.refresh();
			}
		} );

		/* ------------------------------------------------------------ */
		/* Controls                                                      */
		/* ------------------------------------------------------------ */

		$( '.sg-color-field' ).wpColorPicker( {
			change: function () {
				// Fires before the input's value is updated.
				setTimeout( schedulePreview, 0 );
			},
			clear: function () {
				setTimeout( schedulePreview, 0 );
			}
		} );

		$( '.sg-spinner' ).each( function () {
			var $spinner = $( this );
			$spinner.spinner( {
				min: parseInt( $spinner.data( 'min' ), 10 ),
				max: parseInt( $spinner.data( 'max' ), 10 ),
				step: parseInt( $spinner.data( 'step' ), 10 ) || 1,
				stop: schedulePreview
			} );
		} );

		// Layout picker: highlight + only show settings for the selected layout.
		function toggleLayoutRows() {
			var layout = $( 'input[name="image_layout_select"]:checked' ).val();
			$( '[data-sg-layouts]' ).each( function () {
				var layouts = String( $( this ).data( 'sg-layouts' ) ).split( ' ' );
				$( this ).toggle( layouts.indexOf( layout ) !== -1 );
			} );
		}

		$( 'input[name="image_layout_select"]' ).on( 'change', function () {
			$( '.sg-image-select-option' ).removeClass( 'selected' );
			$( this ).closest( '.sg-image-select-option' ).addClass( 'selected' );
			toggleLayoutRows();
		} );
		toggleLayoutRows();

		var codeEditor = null;
		if ( window.wp && wp.codeEditor && data.codeEditor ) {
			codeEditor = wp.codeEditor.initialize( 'add_extra_css', data.codeEditor );
			codeEditor.codemirror.on( 'change', function () {
				codeEditor.codemirror.save();
				schedulePreview();
			} );
		}

		/* ------------------------------------------------------------ */
		/* Presets                                                       */
		/* ------------------------------------------------------------ */

		function setField( name, value ) {
			var $field = $wrap.find( '[name="' + name + '"]' );
			if ( ! $field.length ) {
				return;
			}
			if ( $field.is( ':radio' ) ) {
				$field.filter( '[value="' + value + '"]' ).prop( 'checked', true ).trigger( 'change' );
			} else if ( $field.is( ':checkbox' ) ) {
				$field.prop( 'checked', !! value );
			} else if ( $field.hasClass( 'sg-color-field' ) ) {
				$field.wpColorPicker( 'color', value );
			} else {
				$field.val( value );
			}
		}

		$( '.sg-preset' ).on( 'click', function () {
			var preset = data.presets && data.presets[ $( this ).data( 'preset' ) ];
			if ( ! preset ) {
				return;
			}
			$.each( preset, setField );
			$( '.sg-preset' ).removeClass( 'button-primary' );
			$( this ).addClass( 'button-primary' );
			schedulePreview();
		} );

		/* ------------------------------------------------------------ */
		/* Shortcode copy                                                */
		/* ------------------------------------------------------------ */

		$( '#sg-copy-shortcode' ).on( 'click', function () {
			var $btn   = $( this );
			var $input = $( '#sg-shortcode' );
			function done() {
				$btn.text( data.copied || 'Copied!' );
				setTimeout( function () {
					$btn.text( data.copy || 'Copy' );
				}, 1500 );
			}
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( $input.val() ).then( done );
			} else {
				$input.trigger( 'select' );
				document.execCommand( 'copy' );
				done();
			}
		} );

		/* ------------------------------------------------------------ */
		/* Images: media picker + drag to reorder                        */
		/* ------------------------------------------------------------ */

		var mediaFrame;
		var $hiddenInput = $( '#spartan_gallery_uploaded_images' );
		var $thumbs      = $( '#sg-gallery-preview' );

		function currentIds() {
			return $hiddenInput.val().split( ',' ).filter( Boolean );
		}

		function syncIdsFromThumbs() {
			var ids = $thumbs.children( '.sg-gallery-thumb' ).map( function () {
				return String( $( this ).data( 'id' ) );
			} ).get();
			$hiddenInput.val( ids.join( ',' ) );
			schedulePreview();
		}

		function thumbHtml( id, url ) {
			var $thumb = $( '<span class="sg-gallery-thumb"><img alt="" /><a href="#" class="sg-gallery-remove">&times;</a></span>' );
			$thumb.attr( 'data-id', id );
			$thumb.find( 'img' ).attr( 'src', url );
			$thumb.find( 'a' ).attr( 'title', data.removeLabel || 'Remove' );
			return $thumb;
		}

		$thumbs.sortable( {
			items: '.sg-gallery-thumb',
			tolerance: 'pointer',
			cursor: 'move',
			update: syncIdsFromThumbs
		} );

		$( '#sg-gallery-select' ).on( 'click', function ( e ) {
			e.preventDefault();

			if ( mediaFrame ) {
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media( {
				title: data.mediaTitle || 'Select gallery images',
				button: { text: data.mediaButton || 'Use selected images' },
				multiple: 'add',
				library: { type: 'image' }
			} );

			mediaFrame.on( 'open', function () {
				var selection = mediaFrame.state().get( 'selection' );
				selection.reset();
				currentIds().forEach( function ( id ) {
					var attachment = wp.media.attachment( id );
					attachment.fetch();
					selection.add( attachment );
				} );
			} );

			mediaFrame.on( 'select', function () {
				// Keep the existing (possibly re-ordered) order; new images go last.
				var selected = {};
				var added    = [];
				mediaFrame.state().get( 'selection' ).each( function ( attachment ) {
					selected[ String( attachment.get( 'id' ) ) ] = attachment;
				} );

				$thumbs.children( '.sg-gallery-thumb' ).each( function () {
					if ( ! selected[ String( $( this ).data( 'id' ) ) ] ) {
						$( this ).remove();
					}
				} );
				var existing = currentIds();
				$.each( selected, function ( id, attachment ) {
					if ( existing.indexOf( id ) === -1 ) {
						added.push( attachment );
					}
				} );
				added.forEach( function ( attachment ) {
					var sizes = attachment.get( 'sizes' );
					var url   = sizes && sizes.thumbnail ? sizes.thumbnail.url : attachment.get( 'url' );
					$thumbs.append( thumbHtml( attachment.get( 'id' ), url ) );
				} );
				syncIdsFromThumbs();
			} );

			mediaFrame.open();
		} );

		$thumbs.on( 'click', '.sg-gallery-remove', function ( e ) {
			e.preventDefault();
			$( this ).closest( '.sg-gallery-thumb' ).remove();
			syncIdsFromThumbs();
		} );

		/* ------------------------------------------------------------ */
		/* Live preview                                                  */
		/* ------------------------------------------------------------ */

		var $frame   = $( '#sg-preview-frame' );
		var $status  = $( '.sg-preview-status' );
		var timer    = null;
		var request  = null;
		var observer = null;

		function fitFrame() {
			var doc = $frame[ 0 ].contentDocument;
			if ( doc && doc.documentElement ) {
				$frame.height( Math.min( 1400, Math.max( 160, doc.documentElement.scrollHeight ) ) );
			}
		}

		function watchFrame() {
			fitFrame();
			var doc = $frame[ 0 ].contentDocument;
			if ( observer ) {
				observer.disconnect();
			}
			if ( window.ResizeObserver && doc && doc.body ) {
				observer = new ResizeObserver( fitFrame );
				observer.observe( doc.body );
			}
		}

		function loadPreview() {
			if ( ! $frame.length ) {
				return;
			}
			if ( request ) {
				request.abort();
			}
			if ( codeEditor ) {
				codeEditor.codemirror.save();
			}
			$status.text( '…' );

			var payload = $( '#post' ).serializeArray();
			payload.push( { name: 'action', value: data.previewAction } );
			payload.push( { name: 'sg_preview_nonce', value: data.previewNonce } );

			request = $.post( data.ajaxUrl, $.param( payload ) )
				.done( function ( res ) {
					if ( ! res || ! res.success ) {
						$status.text( data.previewFailed );
						return;
					}
					$status.text( '' );
					$frame.one( 'load', watchFrame );
					$frame.attr( 'srcdoc', res.data.html );
				} )
				.fail( function ( xhr, textStatus ) {
					if ( 'abort' !== textStatus ) {
						$status.text( data.previewFailed );
					}
				} );
		}

		function schedulePreview() {
			clearTimeout( timer );
			timer = setTimeout( loadPreview, 500 );
		}

		$wrap.on( 'change input', 'input, select, textarea', function () {
			if ( this.id === 'sg-shortcode' ) {
				return;
			}
			schedulePreview();
		} );

		$( '#sg-preview-refresh' ).on( 'click', loadPreview );

		$( '.sg-preview-device' ).on( 'click', function () {
			$( '.sg-preview-device' ).removeClass( 'active' ).attr( 'aria-pressed', 'false' );
			$( this ).addClass( 'active' ).attr( 'aria-pressed', 'true' );
			$( '.sg-preview-stage' ).attr( 'data-device', $( this ).data( 'device' ) );
			setTimeout( fitFrame, 50 );
		} );

		// The first preview is rendered by PHP into srcdoc; just size it.
		if ( $frame.length ) {
			var initialDoc = $frame[ 0 ].contentDocument;
			if ( initialDoc && 'complete' === initialDoc.readyState && initialDoc.body && initialDoc.body.childNodes.length ) {
				watchFrame();
			} else {
				$frame.one( 'load', watchFrame );
			}
		}
	} );

} )( jQuery );
