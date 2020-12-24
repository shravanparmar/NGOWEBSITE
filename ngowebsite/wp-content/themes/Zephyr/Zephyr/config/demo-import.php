<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme's demo-import settings
 *
 * @filter us_config_demo-import
 */
return array(
	'main' => array(
		'title' => 'Main Demo',
		'preview_url' => 'http://zephyr.us-themes.com/',
		'front_page' => 'Home',
		'content' => array(
			'pages',
			'posts',
			'portfolio_items',
			'testimonials',
			'grid_layouts',
			'page_blocks',
			'products',
		),
	),
);
