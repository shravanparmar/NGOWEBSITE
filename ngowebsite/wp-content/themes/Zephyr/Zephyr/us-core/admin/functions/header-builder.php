<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Remove all hooks of "Header Builder" plugin
if ( ! function_exists( 'us_remove_old_hb_hooks' ) ) {
	add_action( 'init', 'us_remove_old_hb_hooks', 7 );
	function us_remove_old_hb_hooks() {
		remove_action( 'wp_ajax_ushb_get_elist_html', 'ajax_ushb_get_elist_html' );
		remove_action( 'wp_ajax_ushb_get_ebuilder_html', 'ajax_ushb_get_ebuilder_html' );
		remove_action( 'wp_ajax_ushb_get_htemplates_html', 'ajax_ushb_get_htemplates_html' );
		remove_action( 'wp_ajax_ushb_save', 'ajax_ushb_save' );
		remove_action( 'admin_notices', 'ushb_check_theme_compatibility_error' );
		remove_action( 'init', 'ushb_create_post_types' );
		remove_filter( 'us_files_search_paths', 'ushb_files_search_paths' );
		remove_filter( 'usof_container_classes', 'ushb_usof_container_classes' );
		remove_filter( 'us_load_header_settings', 'ushb_load_header_settings' );
		remove_filter( 'post_row_actions', 'ushb_post_row_actions' );
		remove_filter( 'admin_bar_menu', 'ushb_admin_bar_menu' );
	}
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Try to deactivate old Header Builder plugin
if ( is_admin() AND is_plugin_active( 'us-header-builder/us-header-builder.php' ) ) {
	deactivate_plugins( 'us-header-builder/us-header-builder.php' );
}

if ( ! function_exists( 'us_hb_load_header_settings' ) ) {
	add_filter( 'us_load_header_settings', 'us_hb_load_header_settings', 9 );
	function us_hb_load_header_settings( $header_settings ) {
		global $us_header_id;
		remove_filter( 'us_load_header_settings', 'us_load_usof_header_settings' ); // TODO remove USOF fallback
		if ( function_exists( 'us_get_public_cpt' ) ) {
			$public_cpt = array_keys( us_get_public_cpt() );
		} else {
			$public_cpt = array();
		}

		// Get Header ID from Theme Options
		$us_header_id = us_get_page_area_id( 'header' );

		// Override Header ID and its settings for certain post when they set in metabox
		$states = array( 'default', 'tablets', 'mobiles' );
		$override_options = array();
		$is_shop = FALSE;
		if ( is_singular() ) {
			$postID = get_the_ID();
		}
		if ( is_404() ) {
			$postID = us_get_option( 'page_404' );
		}
		if ( is_search() AND ! is_post_type_archive( 'product' ) ) {
			$postID = us_get_option( 'search_page' );
		}
		if ( is_home() ) {
			$postID = us_get_option( 'posts_page' );
		}
		if ( function_exists( 'is_shop' ) AND is_shop() AND ! is_search() ) {
			$postID = wc_get_page_id( 'shop' );
			$is_shop = TRUE;
		}
		if ( ! empty( $postID ) AND $postID != 'default' ) {
			if ( usof_meta( 'us_header_id', $postID ) != '__defaults__' OR $is_shop ) {
				if ( class_exists( 'SitePress' ) AND defined( 'ICL_LANGUAGE_CODE' ) ) {
					global $sitepress;
					if ( $sitepress->get_default_language() != ICL_LANGUAGE_CODE ) {
						$original_postID = apply_filters( 'wpml_object_id', $postID, get_post_type( $postID ), TRUE, $sitepress->get_default_language() );
						if ( $original_postID != $postID ) {
							$us_header_id = usof_meta( 'us_header_id', $original_postID );
							$us_header_id = apply_filters( 'wpml_object_id', $us_header_id, 'us_header', TRUE, ICL_LANGUAGE_CODE );
						}
					}
				} elseif ( function_exists( 'pll_default_language' ) AND pll_get_post( $postID ) ) {
					if ( pll_current_language() != pll_default_language() ) {
						$original_postID = pll_get_post( $postID, pll_default_language() );
						if ( $original_postID != $postID ) {
							$us_header_id = usof_meta( 'us_header_id', $original_postID );
							$us_header_id = pll_get_post( $us_header_id );
						}
					}
				}
				if ( usof_meta( 'us_header_sticky_override', $postID ) ) {
					$sticky_override = usof_meta( 'us_header_sticky', $postID );
					if ( ! is_array( $sticky_override ) ) {
						$sticky_override = array();
					}
					foreach ( $states as $state ) {
						$override_options[ $state ]['options']['sticky'] = in_array( $state, $sticky_override );
					}
				}
				if ( usof_meta( 'us_header_transparent_override', $postID ) ) {
					$transparent_override = usof_meta( 'us_header_transparent', $postID );
					if ( ! is_array( $transparent_override ) ) {
						$transparent_override = array();
					}
					foreach ( $states as $state ) {
						$override_options[ $state ]['options']['transparent'] = in_array( $state, $transparent_override );
					}
				}
				if ( usof_meta( 'us_header_shadow', $postID ) ) {
					foreach ( $states as $state ) {
						$override_options[ $state ]['options']['shadow'] = 'none';
					}
				}
			}
		}

		// Reset Header ID to Defaults if set
		if ( $us_header_id == '__defaults__' ) {
			$us_header_id = us_get_option( 'header_id' );
		}

		// Generate header settings from Header post content
		if ( $us_header_id != '' ) {
			if ( class_exists( 'SitePress' ) AND defined( 'ICL_LANGUAGE_CODE' ) ) {
				$us_header_id = apply_filters( 'wpml_object_id', $us_header_id, 'us_header', TRUE );
			} elseif ( function_exists( 'pll_default_language' ) AND pll_get_post( $us_header_id ) ) {
				$us_header_id = pll_get_post( $us_header_id );
			}
			$header = get_post( (int) $us_header_id );
			if ( $header instanceof WP_Post AND $header->post_type === 'us_header' ) {
				if ( ! empty( $header->post_content ) AND substr( strval( $header->post_content ), 0, 1 ) === '{' ) {
					try {
						$header_settings = json_decode( $header->post_content, TRUE );
					}
					catch ( Exception $e ) {
					}
				}
			}
			// Add Header ID to settings
			$header_settings['header_id'] = $us_header_id;

		} else {
			$header_settings['is_hidden'] = TRUE;
		}

		// Merge header settings with metabox settings
		$header_settings = us_array_merge( $header_settings, $override_options );

		return $header_settings;
	}
}

if ( ! function_exists( 'us_hb_enqueue_scripts' ) ) {
	function us_hb_enqueue_scripts() {

		// Appending dependencies
		usof_print_styles();
		usof_print_scripts();

		// Appending required assets
		wp_enqueue_script( 'us-header-builder', US_CORE_URI . '/admin/js/header-builder.js', array( 'usof-scripts' ), US_CORE_VERSION, TRUE );

		// Disabling WP auto-save
		wp_dequeue_script( 'autosave' );
	}
}

if ( ! function_exists( 'us_hb_edit_form_top' ) ) {
	function us_hb_edit_form_top( $post ) {
		echo '<div class="usof-container type_builder';
		echo '" data-ajaxurl="' . esc_attr( admin_url( 'admin-ajax.php' ) ) . '" data-id="' . esc_attr( $post->ID ) . '">';
		echo '<form class="usof-form" method="post" action="#" autocomplete="off">';

		// Output screenlock, if the "Header Builder" plugin is active
		if ( is_plugin_active( 'us-header-builder/us-header-builder.php' ) ) {
			echo '<div class="us-screenlock"><div>';
			echo 'Now header builder functionality is included into "UpSolution Core" plugin.<br>';
			echo '<a href="' . admin_url( 'plugins.php' ) . '">Deactivate and delete "Header Builder" plugin</a> to avoid conflicts.';
			echo '</div></div>';
		}

		// Output _nonce and _wp_http_referer hidden fields for ajax secuirity checks
		wp_nonce_field( 'ushb-update' );

		echo '<div class="usof-header">';
		echo '<div class="usof-header-title">' . _x( 'Edit Header', 'site top area', 'us' ) . '</div>';

		us_load_template(
			'usof/templates/field', array(
				'name' => 'post_title',
				'id' => 'usof_header_title',
				'field' => array(
					'type' => 'text',
					'placeholder' => __( 'Header Name', 'us' ),
					'classes' => 'desc_0', // Reset desc position of global HB field
				),
				'values' => array(
					'post_title' => $post->post_title,
				),
			)
		);

		echo '<div class="usof-control for_help"><a href="https://help.us-themes.com/' . strtolower( US_THEMENAME ) . '/hb/" target="_blank" rel="noopener" title="' . us_translate( 'Help' ) . '"></a></div>';
		echo '<div class="usof-control for_import"><a href="#">' . __( 'Export / Import', 'us' ) . '</a></div>';
		echo '<div class="usof-control for_templates"><a href="#">' . us_translate_x( 'Templates', 'TinyMCE' ) . '</a>';
		echo '<div class="usof-control-desc"><span>' . __( 'Choose Header template to start with', 'us' ) . '</span></div>';
		echo '</div>';
		echo '<div class="usof-control for_save status_clear">';
		echo '<button class="usof-button type_save" type="button"><span>' . us_translate( 'Save Changes' ) . '</span>';
		echo '<span class="usof-preloader"></span></button>';
		echo '<div class="usof-control-message"></div></div></div>';

		us_load_template(
			'usof/templates/field', array(
				'name' => 'post_content',
				'id' => 'usof_header',
				'field' => array(
					'type' => 'header_builder',
					'classes' => 'desc_0', // Reset desc position of global HB field
				),
				'values' => array(
					'post_content' => $post->post_content,
				),
			)
		);

		echo '</form>';
		echo '</div>';

	}
}

// Add link to duplicate headers in admin area
if ( ! function_exists( 'us_hb_post_row_actions' ) ) {
	add_filter( 'post_row_actions', 'us_hb_post_row_actions', 10, 2 );
	function us_hb_post_row_actions( $actions, $post ) {
		if ( $post->post_type === 'us_header' ) {

			// Removing duplicate post plugin affection
			unset( $actions['duplicate'], $actions['edit_as_new_draft'] );
			$actions = us_array_merge_insert(
				$actions, array(
				'duplicate' => '<a href="' . admin_url( 'post-new.php?post_type=us_header&duplicate_from=' . $post->ID ) . '" aria-label="' . esc_attr__( 'Duplicate', 'us' ) . '">' . esc_html__( 'Duplicate', 'us' ) . '</a>',
			), 'before', isset( $actions['trash'] ) ? 'trash' : 'untrash'
			);
		}

		return $actions;
	}
}

// Add link to Admin bar to edit the current header
if ( ! function_exists( 'us_hb_admin_bar_menu' ) ) {
	add_filter( 'admin_bar_menu', 'us_hb_admin_bar_menu', 500 );
	function us_hb_admin_bar_menu( $wp_admin_bar ) {
		global $us_header_id;
		if ( $us_header_id != '' AND current_user_can( 'administrator' ) ) {
			$wp_admin_bar->add_node(
				array(
					'id' => 'ushb-edit-header',
					'title' => _x( 'Edit Header', 'site top area', 'us' ),
					'href' => admin_url( 'post.php?post=' . intval( $us_header_id ) . '&action=edit' ),
				)
			);
		}
	}
}
