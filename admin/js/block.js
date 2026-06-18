(function (blocks, blockEditor, components, element, apiFetch) {
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var createElement = element.createElement;

    registerBlockType('custom/gallery-block', {
        title: 'Spartan Gallery Block',
        icon: 'format-gallery',
        category: 'common',

        attributes: {
            galleryId: {
                type: 'integer',
                default: 0,
            },
        },

        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var galleryId = attributes.galleryId;
            var [galleries, setGalleries] = wp.element.useState([]);
            var [loading, setLoading] = wp.element.useState(true);

            // Fetch galleries using the REST API
            wp.element.useEffect(() => {
                apiFetch({ path: '/wp/v2/spartan_gallery_type' })
                    .then(function (response) {
                        setGalleries(response);
                        setLoading(false);
                    })
                    .catch(function (error) {
                        console.error('Error fetching galleries:', error);
                        setLoading(false);
                    });
            }, []);

            // Build the options for SelectControl
            function getGalleryOptions() {
                var options = [{ label: 'Select a Gallery', value: 0 }];
                galleries.forEach(function (gallery) {
                    options.push({
                        label: gallery.title.rendered,
                        value: gallery.id,
                    });
                });
                return options;
            }

            // Render function
            return createElement(
                'div',
                {},
                createElement(
                    InspectorControls,
                    {},
                    createElement(
                        PanelBody,
                        { title: 'Gallery Settings' },
                        loading
                            ? createElement('p', {}, 'Loading galleries...')
                            : createElement(SelectControl, {
                                  label: 'Select a Gallery',
                                  value: galleryId || 0,
                                  options: getGalleryOptions(),
                                  onChange: function (selectedId) {
                                      setAttributes({ galleryId: parseInt(selectedId, 10) });
                                  },
                              })
                    )
                ),
                createElement(
                    'div',
                    { className: 'gallery-block' },
                    galleryId
                        ? createElement('p', {}, 'Gallery ID: ' + galleryId)
                        : createElement('p', {}, 'Please select a gallery.')
                )
            );
        },

        save: function () {
            // Since this block is rendered dynamically, return null
            return null;
        },
    });
})(wp.blocks, wp.blockEditor, wp.components, wp.element, wp.apiFetch);
