/**
 * Spartan Gallery blocks: "Spartan Gallery Block" and "Spartan Album".
 *
 * Both are rendered by PHP (render_callback); in the editor they show a
 * live server-side preview of the real gallery.
 */
( function ( blocks, blockEditor, components, element, apiFetch, i18n, serverSideRender ) {
	var registerBlockType = blocks.registerBlockType;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps     = blockEditor.useBlockProps;
	var PanelBody         = components.PanelBody;
	var SelectControl     = components.SelectControl;
	var Placeholder       = components.Placeholder;
	var Spinner           = components.Spinner;
	var el                = element.createElement;
	var useState          = element.useState;
	var useEffect         = element.useEffect;
	var __                = i18n.__;
	var ServerSideRender  = serverSideRender;

	var data = window.sgBlockData || { layouts: [] };

	/** Published + draft galleries, fetched once and shared by both blocks. */
	var galleryRequest = null;
	function useGalleries() {
		var state = useState( null );
		var galleries = state[ 0 ];
		var setGalleries = state[ 1 ];
		useEffect( function () {
			if ( ! galleryRequest ) {
				galleryRequest = apiFetch( { path: '/wp/v2/spartan_gallery_type?per_page=100&status=publish,draft,private&_fields=id,title,status' } )
					.catch( function () {
						return apiFetch( { path: '/wp/v2/spartan_gallery_type?per_page=100&_fields=id,title' } );
					} );
			}
			galleryRequest.then( setGalleries ).catch( function () {
				setGalleries( [] );
			} );
		}, [] );
		return galleries;
	}

	function galleryLabel( g ) {
		var title = ( g.title && g.title.rendered ) || ( '#' + g.id );
		return g.status && 'publish' !== g.status ? title + ' (' + g.status + ')' : title;
	}

	function preview( blockName, attributes, emptyLabel ) {
		return el( ServerSideRender, {
			block: blockName,
			attributes: attributes,
			LoadingResponsePlaceholder: function () {
				return el( Placeholder, { icon: 'format-gallery', label: __( 'Loading gallery…', 'spartan-gallery' ) }, el( Spinner ) );
			},
			EmptyResponsePlaceholder: function () {
				return el( Placeholder, { icon: 'format-gallery', label: emptyLabel } );
			}
		} );
	}

	registerBlockType( 'custom/gallery-block', {
		apiVersion: 2,
		title: __( 'Spartan Gallery Block', 'spartan-gallery' ),
		description: __( 'Show one of your Spartan Galleries.', 'spartan-gallery' ),
		icon: 'format-gallery',
		category: 'media',
		keywords: [ 'gallery', 'photos', 'images', 'lightbox' ],

		attributes: {
			galleryId: { type: 'integer', default: 0 },
			layout: { type: 'string', default: '' }
		},

		edit: function ( props ) {
			var a = props.attributes;
			var setAttributes = props.setAttributes;
			var galleries = useGalleries();
			var blockProps = useBlockProps();

			var galleryOptions = [ { label: __( 'Select a gallery', 'spartan-gallery' ), value: 0 } ].concat(
				( galleries || [] ).map( function ( g ) {
					return { label: galleryLabel( g ), value: g.id };
				} )
			);
			var layoutOptions = [ { label: __( 'Gallery default', 'spartan-gallery' ), value: '' } ].concat( data.layouts );

			var picker = galleries === null
				? el( Spinner )
				: el( SelectControl, {
					label: __( 'Gallery', 'spartan-gallery' ),
					value: a.galleryId || 0,
					options: galleryOptions,
					onChange: function ( v ) {
						setAttributes( { galleryId: parseInt( v, 10 ) || 0 } );
					},
					__nextHasNoMarginBottom: true
				} );

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Gallery Settings', 'spartan-gallery' ) },
						picker,
						el( SelectControl, {
							label: __( 'Layout', 'spartan-gallery' ),
							help: __( 'Overrides the layout chosen in the gallery, for this block only.', 'spartan-gallery' ),
							value: a.layout,
							options: layoutOptions,
							onChange: function ( v ) {
								setAttributes( { layout: v } );
							},
							__nextHasNoMarginBottom: true
						} ),
						a.galleryId ? el(
							'p',
							{},
							el( 'a', { href: data.editUrl + a.galleryId, target: '_blank', rel: 'noopener noreferrer' }, __( 'Edit this gallery', 'spartan-gallery' ) )
						) : null
					)
				),
				a.galleryId
					? preview( 'custom/gallery-block', a, __( 'This gallery has no images yet.', 'spartan-gallery' ) )
					: el(
						Placeholder,
						{ icon: 'format-gallery', label: __( 'Spartan Gallery', 'spartan-gallery' ), instructions: __( 'Choose which gallery to show.', 'spartan-gallery' ) },
						picker
					)
			);
		},

		save: function () {
			return null;
		}
	} );

	registerBlockType( 'custom/album-block', {
		apiVersion: 2,
		title: __( 'Spartan Album', 'spartan-gallery' ),
		description: __( 'Gallery covers; clicking one opens that gallery in a lightbox.', 'spartan-gallery' ),
		icon: 'images-alt2',
		category: 'media',
		keywords: [ 'album', 'gallery', 'portfolio' ],

		attributes: {
			galleryIds: { type: 'array', items: { type: 'integer' }, default: [] },
			columns: { type: 'integer', default: 3 },
			showTitles: { type: 'boolean', default: true }
		},

		edit: function ( props ) {
			var a = props.attributes;
			var setAttributes = props.setAttributes;
			var galleries = useGalleries();
			var blockProps = useBlockProps();

			function toggleGallery( id, checked ) {
				var ids = a.galleryIds.filter( function ( x ) {
					return x !== id;
				} );
				if ( checked ) {
					ids.push( id );
				}
				setAttributes( { galleryIds: ids } );
			}

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Galleries', 'spartan-gallery' ) },
						el( 'p', {}, __( 'Leave all unchecked to show every published gallery.', 'spartan-gallery' ) ),
						galleries === null
							? el( Spinner )
							: galleries.map( function ( g ) {
								return el( components.CheckboxControl, {
									key: g.id,
									label: galleryLabel( g ),
									checked: a.galleryIds.indexOf( g.id ) !== -1,
									onChange: function ( checked ) {
										toggleGallery( g.id, checked );
									},
									__nextHasNoMarginBottom: true
								} );
							} )
					),
					el(
						PanelBody,
						{ title: __( 'Layout', 'spartan-gallery' ) },
						el( components.RangeControl, {
							label: __( 'Columns', 'spartan-gallery' ),
							min: 1,
							max: 6,
							value: a.columns,
							onChange: function ( v ) {
								setAttributes( { columns: v || 3 } );
							},
							__nextHasNoMarginBottom: true
						} ),
						el( components.ToggleControl, {
							label: __( 'Show gallery titles', 'spartan-gallery' ),
							checked: a.showTitles,
							onChange: function ( v ) {
								setAttributes( { showTitles: v } );
							},
							__nextHasNoMarginBottom: true
						} )
					)
				),
				preview( 'custom/album-block', a, __( 'No published galleries with images yet.', 'spartan-gallery' ) )
			);
		},

		save: function () {
			return null;
		}
	} );
} )( wp.blocks, wp.blockEditor, wp.components, wp.element, wp.apiFetch, wp.i18n, wp.serverSideRender );
