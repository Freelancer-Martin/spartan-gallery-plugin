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
    registerBlockType('custom/album-block', {
        title: 'Spartan Album',
        description: 'Gallery covers; clicking one opens that gallery in a lightbox.',
        icon: 'images-alt2',
        category: 'common',

        attributes: {
            galleryIds: { type: 'array', items: { type: 'integer' }, default: [] },
            columns: { type: 'integer', default: 3 },
            showTitles: { type: 'boolean', default: true },
        },

        edit: function (props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;
            var [galleries, setGalleries] = wp.element.useState([]);
            var [loading, setLoading] = wp.element.useState(true);

            wp.element.useEffect(function () {
                apiFetch({ path: '/wp/v2/spartan_gallery_type?per_page=100&_fields=id,title' })
                    .then(function (response) {
                        setGalleries(response);
                        setLoading(false);
                    })
                    .catch(function () {
                        setLoading(false);
                    });
            }, []);

            function toggleGallery(id, checked) {
                var ids = a.galleryIds.filter(function (x) { return x !== id; });
                if (checked) {
                    ids.push(id);
                }
                setAttributes({ galleryIds: ids });
            }

            var selectedNames = galleries
                .filter(function (g) { return a.galleryIds.indexOf(g.id) !== -1; })
                .map(function (g) { return g.title.rendered; });

            return createElement(
                'div',
                {},
                createElement(
                    InspectorControls,
                    {},
                    createElement(
                        PanelBody,
                        { title: 'Galleries' },
                        createElement('p', {}, 'Leave all unchecked to show every gallery.'),
                        loading
                            ? createElement('p', {}, 'Loading galleries...')
                            : galleries.map(function (g) {
                                  return createElement(components.CheckboxControl, {
                                      key: g.id,
                                      label: g.title.rendered || '#' + g.id,
                                      checked: a.galleryIds.indexOf(g.id) !== -1,
                                      onChange: function (checked) { toggleGallery(g.id, checked); },
                                  });
                              })
                    ),
                    createElement(
                        PanelBody,
                        { title: 'Layout' },
                        createElement(components.RangeControl, {
                            label: 'Columns',
                            min: 1,
                            max: 6,
                            value: a.columns,
                            onChange: function (v) { setAttributes({ columns: v || 3 }); },
                        }),
                        createElement(components.ToggleControl, {
                            label: 'Show gallery titles',
                            checked: a.showTitles,
                            onChange: function (v) { setAttributes({ showTitles: v }); },
                        })
                    )
                ),
                createElement(
                    'div',
                    { className: 'gallery-block' },
                    createElement('strong', {}, 'Spartan Album'),
                    createElement(
                        'p',
                        {},
                        selectedNames.length
                            ? 'Galleries: ' + selectedNames.join(', ')
                            : 'All galleries'
                    ),
                    createElement('p', {}, a.columns + ' columns')
                )
            );
        },

        save: function () {
            return null;
        },
    });
})(wp.blocks, wp.blockEditor, wp.components, wp.element, wp.apiFetch);
