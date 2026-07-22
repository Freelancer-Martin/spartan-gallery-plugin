( function ( $ ) {
	'use strict';

	$( function () {

		// Left-hand icon tab navigation (Redux-style section switcher).
		$( '.sg-options-nav-item' ).on( 'click', function () {
			var panelId = $( this ).data( 'panel' );

			$( '.sg-options-nav-item' ).removeClass( 'active' );
			$( this ).addClass( 'active' );

			$( '.sg-options-panel' ).removeClass( 'active' );
			var $panel = $( '.sg-options-panel[data-panel="' + panelId + '"]' ).addClass( 'active' );

			// jQuery UI sliders measure their track width when they're initialized.
			// Sliders that live in a tab other than the default one are initialized
			// while their panel is still display:none, so they measure a width of 0
			// and can't translate drags into values. Re-measure now that the panel
			// is actually visible.
			$panel.find( '.sg-slider.ui-slider' ).each( function () {
				var $s = $( this );
				$s.slider( 'option', 'value', $s.slider( 'value' ) );
			} );
		} );

		// Color pickers.
		$( '.sg-color-field' ).wpColorPicker();

		// Sliders (image width/height, textbox height).
		$( '.sg-slider' ).each( function () {
			var $slider = $( this );
			var $input  = $( '#' + $slider.data( 'target' ) );

			$slider.slider( {
				min: parseInt( $slider.data( 'min' ), 10 ),
				max: parseInt( $slider.data( 'max' ), 10 ),
				step: parseInt( $slider.data( 'step' ), 10 ),
				value: parseInt( $input.val(), 10 ) || 0,
				slide: function ( event, ui ) {
					$input.val( ui.value );
				}
			} );
		} );

		// Spinner (image margin).
		$( '.sg-spinner' ).each( function () {
			var $spinner = $( this );
			$spinner.spinner( {
				min: parseInt( $spinner.data( 'min' ), 10 ),
				max: parseInt( $spinner.data( 'max' ), 10 ),
				step: parseInt( $spinner.data( 'step' ), 10 )
			} );
		} );

		// Layout image-select visual state.
		$( '.sg-image-select-option input[type="radio"]' ).on( 'change', function () {
			$( '.sg-image-select-option' ).removeClass( 'selected' );
			$( this ).closest( '.sg-image-select-option' ).addClass( 'selected' );
		} );

		// CSS code editor.
		if ( window.wp && wp.codeEditor && window.sgOptionsData && sgOptionsData.codeEditor ) {
			wp.codeEditor.initialize( 'add_extra_css', sgOptionsData.codeEditor );
		}

		// Gallery media picker.
		var mediaFrame;
		var $hiddenInput = $( '#spartan_gallery_uploaded_images' );
		var $preview      = $( '#sg-gallery-preview' );

		function renderPreview( ids ) {
			$preview.empty();
			if ( ! ids.length ) {
				return;
			}
			wp.media.attachment( ids[ 0 ] ); // Ensure model cache primed.
			ids.forEach( function ( id ) {
				var attachment = wp.media.attachment( id );
				attachment.fetch();
				var url = attachment.get( 'url' );
				var $thumb = $( '<span class="sg-gallery-thumb" data-id="' + id + '"><img /><a href="#" class="sg-gallery-remove" title="Remove">&times;</a></span>' );
				$thumb.find( 'img' ).attr( 'src', ( attachment.get( 'sizes' ) && attachment.get( 'sizes' ).thumbnail ) ? attachment.get( 'sizes' ).thumbnail.url : url );
				$preview.append( $thumb );
			} );
		}

		$( '#sg-gallery-select' ).on( 'click', function ( e ) {
			e.preventDefault();

			if ( mediaFrame ) {
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media( {
				title: ( window.sgOptionsData && sgOptionsData.mediaTitle ) || 'Select gallery images',
				button: { text: ( window.sgOptionsData && sgOptionsData.mediaButton ) || 'Use selected images' },
				multiple: 'add',
				library: { type: 'image' }
			} );

			mediaFrame.on( 'open', function () {
				var selection = mediaFrame.state().get( 'selection' );
				var currentIds = $hiddenInput.val().split( ',' ).filter( Boolean );
				currentIds.forEach( function ( id ) {
					var attachment = wp.media.attachment( id );
					attachment.fetch();
					selection.add( attachment ? [ attachment ] : [] );
				} );
			} );

			mediaFrame.on( 'select', function () {
				var selection = mediaFrame.state().get( 'selection' );
				var ids = [];
				selection.each( function ( attachment ) {
					ids.push( attachment.get( 'id' ) );
				} );
				$hiddenInput.val( ids.join( ',' ) );
				renderPreview( ids );
			} );

			mediaFrame.open();
		} );

		$preview.on( 'click', '.sg-gallery-remove', function ( e ) {
			e.preventDefault();
			var $thumb = $( this ).closest( '.sg-gallery-thumb' );
			var removeId = String( $thumb.data( 'id' ) );
			var ids = $hiddenInput.val().split( ',' ).filter( function ( id ) {
				return id && id !== removeId;
			} );
			$hiddenInput.val( ids.join( ',' ) );
			$thumb.remove();
		} );

	} );

} )( jQuery );
