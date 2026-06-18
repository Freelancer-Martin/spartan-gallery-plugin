<?php
if ( ! class_exists( 'Redux' ) ) {
    return;
}

$opt_name = 'spartan_gallery_options';
/*
$args = array(
    'opt_name'             => $opt_name,
    'display_name'         => 'My Plugin Options',
    'menu_title'           => 'My Plugin Settings',
    'page_title'           => 'My Plugin Options',
    'admin_bar'            => true,
    'menu_type'            => 'menu',
    'allow_sub_menu'       => true,
    'page_parent_post_type' => 'spartan_gallery_type',
    'customizer'           => true,
);
*/
Redux::setArgs( $opt_name/*, $args*/ );



// Example Section
Redux::setSection( $opt_name, array(
    'title'  => 'General Settings',
    //'id'     => 'general',
    'desc'   => 'Basic settings for your plugin.',
    
) );

// Change this variable to the opt_name of YOUR project.
//$opt_name = 'your_opt_name';
/*
// Standard metabox.
Redux_Metaboxes::set_box(
	$opt_name,
	array(
		'id'         => 'opt-metaboxes',
		'title'      => esc_html__( 'Metabox Options', 'your-textdomain-here' ),
		'post_types' => array( 'spartan_gallery_type' ),
		'position'   => 'normal', // normal, advanced, side.
		'priority'   => 'high',   // high, core, default, low.
		'sections'   => array(
			array(
				'title'  => esc_html__( 'Basic Field', 'your-textdomain-here' ),
				'id'     => 'opt-basic-fields',
				'desc'   => esc_html__( 'Redux Framework was created with the developer in mind. It allows for any theme developer to have an advanced theme panel with most of the features a developer would need. For more information check out the Github repo at:', 'your-textdomain-here' ) . '  <a href="https://github.com/ReduxFramework/Redux-Framework">https://github.com/ReduxFramework/Redux-Framework</a>',
				'icon'   => 'el-icon-cogs',
				'fields' => array(
					array(
						'id'       => 'opt-checkbox',
						'type'     => 'checkbox',
						'title'    => esc_html__( 'Checkbox', 'your-textdomain-here' ),
						'subtitle' => esc_html__( 'Basic Checkbox field.', 'your-textdomain-here' ),
						'default'  => true,
					),
					array(
						'id'       => 'switch-on',
						'type'     => 'switch',
						'title'    => esc_html__( 'Switch On', 'your-textdomain-here' ),
						'subtitle' => esc_html__( 'Look, it\'s on!', 'your-textdomain-here' ),
						'default'  => 1,
					),
				),
			),
			array(
				'title'      => esc_html__( 'Text Field', 'your-textdomain-here' ),
				'desc'       => esc_html__( 'Redux Framework was created with the developer in mind. It allows for any theme developer to have an advanced theme panel with most of the features a developer would need. For more information check out the Github repo at:', 'your-textdomain-here' ) . '  <a href="https://github.com/ReduxFramework/Redux-Framework">https://github.com/ReduxFramework/Redux-Framework</a>',
				'icon'       => 'el-icon-cog',
				'id'         => 'opt-text-fields',
				'subsection' => true,
				'fields'     => array(
					array(
						'title' => esc_html__( 'Text Field', 'your-textdomain-here' ),
						'id'    => 'opt-text',
						'type'  => 'text',
					),
				),
			),
			array(
				'title'  => esc_html__( 'Color Field', 'your-textdomain-here' ),
				'desc'   => esc_html__( 'Redux Framework was created with the developer in mind. It allows for any theme developer to have an advanced theme panel with most of the features a developer would need. For more information check out the Github repo at:', 'your-textdomain-here' ) . '  <a href="https://github.com/ReduxFramework/Redux-Framework">https://github.com/ReduxFramework/Redux-Framework</a>',
				'icon'   => 'el-icon-pencil',
				'id'     => 'color-section',
				'fields' => array(
					array(
						'id'       => 'opt-color',
						'type'     => 'color',
						'title'    => esc_html__( 'Color Field', 'your-textdomain-here' ),
						'default'  => '#333333',
						'required' => array( 'opt-layout', '=', 'on' ),
					),
				),
			),
		),
	)
);

// Metabox sidebar.
Redux_Metaboxes::set_box(
	$opt_name,
	array(
		'id'         => 'opt-metaboxes-3',
		'post_types' => array( 'spartan_gallery_type' ),
		'position'   => 'side', // normal, advanced, side.
		'priority'   => 'high', // high, core, default, low.
		'sections'   => array(
			array(
				'id'         => 'test-sidebar',
				'icon_class' => 'icon-large',
				'icon'       => 'el-icon-home',
				'fields'     => array(
					array(
						'id'      => 'sidebar',
						'title'   => esc_html__( 'Sidebar', 'your-textdomain-here' ),
						'desc'    => esc_html__( 'Please select the sidebar you would like to display on this page. Note: You must first create the sidebar under Appearance > Widgets.', 'your-textdomain-here' ),
						'type'    => 'select',
						'data'    => 'sidebars',
						'default' => 'None',
					),
				),
			),
		),
	)
);
*/
// Standard metabox.
Redux_Metaboxes::set_box(
	$opt_name,
	array(
		'id'         => 'opt-metaboxes-4',
		'title'      => esc_html__( 'Spartan Gallery Options', 'your-textdomain-here' ),
		'post_types' => array( 'spartan_gallery_type' ), // Define post types where this metabox will appear
		'position'   => 'normal', // normal, advanced, side.
		'priority'   => 'high',   // high, core, default, low.
		'sections'   => array(
			// Spartan Gallery First Section
			array(
				'title'  => esc_html__( 'Gallery Layout Options', 'your-textdomain-here' ),
				'id'     => 'spartan_gallery_first_section',
				'icon'   => 'fa-solid fa-bars',
				'fields' => array(
					array(
						'id'       => 'image_layout_select',
						'type'     => 'image_select',
						'title'    => esc_html__( 'Select Gallery Layout', 'your-textdomain-here' ),
						'subtitle'      => esc_html__( 'Here you select layout of the gallery. 
							Gallery layout looks exactly like on the image. If you wish to change the apect radio of gallery, select the 
							third image and click "Display Adjustable Image" ON under "Image options" and switch "Display thumbnail size" OFF. From there you could change image width and height. This settings works 
							only with third and sixth layout. In other layouts image size are generated randomly for the grid.', 'your-textdomain-here' ),
						'options'  => array(
							'value-1' => plugins_url( '/img/FlexFreewallTable.png',  __FILE__ ),
							'value-2' => plugins_url( '/img/ImageFreewallGrid.png',  __FILE__ ),
							'value-3' => plugins_url( 'img/NestedFreewallGrid.png',  __FILE__ ),
							'value-4' => plugins_url( '/img/PinterestFreewallGrid.png',  __FILE__ ),
							'value-5' => plugins_url( '/img/hexagon_layout.jpg',  __FILE__ ),
							'value-6' => plugins_url( '/img/spartan_3D_layout.png',  __FILE__ ),
						),
						'radio'    => true,
						'default'  => 'value-1',
					),
				),
			),

			// Spartan Gallery Second Section
			array(
				'title'  => esc_html__( 'Upload Images', 'your-textdomain-here' ),
				'id'     => 'spartan_gallery_second_section',
				'icon'   => 'fa-regular fa-image',
				'fields' => array(
					array(
						'id'      => 'spartan_gallery_uploaded_images',
						'type'    => 'gallery',
						'title'   => esc_html__( 'Upload', 'your-textdomain-here' ),
						'options' => array(
							'attach'      => true,
							'filecount'   => '101',
							'auto-upload' => true,
						),
					),
					
				),
			),

			// Image Options
			array(
				'title'  => esc_html__( 'Image Options', 'your-textdomain-here' ),
				'id'     => 'image_options',
				'icon'   => 'fa-solid fa-sliders',
				'fields' => array(
					array(
						'id'      => 'display_original',
						'type'    => 'switch',
						'title'   => esc_html__( 'Display Adjustable Image', 'your-textdomain-here' ),
						'subtitle'      => esc_html__( '"Display thumbnail size" setting cant be turned on when when this setting is turned on', 'your-textdomain-here' ),
						'default' => false,
					),
					array(
						'id'      => 'image_width',
						'type'          => 'slider',
						'title'         => esc_html__( 'Image width Steps (1) PX', 'your-textdomain-here' ),
						//'subtitle'      => esc_html__( 'px', 'your-textdomain-here' ),
						//'desc'          => esc_html__( 'Slider description. Min: 0, max: 800, step: 5, default value: 75', 'your-textdomain-here' ),
						'default'       => 150,
						'min'           => 0,
						'step'          => 1,
						'max'           => 2000,
						'display_value' => 'text',
						'output'        => array( '.site-content' => 'max-width' ),
					),
					array(
						'id'      => 'image_height',
						'type'          => 'slider',
						'title'         => esc_html__( 'Image height Steps (1) PX', 'your-textdomain-here' ),
						//'subtitle'      => esc_html__( 'px', 'your-textdomain-here' ),
						//'desc'          => esc_html__( 'Slider description. Min: 0, max: 800, step: 5, default value: 75', 'your-textdomain-here' ),
						'default'       => 150,
						'min'           => 0,
						'step'          => 1,
						'max'           => 2000,
						'display_value' => 'text',
						'output'        => array( '.site-content' => 'max-width' ),
					),
					array(
						'id'             => 'image_measure_value',
						'type'           => 'select',
						'title'          => esc_html__( 'Img measure value', 'your-textdomain-here' ),
						'options'        => array(
							'px'   => 'px',
							'%'    => '%',
							'vh'   => 'vh/vw',
							'rem'  => 'rem',
						),
						'default_option' => esc_html__( 'Select image measure value', 'your-textdomain-here' ),
						'default'        => 'px',
					),
					array(
						'id'      => 'thumbnail_options',
						'type'    => 'switch',
						'title'   => esc_html__( 'Display thumbnail size', 'your-textdomain-here' ),
						'subtitle'      => esc_html__( '"Display Adjustable Image " setting cant be turned on when when this setting is turned on', 'your-textdomain-here' ),
						'default' => true,
					),
					array(
						'id'             => 'thumbnail_size',
						'type'           => 'select',
						'title'          => esc_html__( 'Select Thumbnail Size', 'your-textdomain-here' ),
						'options'        => array(
							'thumbnail'      => 'Thumbnail (default 150px x 150px max)',
							'medium'         => 'Medium resolution (default 300px x 300px max)',
							'medium_large'   => 'Medium Large resolution (default 768px x 0px max)',
							'large'          => 'Large resolution (default 1024px x 1024px max)',
							'original'       => 'Original image resolution (unmodified)',
						),
						'default_option' => esc_html__( 'Select Image Thumbnail Size', 'your-textdomain-here' ),
						'default'        => 'medium_large',
					),
					array(
						'id'      => 'img_margin',
						'type'     => 'spinner', 
						'title'    => esc_html__('Image Margin (PX)', 'your-textdomain-here'),
						//'subtitle' => esc_html__('No validation can be done on this field type','your-textdomain-here'),
						//'desc'     => esc_html__('JQuery UI spinner description. Min:20, max: 100, step:20, default value: 40', 'your-textdomain-here'),
						'default'  => '20',
						'min'      => '0',
						'step'     => '1',
						'max'      => '100',
						//'output'   => array( '.content-area' => 'max-width' ),
					),
					/*
					array(
						'id'      => 'img_border',
						'type'     => 'border',
						'title'    => esc_html__('Image Style Settings', 'your-project-name'),
						//'subtitle' => esc_html__('Only color validation can be done on this field type', 'your-project-name'),
						//'output'   => array('.site-header'),
						'desc'     => esc_html__('This is the description field, again good for additional info.', 'your-project-name'),
						'default'  => array(
							'border-color'  => ' ', 
							'border-style'  => ' ', 
							'border-top'    => ' ', 
							'border-right'  => ' ', 
							'border-bottom' => ' ', 
							'border-left'   => ' '
    )
					),
					*/
				),
			),

			// Button Style Options
			array(
				'title'  => esc_html__( 'Text Style Options', 'your-textdomain-here' ),
				'id'     => 'button_style_options',
				'icon'   => 'fa-solid fa-gears',
				'fields' => array(
					array(
						'id'      => 'display_text',
						'type'    => 'switch',
						'title'   => esc_html__( 'Display text under image', 'your-textdomain-here' ),
						'default' => false,
					),
					array(
						'id'      => 'textbox_height_range',
						'type'          => 'slider',
						'title'         => esc_html__( 'Text conteiner height, value is in %', 'your-textdomain-here' ),
						//'subtitle'      => esc_html__( 'px', 'your-textdomain-here' ),
						//'desc'          => esc_html__( 'Slider description. Min: 0, max: 800, step: 5, default value: 75', 'your-textdomain-here' ),
						'default'       => 50,
						'min'           => 0,
						'step'          => 1,
						'max'           => 100,
						'display_value' => 'text',
						//'output'        => array( '.site-content' => 'max-width' ),
					),
			
					array(
						'id'     => 'info_background_color',
						'type'     => 'color',
						'title'    => esc_html__('Text box Background Color', 'your-textdomain-here'), 
						//'subtitle' => esc_html__('Pick a background color for the theme (default: #fff).', 'your-textdomain-here'),
						'default'  => '#FFFFFF',
						'validate' => 'color',
					),
					
					array(
						 'id'      => 'font_family',
						'type'        => 'typography', 
						'title'       => esc_html__('Typography', 'your-textdomain-here'),
						'google'      => true, 
						'font-backup' => false,
						'font-subsets' => false,
						//'output'      => array('h2.site-description'),
						'units'       =>'px',
						'subtitle'    => esc_html__('Text box typography.', 'your-textdomain-here'),
						'default'     => array(
							'color'       => '#333', 
							'font-style'  => '200', 
							'font-family' => 'Abel', 
							'google'      => true,
							'font-size'   => '9px', 
							'line-height' => '0'
						),
					),	
				),
			),

			array(
				'title'  => esc_html__( 'Gallery Container Settings', 'your-textdomain-here' ),
				'id'     => 'gallery_container_first_section',
				'icon'   => 'fa-solid fa-screwdriver-wrench',
				'fields' => array(
					
					array(
						'id'      => 'container_settings_on_off',
						'type'    => 'switch',
						'title'   => esc_html__( 'Turn settings On/Off', 'your-textdomain-here' ),
						'default' => false,
					),
					array(
						'id'      => 'container_height_width_range',
						'type'     => 'dimensions',
							'units'    => array('em','px','%'),
							'title'    => esc_html__('Container dimensions (Width/Height) Option', 'your-textdomain-here'),
							//'subtitle' => esc_html__('Allow your users to choose width, height, and/or unit.', 'your-textdomain-here'),
							//'desc'     => esc_html__('Enable or disable any piece of this field. Width, Height, or Units.', 'your-textdomain-here'),
							'output'   => array('.spartan-gallery-wrapper'),
							'default'  => array(
								'Width'   => '200', 
								'Height'  => ''
							),
					),
					array(
						'id'     => 'container_background_color',
						'type'     => 'color',
						'title'    => esc_html__('Background Color', 'your-textdomain-here'), 
						//'subtitle' => esc_html__('Pick a background color for the theme (default: #fff).', 'your-textdomain-here'),
						//'output'   => array('.spartan-gallery-wrapper'),	
						
						'default'  => '#FFFFFF',
						'validate' => 'color',
					),
					array(
						'id'     => 'container_border',
						'type'     => 'border',
						'title'    => esc_html__('Border Option', 'your-project-name'),
						//'subtitle' => esc_html__('Only color validation can be done on this field type', 'your-project-name'),
						//'output'   => array('.spartan-gallery-wrapper'),
						//'desc'     => esc_html__('.spartan-gallery-wrapper'.', 'your-project-name'),
						'default'  => array(
							'border-color'  => ' ', 
							'border-style'  => 'none', 
							'border-top'    => ' ', 
							'border-right'  => ' ', 
							'border-bottom' => ' ', 
							'border-left'   => ' '
						),
					),
				),
			),

			
			array(
				'title'  => esc_html__( 'Insert Custom CSS', 'your-textdomain-here' ),
				'id'     => 'insert_extra_css_section',
				'icon'   => 'fa-solid fa-bars-progress',
				'fields' => array(
					array(
						'id'      => 'add_extra_css',
						'type'     => 'ace_editor',
						'title'    => esc_html__('CSS Code', 'your-project-name'),
						'subtitle' => esc_html__('Paste your CSS code here. Image container HTML class is .spartan-gallery-wrapper', 'your-project-name'),
						'desc'     => esc_html__('Image container HTML class is .spartan-gallery-wrapper', 'your-project-name'),
						'mode'     => 'css',
						'theme'    => 'monokai',
						'desc'     => 'Possible modes can be found at https://ace.c9.io/.',
						//'default'  => "#header{\nmargin: 0 auto;\n}"
					),
					
				),
			),

			array(
				'title'  => esc_html__( 'Import/Export Options', 'your-textdomain-here' ),
				//'id'     => 'insert_extra_css_section',
				'icon'   => 'fa-solid fa-bars-progress',
				'fields' => array(
					
					array(
						'id'            => 'opt-import-export',
						'type'          => 'import_export',
						'title'         => 'Import Export',
						'subtitle'      => 'Save and restore your Redux options',
						'full_width'    => false,
					),
				),
			),


		



		),
	)
);


