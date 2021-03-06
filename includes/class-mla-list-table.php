<?php
/**
 * Media Library Assistant extended List Table class
 *
 * @package Media Library Assistant
 * @since 0.1
 */

/* 
 * The WP_List_Table class isn't automatically available to plugins
 */
if ( !class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class MLA (Media Library Assistant) List Table implements the "Assistant" admin submenu
 *
 * Extends the core WP_List_Table class.
 *
 * @package Media Library Assistant
 * @since 0.1
 */
class MLA_List_Table extends WP_List_Table {
	/*
	 * These variables are used to assign row_actions to exactly one visible column
	 */

	/**
	 * Records assignment of row-level actions to a table row
	 *
	 * Set to the current Post-ID when row-level actions are output for the row.
	 *
	 * @since 0.1
	 *
	 * @var	int
	 */
	protected $rollover_id = 0;

	/**
	 * Currently hidden columns
	 *
	 * Records hidden columns so row-level actions are not assigned to them.
	 *
	 * @since 0.1
	 *
	 * @var	array
	 */
	protected $currently_hidden = array();

	/*
	 * The $default_columns, $default_hidden_columns, and $default_sortable_columns
	 * arrays define the table columns.
	 */

	/**
	 * Table column definitions
	 *
	 * This array defines table columns and titles where the key is the column slug (and class)
	 * and the value is the column's title text. If you need a checkbox for bulk actions,
	 * use the special slug "cb".
	 * 
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * All of the columns are added to this array by MLA_List_Table::mla_admin_init_action.
	 *
	 * @since 0.1
	 *
	 * @var	array
	 */
	protected static $default_columns = array();

	/**
	 * Default values for hidden columns
	 *
	 * This array is used when the user-level option is not set, i.e.,
	 * the user has not altered the selection of hidden columns.
	 *
	 * The value on the right-hand side must match the column slug, e.g.,
	 * array(0 => 'ID_parent, 1 => 'title_name').
	 *
	 * Taxonomy and custom field columns are added to this array by
	 * MLA_List_Table::mla_admin_init_action.
	 * 
	 * @since 0.1
	 *
	 * @var	array
	 */
	protected static $default_hidden_columns	= array(
		// 'ID_parent',
		// 'title_name',
		'post_title',
		'post_name',
		'parent',
		'menu_order',
		// 'featured',
		// 'inserted,
		'galleries',
		'mla_galleries',
		'alt_text',
		'caption',
		'description',
		'post_mime_type',
		'file_url',
		'base_file',
		'date',
		'modified',
		'author',
		'attached_to',
		// taxonomy columns added by mla_admin_init_action
		// custom field columns added by mla_admin_init_action
	);

	/**
	 * Sortable column definitions
	 *
	 * This array defines the table columns that can be sorted. The array key
	 * is the column slug that needs to be sortable, and the value is database column
	 * to sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * The array value also contains a boolean which is 'true' if the initial sort order
	 * for the column is DESC/Descending.
	 *
	 * Taxonomy and custom field columns are added to this array by
	 * MLA_List_Table::mla_admin_init_action.
	 *
	 * @since 0.1
	 *
	 * @var	array
	 */
	protected static $default_sortable_columns = array(
		'ID_parent' => array('ID',true),
		'title_name' => array('title_name',false),
		'post_title' => array('post_title',false),
		'post_name' => array('post_name',false),
		'parent' => array('post_parent',false),
		'menu_order' => array('menu_order',false),
		// 'featured'   => array('featured',false),
		// 'inserted' => array('inserted',false),
		// 'galleries' => array('galleries',false),
		// 'mla_galleries' => array('mla_galleries',false),
		'alt_text' => array('_wp_attachment_image_alt',true),
		'caption' => array('post_excerpt',false),
		'description' => array('post_content',false),
		'post_mime_type' => array('post_mime_type',false),
		'file_url' => array('guid',false),
		'base_file' => array('_wp_attached_file',false),
		'date' => array('post_date',true),
		'modified' => array('post_modified',true),
		'author' => array('post_author',false),
		'attached_to' => array('post_parent',false),
		// sortable taxonomy columns, if any, added by mla_admin_init_action
		// sortable custom field columns, if any, added by mla_admin_init_action
        );

	/**
	 * Get MIME types with one or more attachments for view preparation
	 *
	 * Modeled after get_available_post_mime_types in wp-admin/includes/post.php,
	 * but uses the output of wp_count_attachments() as input.
	 *
	 * @since 0.1
	 *
	 * @param	array	Number of posts for each MIME type
	 *
	 * @return	array	Mime type names
	 */
	protected static function _avail_mime_types( $num_posts ) {
		$available = array();

		foreach ( $num_posts as $mime_type => $number ) {
			if ( ( $number > 0 ) && ( $mime_type <> 'trash' ) ) {
				$available[] = $mime_type;
			}
		}

		return $available;
	}

	/**
	 * Builds the $default_columns array with translated source texts.
	 *
	 * Called from MLA:mla_plugins_loaded_action because the $default_columns information might be
	 * accessed from "front end" posts/pages.
	 *
	 * @since 1.71
	 *
	 * @return	void
	 */
	public static function mla_localize_default_columns_array( ) {
		/*
		 * Build the default columns array at runtime to accomodate calls to the localization functions
		 */
		self::$default_columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
			'icon' => '',
			'ID_parent' => _x( 'ID/Parent', 'list_table_column', 'media-library-assistant' ),
			'title_name' => _x( 'Title/Name', 'list_table_column', 'media-library-assistant' ),
			'post_title' => _x( 'Title', 'list_table_column', 'media-library-assistant' ),
			'post_name' => _x( 'Name', 'list_table_column', 'media-library-assistant' ),
			'parent' => _x( 'Parent ID', 'list_table_column', 'media-library-assistant' ),
			'menu_order' => _x( 'Menu Order', 'list_table_column', 'media-library-assistant' ),
			'featured' => _x( 'Featured in', 'list_table_column', 'media-library-assistant' ),
			'inserted' => _x( 'Inserted in', 'list_table_column', 'media-library-assistant' ),
			'galleries' => _x( 'Gallery in', 'list_table_column', 'media-library-assistant' ),
			'mla_galleries' => _x( 'MLA Gallery in', 'list_table_column', 'media-library-assistant' ),
			'alt_text' => _x( 'ALT Text', 'list_table_column', 'media-library-assistant' ),
			'caption' => _x( 'Caption', 'list_table_column', 'media-library-assistant' ),
			'description' => _x( 'Description', 'list_table_column', 'media-library-assistant' ),
			'post_mime_type' => _x( 'MIME Type', 'list_table_column', 'media-library-assistant' ),
			'file_url' => _x( 'File URL', 'list_table_column', 'media-library-assistant' ),
			'base_file' => _x( 'Base File', 'list_table_column', 'media-library-assistant' ),
			'date' => _x( 'Date', 'list_table_column', 'media-library-assistant' ),
			'modified' => _x( 'Last Modified', 'list_table_column', 'media-library-assistant' ),
			'author' => _x( 'Author', 'list_table_column', 'media-library-assistant' ),
			'attached_to' => _x( 'Attached to', 'list_table_column', 'media-library-assistant' ),
			// taxonomy and custom field columns added below
		);
	}

	/**
	 * Get dropdown box of terms to filter by, if available
	 *
	 * @since 1.20
	 *
	 * @param	integer	currently selected term_id || zero (default)
	 *
	 * @return	string	HTML markup for dropdown box
	 */
	public static function mla_get_taxonomy_filter_dropdown( $selected = 0 ) {
		$dropdown = '';
		$tax_filter =  MLAOptions::mla_taxonomy_support('', 'filter');

		if ( ( '' != $tax_filter ) && ( is_object_in_taxonomy( 'attachment', $tax_filter ) ) ) {
			$tax_object = get_taxonomy( $tax_filter );
			$dropdown_options = array(
				'show_option_all' => __( 'All', 'media-library-assistant' ) . ' ' . $tax_object->labels->name,
				'show_option_none' => __( 'No', 'media-library-assistant' ) . ' ' . $tax_object->labels->name,
				'orderby' => 'name',
				'order' => 'ASC',
				'show_count' => false,
				'hide_empty' => false,
				'child_of' => 0,
				'exclude' => '',
				// 'exclude_tree => '', 
				'echo' => true,
				'depth' => MLAOptions::mla_get_option( MLAOptions::MLA_TAXONOMY_FILTER_DEPTH ),
				'tab_index' => 0,
				'name' => 'mla_filter_term',
				'id' => 'name',
				'class' => 'postform',
				'selected' => $selected,
				'hierarchical' => true,
				'pad_counts' => false,
				'taxonomy' => $tax_filter,
				'hide_if_empty' => false 
			);

			ob_start();
			wp_dropdown_categories( $dropdown_options );
			$dropdown = ob_get_contents();
			ob_end_clean();
		}

		return $dropdown;
	}

	/**
	 * Return the names and display values of the sortable columns
	 *
	 * @since 0.30
	 *
	 * @return	array	name => array( orderby value, heading ) for sortable columns
	 */
	public static function mla_get_sortable_columns( ) {
		$results = array() ;

		foreach ( self::$default_sortable_columns as $key => $value ) {
			$value[1] = self::$default_columns[ $key ];
			$results[ $key ] = $value;
		}

		return $results;
	}

	/**
	 * Process $_REQUEST, building $submenu_arguments
	 *
	 * @since 1.42
	 *
	 * @param	boolean	Optional: Include the "click filter" values in the results
	 *
	 * @return	array	non-empty view, search, filter and sort arguments
	 */
	public static function mla_submenu_arguments( $include_filters = true ) {
		global $sitepress;
		static $submenu_arguments = NULL, $has_filters = NULL;

		if ( is_array( $submenu_arguments ) && ( $has_filters == $include_filters ) ) {
			return $submenu_arguments;
		}

		$submenu_arguments = array();
		$has_filters = $include_filters;
		
		/*
		 * WPML arguments
		 */
		if ( isset( $_REQUEST['lang'] ) ) {
			$submenu_arguments['lang'] = $_REQUEST['lang'];
		} elseif ( is_object( $sitepress ) ) {		 
			$submenu_arguments['lang'] = $sitepress->get_current_language();
		}

		/*
		 * View arguments
		 */
		if ( isset( $_REQUEST['post_mime_type'] ) ) {
			$submenu_arguments['post_mime_type'] = $_REQUEST['post_mime_type'];
		}

		if ( isset( $_REQUEST['detached'] ) ) {
			$submenu_arguments['detached'] = $_REQUEST['detached'];
		}

		if ( isset( $_REQUEST['status'] ) ) {
			$submenu_arguments['status'] = $_REQUEST['status'];
		}

		if ( isset( $_REQUEST['meta_query'] ) ) {
			$submenu_arguments['meta_query'] = $_REQUEST['meta_query'];
		}

		/*
		 * Search box arguments
		 */
		if ( !empty( $_REQUEST['s'] ) ) {
			$submenu_arguments['s'] = urlencode( stripslashes( $_REQUEST['s'] ) );

			if ( isset( $_REQUEST['mla_search_connector'] ) ) {
				$submenu_arguments['mla_search_connector'] = $_REQUEST['mla_search_connector'];
			}

			if ( isset( $_REQUEST['mla_search_fields'] ) ) {
				$submenu_arguments['mla_search_fields'] = $_REQUEST['mla_search_fields'];
			}
		}

		/*
		 * Filter arguments (from table header)
		 */
		if ( isset( $_REQUEST['m'] ) && ( '0' != $_REQUEST['m'] ) ) {
			$submenu_arguments['m'] = $_REQUEST['m'];
		}

		if ( isset( $_REQUEST['mla_filter_term'] ) && ( '0' != $_REQUEST['mla_filter_term'] ) ) {
			$submenu_arguments['mla_filter_term'] = $_REQUEST['mla_filter_term'];
		}

		/*
		 * Sort arguments (from column header)
		 */
		if ( isset( $_REQUEST['order'] ) ) {
			$submenu_arguments['order'] = $_REQUEST['order'];
		}

		if ( isset( $_REQUEST['orderby'] ) ) {
			$submenu_arguments['orderby'] = $_REQUEST['orderby'];
		}

		/*
		 * Filter arguments (from interior table cells)
		 */
		if ( $include_filters ) {
			if ( isset( $_REQUEST['heading_suffix'] ) ) {
				$submenu_arguments['heading_suffix'] = $_REQUEST['heading_suffix'];
			}

			if ( isset( $_REQUEST['parent'] ) ) {
				$submenu_arguments['parent'] = $_REQUEST['parent'];
			}

			if ( isset( $_REQUEST['author'] ) ) {
				$submenu_arguments['author'] = $_REQUEST['author'];
			}

			if ( isset( $_REQUEST['mla-tax'] ) ) {
				$submenu_arguments['mla-tax'] = $_REQUEST['mla-tax'];
			}

			if ( isset( $_REQUEST['mla-term'] ) ) {
				$submenu_arguments['mla-term'] = $_REQUEST['mla-term'];
			}

			if ( isset( $_REQUEST['mla-metakey'] ) ) {
				$submenu_arguments['mla-metakey'] = $_REQUEST['mla-metakey'];
			}

			if ( isset( $_REQUEST['mla-metavalue'] ) ) {
				$submenu_arguments['mla-metavalue'] = $_REQUEST['mla-metavalue'];
			}
		}

		return apply_filters( 'mla_list_table_submenu_arguments', $submenu_arguments, $include_filters );
	}

	/**
	 * Handler for filter 'get_user_option_managemedia_page_mla-menucolumnshidden'
	 *
	 * Required because the screen.php get_hidden_columns function only uses
	 * the get_user_option result. Set when the file is loaded because the object
	 * is not created in time for the call from screen.php.
	 *
	 * @since 0.1
	 *
	 * @param	string	current list of hidden columns, if any
	 * @param	string	'managemedia_page_mla-menucolumnshidden'
	 * @param	object	WP_User object, if logged in
	 *
	 * @return	array	updated list of hidden columns
	 */
	public static function mla_manage_hidden_columns_filter( $result, $option, $user_data ) {
		if ( $result ) {
			return $result;
		}

		return self::$default_hidden_columns;
	}

	/**
	 * Handler for filter 'manage_media_page_mla-menu_columns'
	 *
	 * This required filter dictates the table's columns and titles. Set when the
	 * file is loaded because the list_table object isn't created in time
	 * to affect the "screen options" setup.
	 *
	 * @since 0.1
	 *
	 * @return	array	list of table columns
	 */
	public static function mla_manage_columns_filter( ) {
		return apply_filters( 'mla_list_table_get_columns', self::$default_columns );
	}

	/**
	 * Handler for filter "views_{$this->screen->id}" in /admin/includes/class-wp-list-table.php
	 *
	 * Filter the list of available list table views. Set when the
	 * file is loaded because the list_table object isn't created in time
	 * to affect the "screen options" setup.
	 *
	 * @since 1.82
	 *
	 * @param	array	A list of available list table views
	 *
	 * @return	array	Updated list of available list table views
	 */
	public static function mla_views_media_page_mla_menu_filter( $views ) {
		// hooked by WPML Media in wpml-media.class.php
		$views = apply_filters( 'views_upload', $views );
		return $views;
	}

	/**
	 * Handler for filter "wpml-media_view-upload-count" in /plugins/wpml-media/inc/wpml-media.class.php
	 *
	 * Computes the number of attachments that satisfy a meta_query specification.
	 * The count is automatically made language-specific by WPML filters.
	 *
	 * @since 1.90
	 *
	 * @param	NULL	default return value if not replacing count
	 * @param	string	key/slug value for the selected view
	 * @param	string	HTML <a></a> tag for the link to the selected view
	 * @param	string	language code, e.g., 'en', 'es'
	 *
	 * @return	mixed	NULL to allow SQL query or replacement count value
	 */
	public static function mla_wpml_media_view_upload_count_filter( $count, $key, $view, $lang ) {
		// extract the base URL and query parameters
		$href_count = preg_match( '/(href=["\'])([\s\S]+?)\?([\s\S]+?)(["\'])/', $view, $href_matches );	
		if ( $href_count ) {
			wp_parse_str( $href_matches[3], $href_args );

			if ( isset( $href_args['meta_query'] ) ) {
				$meta_view = self::_get_view( $key, '' );
				// extract the count value
				$href_count = preg_match( '/class="count">\(([^\)]*)\)/', $meta_view, $href_matches );	
				if ( $href_count ) {
					$count = array( $href_matches[1] );
				}
			}
		}

		return $count;
	}

	/**
	 * Handler for filter "wpml-media_view-upload-page-count" in /plugins/wpml-media/inc/wpml-media.class.php
	 *
	 * Computes the number of language-specific attachments that satisfy a meta_query specification.
	 * The count is made language-specific by WPML filters when the current_language is set.
	 *
	 * @since 1.90
	 *
	 * @param	NULL	default return value if not replacing count
	 * @param	string	language code, e.g., 'en', 'es'
	 *
	 * @return	mixed	NULL to allow SQL query or replacement count value
	 */
	public static function mla_wpml_media_view_upload_page_count_filter( $count, $lang ) {
		global $sitepress;

		if ( isset( $_GET['meta_slug'] ) ) {
			$save_lang = $sitepress->get_current_language();
			$sitepress->switch_lang( $lang['code'] );
			$meta_view = self::_get_view( $_GET['meta_slug'], '' );
			$sitepress->switch_lang( $save_lang );

			// extract the count value
			$href_count = preg_match( '/class="count">\(([^\)]*)\)/', $meta_view, $href_matches );	
			if ( $href_count ) {
				$count = array( $href_matches[1] );
			}
		}

		return $count;
	}

	/**
	 * Adds support for taxonomy and custom field columns
	 *
	 * Called in the admin_init action because the list_table object isn't
	 * created in time to affect the "screen options" setup.
	 *
	 * @since 0.30
	 *
	 * @return	void
	 */
	public static function mla_admin_init_action( ) {
		$taxonomies = get_taxonomies( array ( 'show_ui' => true ), 'names' );

		foreach ( $taxonomies as $tax_name ) {
			if ( MLAOptions::mla_taxonomy_support( $tax_name ) ) {
				$tax_object = get_taxonomy( $tax_name );
				self::$default_columns[ 't_' . $tax_name ] = $tax_object->labels->name;
				self::$default_hidden_columns [] = 't_' . $tax_name;
				// self::$default_sortable_columns [] = none at this time
			} // supported taxonomy
		} // foreach $tax_name

		self::$default_columns = array_merge( self::$default_columns, MLAOptions::mla_custom_field_support( 'default_columns' ) );
		self::$default_hidden_columns = array_merge( self::$default_hidden_columns, MLAOptions::mla_custom_field_support( 'default_hidden_columns' ) );
		self::$default_sortable_columns = array_merge( self::$default_sortable_columns, MLAOptions::mla_custom_field_support( 'default_sortable_columns' ) );
	}

	/**
	 * Initializes some properties from $_REQUEST variables, then
	 * calls the parent constructor to set some default configs.
	 *
	 * @since 0.1
	 *
	 * @return	void
	 */
	function __construct( ) {
		global $sitepress;

		$this->detached = isset( $_REQUEST['detached'] );
		$this->is_trash = isset( $_REQUEST['status'] ) && $_REQUEST['status'] == 'trash';

		//Set parent defaults
		parent::__construct( array(
			'singular' => 'attachment', //singular name of the listed records
			'plural' => 'attachments', //plural name of the listed records
			'ajax' => true, //does this table support ajax?
			'screen' => 'media_page_' . MLA::ADMIN_PAGE_SLUG
		), self::$default_columns );

		$this->currently_hidden = self::get_hidden_columns();

		/*
		 * NOTE: There is one add_action call at the end of this source file.
		 * NOTE: There are two add_filter calls at the end of this source file.
		 *
		 * They are added when the source file is loaded because the MLA_List_Table
		 * object is created too late to be useful.
		 */

		if ( is_object( $sitepress ) ) {		 
			add_filter( 'views_media_page_mla-menu', 'MLA_List_Table::mla_views_media_page_mla_menu_filter', 10, 1 );
			add_filter( 'wpml-media_view-upload-count', 'MLA_List_Table::mla_wpml_media_view_upload_count_filter', 10, 4 );
			add_filter( 'wpml-media_view-upload-page-count', 'MLA_List_Table::mla_wpml_media_view_upload_page_count_filter', 10, 2 );
		}
	}

	/**
	 * Supply a column value if no column-specific function has been defined
	 *
	 * Called when the parent class can't find a method specifically built for a given column.
	 * The taxonomy and custom field columns are handled here. All other columns should have
	 * a specific method, so this function returns a troubleshooting message.
	 *
	 * @since 0.1
	 *
	 * @param	array	A singular item (one full row's worth of data)
	 * @param	array	The name/slug of the column to be processed
	 * @return	string	Text or HTML to be placed inside the column
	 */
	function column_default( $item, $column_name ) {
		if ( 't_' == substr( $column_name, 0, 2 ) ) {
			$taxonomy = substr( $column_name, 2 );
			$tax_object = get_taxonomy( $taxonomy );
			$terms = get_object_term_cache( $item->ID, $taxonomy );

			if ( false === $terms ) {
				$terms = wp_get_object_terms( $item->ID, $taxonomy );
				wp_cache_add( $item->ID, $terms, $taxonomy . '_relationships' );
			}

			if ( !is_wp_error( $terms ) ) {
				if ( empty( $terms ) ) {
					return __( 'None', 'media-library-assistant' );
				}

				$list = array();
				foreach ( $terms as $term ) {
					$term_name = esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy, 'display' ) );
					$list[] = sprintf( '<a href="%1$s" title="' . __( 'Filter by', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%3$s</a>', esc_url( add_query_arg( array_merge( self::mla_submenu_arguments( false ), array(
						'page' => MLA::ADMIN_PAGE_SLUG,
						'mla-tax' => $taxonomy,
						'mla-term' => $term->slug,
						'heading_suffix' => urlencode( $tax_object->label . ': ' . $term->name ) 
					) ), 'upload.php' ) ), $term_name, $term_name );
				} // foreach $term

				return join( ', ', $list );
			} else { // if !is_wp_error
				return __( 'not supported', 'media-library-assistant' );
			}
		} // 't_'
		elseif ( 'c_' == substr( $column_name, 0, 2 ) ) {
			$values = get_post_meta( $item->ID, self::$default_columns[ $column_name ], false );
			if ( empty( $values ) ) {
				return '';
			}

			$list = array();
			foreach ( $values as $index => $value ) {
				/*
				 * For display purposes, convert array values.
				 * They are not links because no search will match them.
				 */
				if ( is_array( $value ) ) {
					$list[] = 'array( ' . implode( ', ', $value ) . ' )';
				} else {
					$list[] = sprintf( '<a href="%1$s" title="' . __( 'Filter by', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%3$s</a>', esc_url( add_query_arg( array_merge( self::mla_submenu_arguments( false ), array(
						'page' => MLA::ADMIN_PAGE_SLUG,
						'mla-metakey' => urlencode( self::$default_columns[ $column_name ] ),
						'mla-metavalue' => urlencode( $value ),
						'heading_suffix' => urlencode( self::$default_columns[ $column_name ] . ': ' . $value ) 
					) ), 'upload.php' ) ), esc_html( substr( $value, 0, 64 ) ), esc_html( $value ) );
				}
			}

			if ( count( $list ) > 1 ) {
				return '[' . join( '], [', $list ) . ']';
			} else {
				return $list[0];
			}
		} else { // 'c_'
		
			$content = apply_filters( 'mla_list_table_column_default', NULL, $item, $column_name );
			if ( is_null( $content ) ) {
				//Show the whole array for troubleshooting purposes
				/* translators: 1: column_name 2: column_values */
				return sprintf( __( 'column_default: %1$s, %2$s', 'media-library-assistant' ), $column_name, print_r( $item, true ) );
			} else {
				return $content;
			}
		}
	}

	/**
	 * Displays checkboxes for using bulk actions. The 'cb' column
	 * is given special treatment when columns are processed.
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="cb_%1$s[]" value="%2$s" />',
		/*%1$s*/ $this->_args['singular'], //Let's simply repurpose the table's singular label ("attachment")
		/*%2$s*/ $item->ID //The value of the checkbox should be the object's id
		);
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_icon( $item ) {
		if ( 'checked' == MLAOptions::mla_get_option( MLAOptions::MLA_ENABLE_MLA_ICONS ) ) {
			$dimensions = array( 64, 64 );
			$thumb = wp_get_attachment_image( $item->ID, $dimensions, true, array( 'class' => 'mla_media_thumbnail_64_64' ) );
		} else {
			$dimensions = array( 80, 60 );
			$thumb = wp_get_attachment_image( $item->ID, $dimensions, true, array( 'class' => 'mla_media_thumbnail_80_60' ) );
		}

		if ( in_array( $item->post_mime_type, array( 'image/svg+xml' ) ) ) {
			$thumb = preg_replace( '/width=\"[^\"]*\"/', sprintf( 'width="%1$d"', $dimensions[1] ), $thumb );
			$thumb = preg_replace( '/height=\"[^\"]*\"/', sprintf( 'height="%1$d"', $dimensions[0] ), $thumb );
		}

		if ( $this->is_trash || ! current_user_can( 'edit_post', $item->ID ) ) {
			return $thumb;
		}

		/*
		 * Use the WordPress Edit Media screen for 3.5 and later
		 */
		$view_args = self::mla_submenu_arguments();
		if ( MLATest::$wordpress_3point5_plus ) {
			if ( isset( $view_args['lang'] ) ) {
				$edit_url = 'post.php?post=' . $item->ID . '&action=edit&mla_source=edit&lang=' . $view_args['lang'];
			} else {
				$edit_url = 'post.php?post=' . $item->ID . '&action=edit&mla_source=edit';
			}
		} else {
			if ( isset( $view_args['lang'] ) ) {
				$view_args = array( 'lang' => $view_args['lang'] );
			} else {
				$view_args = array();
			}
			
			$edit_url = '<a href="' . add_query_arg( $view_args, wp_nonce_url( '?mla_admin_action=' . MLA::MLA_ADMIN_SINGLE_EDIT_DISPLAY, MLA::MLA_ADMIN_NONCE ) ) . '" title="' . __( 'Edit this item', 'media-library-assistant' ) . '">' . __( 'Edit', 'media-library-assistant' ) . '</a>';
		}
		
		return sprintf( '<a href="%1$s" title="' . __( 'Edit', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%3$s</a>', admin_url( $edit_url ), esc_attr( $item->post_title ), $thumb ); 
	}

	/**
	 * Translate post_status 'future', 'pending' and 'draft' to label
	 *
	 * @since 2.01
	 * 
	 * @param	string	post_status
	 *
	 * @return	string	Status label or empty string
	 */
	protected function _format_post_status( $post_status ) {
		$flag = ',<br>';
		switch ( $post_status ) {
			case 'future' :
				$flag .= __('Scheduled');
				break;
			case 'pending' :
				$flag .= _x('Pending', 'post state');
				break;
			case 'draft' :
				$flag .= __('Draft');
				break;
			default:
				$flag = '';
		}
		
	return $flag;
	}

	/**
	 * Add rollover actions to exactly one of the following displayed columns:
	 * 'ID_parent', 'title_name', 'post_title', 'post_name'
	 *
	 * @since 0.1
	 * 
	 * @param	object	A singular attachment (post) object
	 * @param	string	Current column name
	 *
	 * @return	array	Names and URLs of row-level actions
	 */
	protected function _build_rollover_actions( $item, $column ) {
		$actions = array();

		if ( ( $this->rollover_id != $item->ID ) && !in_array( $column, $this->currently_hidden ) ) {
			/*
			 * Build rollover actions
			 */
			$view_args = array_merge( array( 'page' => MLA::ADMIN_PAGE_SLUG, 'mla_item_ID' => $item->ID ),
				self::mla_submenu_arguments() );
				
			if ( isset( $_REQUEST['paged'] ) ) {
				$view_args['paged'] = $_REQUEST['paged'];
			}

			if ( current_user_can( 'edit_post', $item->ID ) ) {
				if ( $this->is_trash ) {
					$actions['restore'] = '<a class="submitdelete" href="' . add_query_arg( $view_args, wp_nonce_url( '?mla_admin_action=' . MLA::MLA_ADMIN_SINGLE_RESTORE, MLA::MLA_ADMIN_NONCE ) ) . '" title="' . __( 'Restore this item from the Trash', 'media-library-assistant' ) . '">' . __( 'Restore', 'media-library-assistant' ) . '</a>';
				} else {
					/*
					 * Use the WordPress Edit Media screen for 3.5 and later
					 */
					if ( MLATest::$wordpress_3point5_plus ) {
						if ( isset( $view_args['lang'] ) ) {
							$edit_url = 'post.php?post=' . $item->ID . '&action=edit&mla_source=edit&lang=' . $view_args['lang'];
						} else {
							$edit_url = 'post.php?post=' . $item->ID . '&action=edit&mla_source=edit';
						}

						$actions['edit'] = '<a href="' . admin_url( $edit_url ) . '" title="' . __( 'Edit this item', 'media-library-assistant' ) . '">' . __( 'Edit', 'media-library-assistant' ) . '</a>';
					} else {
						$actions['edit'] = '<a href="' . add_query_arg( $view_args, wp_nonce_url( '?mla_admin_action=' . MLA::MLA_ADMIN_SINGLE_EDIT_DISPLAY, MLA::MLA_ADMIN_NONCE ) ) . '" title="' . __( 'Edit this item', 'media-library-assistant' ) . '">' . __( 'Edit', 'media-library-assistant' ) . '</a>';
					}
					
					$actions['inline hide-if-no-js'] = '<a class="editinline" href="#" title="' . __( 'Edit this item inline', 'media-library-assistant' ) . '">' . __( 'Quick Edit', 'media-library-assistant' ) . '</a>';
				}
			} // edit_post

			if ( current_user_can( 'delete_post', $item->ID ) ) {
				if ( !$this->is_trash && EMPTY_TRASH_DAYS && MEDIA_TRASH ) {
					$actions['trash'] = '<a class="submitdelete" href="' . add_query_arg( $view_args, wp_nonce_url( '?mla_admin_action=' . MLA::MLA_ADMIN_SINGLE_TRASH, MLA::MLA_ADMIN_NONCE ) ) . '" title="' . __( 'Move this item to the Trash', 'media-library-assistant' ) . '">' . __( 'Move to Trash', 'media-library-assistant' ) . '</a>';
				} else {
					// If using trash for posts and pages but not for attachments, warn before permanently deleting 
					$delete_ays = EMPTY_TRASH_DAYS && !MEDIA_TRASH ? ' onclick="return showNotice.warn();"' : '';

					$actions['delete'] = '<a class="submitdelete"' . $delete_ays . ' href="' . add_query_arg( $view_args, wp_nonce_url( '?mla_admin_action=' . MLA::MLA_ADMIN_SINGLE_DELETE, MLA::MLA_ADMIN_NONCE ) ) . '" title="' . __( 'Delete this item Permanently', 'media-library-assistant' ) . '">' . __( 'Delete Permanently', 'media-library-assistant' ) . '</a>';
				}
			} // delete_post

			if ( current_user_can( 'upload_files' ) ) {
				$file = get_attached_file( $item->ID );
				$download_args = array( 'page' => MLA::ADMIN_PAGE_SLUG, 'mla_download_file' => urlencode( $file ), 'mla_download_type' => $item->post_mime_type );

				$actions['download'] = '<a href="' . add_query_arg( $download_args, wp_nonce_url( 'upload.php', MLA::MLA_ADMIN_NONCE ) ) . '" title="' . __( 'Download', 'media-library-assistant' ) . ' &#8220;' . esc_attr( $item->post_title ) . '&#8221;">' . __( 'Download', 'media-library-assistant' ) . '</a>';
			}

			$actions['view']  = '<a href="' . site_url( ) . '?attachment_id=' . $item->ID . '" rel="permalink" title="' . __( 'View', 'media-library-assistant' ) . ' &#8220;' . esc_attr( $item->post_title ) . '&#8221;">' . __( 'View', 'media-library-assistant' ) . '</a>';

			$actions = apply_filters( 'mla_list_table_build_rollover_actions', $actions, $item, $column );
		
			$this->rollover_id = $item->ID;
		} // $this->rollover_id != $item->ID

		return $actions;
	}

	/**
	 * Add hidden fields with the data for use in the inline editor
	 *
	 * @since 0.20
	 * 
	 * @param	object	A singular attachment (post) object
	 *
	 * @return	string	HTML <div> with row data
	 */
	protected function _build_inline_data( $item ) {
		$inline_data = "\r\n" . '<div class="hidden" id="inline_' . $item->ID . "\">\r\n";
		$inline_data .= '	<div class="post_title">' . esc_attr( $item->post_title ) . "</div>\r\n";
		$inline_data .= '	<div class="post_name">' . esc_attr( $item->post_name ) . "</div>\r\n";
		$inline_data .= '	<div class="post_excerpt">' . esc_attr( $item->post_excerpt ) . "</div>\r\n";
		$inline_data .= '	<div class="post_content">' . esc_attr( $item->post_content ) . "</div>\r\n";

		if ( !empty( $item->mla_wp_attachment_metadata ) ) {
			if ( isset( $item->mla_wp_attachment_image_alt ) ) {
				$inline_data .= '	<div class="image_alt">' . esc_attr( $item->mla_wp_attachment_image_alt ) . "</div>\r\n";
			} else {
				$inline_data .= '	<div class="image_alt">' . "</div>\r\n";
			}
		}

		$inline_data .= '	<div class="post_parent">' . $item->post_parent . "</div>\r\n";

		if ( $item->post_parent ) {
			if ( isset( $item->parent_title ) ) {
				$parent_title = $item->parent_title;
			} else {
				$parent_title = __( '(no title)', 'media-library-assistant' );
			}
		} else {
			$parent_title = '';
		}

		$inline_data .= '	<div class="post_parent_title">' . $parent_title . "</div>\r\n";
		$inline_data .= '	<div class="menu_order">' . $item->menu_order . "</div>\r\n";
		$inline_data .= '	<div class="post_author">' . $item->post_author . "</div>\r\n";

		$custom_fields = MLAOptions::mla_custom_field_support( 'quick_edit' );
		$custom_fields = array_merge( $custom_fields, MLAOptions::mla_custom_field_support( 'bulk_edit' ) );
		foreach ($custom_fields as $slug => $label ) {
			$value = get_metadata( 'post', $item->ID, $label, true );
			$inline_data .= '	<div class="' . $slug . '">' . esc_html( $value ) . "</div>\r\n";
		}

		$taxonomies = get_object_taxonomies( 'attachment', 'objects' );

		foreach ( $taxonomies as $tax_name => $tax_object ) {
			if ( $tax_object->show_ui && MLAOptions::mla_taxonomy_support( $tax_name, 'quick-edit' ) ) {
				$terms = get_object_term_cache( $item->ID, $tax_name );
				if ( false === $terms ) {
					$terms = wp_get_object_terms( $item->ID, $tax_name );
					wp_cache_add( $item->ID, $terms, $tax_name . '_relationships' );
				}

				if ( is_wp_error( $terms ) || empty( $terms ) ) {
					$terms = array();
				}

				$ids = array();

				if ( $tax_object->hierarchical ) {
					foreach( $terms as $term ) {
						$ids[] = $term->term_id;
					}

					$inline_data .= '	<div class="mla_category" id="' . $tax_name . '_' . $item->ID . '">'
						. implode( ',', $ids ) . "</div>\r\n";
				} else {
					foreach( $terms as $term ) {
						$ids[] = $term->name;
					}

					$inline_data .= '	<div class="mla_tags" id="'.$tax_name.'_'.$item->ID. '">'
						. esc_attr( implode( ', ', $ids ) ) . "</div>\r\n";
				}
			}
		}

		$inline_data = apply_filters( 'mla_list_table_build_inline_data', $inline_data, $item );
		
		$inline_data .= "</div>\r\n";

		return $inline_data;
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_ID_parent( $item ) {
		$row_actions = self::_build_rollover_actions( $item, 'ID_parent' );
		if ( $item->post_parent ) {
			if ( isset( $item->parent_title ) ) {
				$parent_title = $item->parent_title;
			} else {
				$parent_title = sprintf( '%1$d %2$s', $item->post_parent, __( '(no title)', 'media-library-assistant' ) );
			}

			$parent = sprintf( '<a href="%1$s" title="' . __( 'Filter by Parent ID', 'media-library-assistant' ) . '">(parent:%2$s)</a>', esc_url( add_query_arg( array_merge( self::mla_submenu_arguments( false ), array(
					'page' => MLA::ADMIN_PAGE_SLUG,
					'parent' => $item->post_parent,
					'heading_suffix' => urlencode( __( 'Parent', 'media-library-assistant' ) . ': ' .  $parent_title ) 
				) ), 'upload.php' ) ), (string) $item->post_parent );
		} else {// $item->post_parent
			$parent = 'parent:0';
		}

		if ( !empty( $row_actions ) ) {
			return sprintf( '%1$s<br><span style="color:silver">%2$s</span><br>%3$s%4$s', /*%1$s*/ $item->ID, /*%2$s*/ $parent, /*%3$s*/ $this->row_actions( $row_actions ), /*%4$s*/ $this->_build_inline_data( $item ) );
		} else {
			return sprintf( '%1$s<br><span style="color:silver">%2$s</span>', /*%1$s*/ $item->ID, /*%2$s*/ $parent );
		}
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_title_name( $item ) {
		$row_actions = self::_build_rollover_actions( $item, 'title_name' );
		$post_title = esc_attr( $item->post_title );
		$post_name = esc_attr( $item->post_name );
		$errors = $item->mla_references['parent_errors'];
		if ( '(' . __( 'NO REFERENCE TESTS', 'media-library-assistant' ) . ')' == $errors ) {
			$errors = '';
		}

		if ( !empty( $row_actions ) ) {
			return sprintf( '%1$s<br>%2$s<br>%3$s%4$s%5$s', /*%1$s*/ $post_title, /*%2$s*/ $post_name, /*%3$s*/ $errors, /*%4$s*/ $this->row_actions( $row_actions ), /*%5$s*/ $this->_build_inline_data( $item ) );
		} else {
			return sprintf( '%1$s<br>%2$s<br>%3$s', /*%1$s*/ $post_title, /*%2$s*/ $post_name, /*%3$s*/ $errors );
		}
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_post_title( $item ) {
		$row_actions = self::_build_rollover_actions( $item, 'post_title' );

		if ( !empty( $row_actions ) ) {
			return sprintf( '%1$s<br>%2$s%3$s', /*%1$s*/ esc_attr( $item->post_title ), /*%2$s*/ $this->row_actions( $row_actions ), /*%3$s*/ $this->_build_inline_data( $item ) );
		} else {
			return esc_attr( $item->post_title );
		}
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_post_name( $item ) {
		$row_actions = self::_build_rollover_actions( $item, 'post_name' );

		if ( !empty( $row_actions ) ) {
			return sprintf( '%1$s<br>%2$s%3$s', /*%1$s*/ esc_attr( $item->post_name ), /*%2$s*/ $this->row_actions( $row_actions ), /*%3$s*/ $this->_build_inline_data( $item ) );
		} else {
			return esc_attr( $item->post_name );
		}
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_parent( $item ) {
		if ( $item->post_parent ){
			if ( isset( $item->parent_title ) ) {
				$parent_title = $item->parent_title;
			} else {
				$parent_title = __( '(no title: bad ID)', 'media-library-assistant' );
			}

			return sprintf( '<a href="%1$s" title="' . __( 'Filter by Parent ID', 'media-library-assistant' ) . '">%2$s</a>', esc_url( add_query_arg( array_merge( self::mla_submenu_arguments( false ), array(
				'page' => MLA::ADMIN_PAGE_SLUG,
				'parent' => $item->post_parent,
				'heading_suffix' => urlencode( __( 'Parent', 'media-library-assistant' ) . ': ' .  $parent_title ) 
			) ), 'upload.php' ) ), (string) $item->post_parent );
		} else {
			return (string) $item->post_parent;
		}
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.60
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_menu_order( $item ) {
		return (string) $item->menu_order;
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_featured( $item ) {
		if ( !MLAOptions::$process_featured_in ) {
			return __( 'Disabled', 'media-library-assistant' );
		}
		
		/*
		 * Move parent to the top of the list
		 */
		$features = $item->mla_references['features'];
		if ( isset( $features[ $item->post_parent ] ) ) {
			$parent = $features[ $item->post_parent ];
			unset( $features[ $item->post_parent ] );
			array_unshift( $features, $parent );
		}

		$value = '';
		foreach ( $features as $feature ) {
			$status = self::_format_post_status( $feature->post_status );
			
			if ( $feature->ID == $item->post_parent ) {
				$parent = ',<br>' . __( 'PARENT', 'media-library-assistant' );
			} else {
				$parent = '';
			}

			$value .= sprintf( '<a href="%1$s" title="' . __( 'Edit', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%2$s</a> (%3$s %4$s%5$s%6$s), ',
				/*%1$s*/ esc_url( add_query_arg( array('post' => $feature->ID, 'action' => 'edit'), 'post.php' ) ),
				/*%2$s*/ esc_attr( $feature->post_title ),
				/*%3$s*/ esc_attr( $feature->post_type ),
				/*%4$s*/ $feature->ID,
				/*%5$s*/ $status,
				/*%6$s*/ $parent ) . "<br>\r\n";
		} // foreach $feature

		return $value;
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_inserted( $item ) {
		if ( !MLAOptions::$process_inserted_in ) {
			return __( 'Disabled', 'media-library-assistant' );
		}

		$value = '';
		foreach ( $item->mla_references['inserts'] as $file => $inserts ) {
			if ( 'base' != $item->mla_references['inserted_option'] ) {
				$value .= sprintf( '<strong>%1$s</strong><br>', $file );
			}

			/*
			 * Move parent to the top of the list
			 */
			if ( isset( $inserts[ $item->post_parent ] ) ) {
				$parent = $inserts[ $item->post_parent ];
				unset( $inserts[ $item->post_parent ] );
				array_unshift( $inserts, $parent );
			}
			
			foreach ( $inserts as $insert ) {
				$status = self::_format_post_status( $insert->post_status );

				if ( $insert->ID == $item->post_parent ) {
					$parent = ',<br>' . __( 'PARENT', 'media-library-assistant' );
				} else {
					$parent = '';
				}

				$value .= sprintf( '<a href="%1$s" title="' . __( 'Edit', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%2$s</a> (%3$s %4$s%5$s%6$s), ',
				/*%1$s*/ esc_url( add_query_arg( array('post' => $insert->ID, 'action' => 'edit'), 'post.php' ) ),
				/*%2$s*/ esc_attr( $insert->post_title ),
				/*%3$s*/ esc_attr( $insert->post_type ),
				/*%4$s*/ $insert->ID,
				/*%3$s*/ $status,
				/*%6$s*/ $parent ) . "<br>\r\n";
			} // foreach $insert
		} // foreach $file

		return $value;
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.70
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_galleries( $item ) {
		if ( !MLAOptions::$process_gallery_in ) {
			return __( 'Disabled', 'media-library-assistant' );
		}

		/*
		 * Move parent to the top of the list
		 */
		$galleries = $item->mla_references['galleries'];
		if ( isset( $galleries[ $item->post_parent ] ) ) {
			$parent = $galleries[ $item->post_parent ];
			unset( $galleries[ $item->post_parent ] );
			array_unshift( $galleries, $parent );
		}
		
		$value = '';
		foreach ( $galleries as $ID => $gallery ) {
			$status = self::_format_post_status( $gallery['post_status'] );
			
			if ( $gallery['ID'] == $item->post_parent ) {
				$parent = ',<br>' . __( 'PARENT', 'media-library-assistant' );
			} else {
				$parent = '';
			}

			$value .= sprintf( '<a href="%1$s" title="' . __( 'Edit', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%2$s</a> (%3$s %4$s%5$s%6$s),',
				/*%1$s*/ esc_url( add_query_arg( array('post' => $gallery['ID'], 'action' => 'edit'), 'post.php' ) ),
				/*%2$s*/ esc_attr( $gallery['post_title'] ),
				/*%3$s*/ esc_attr( $gallery['post_type'] ),
				/*%4$s*/ $gallery['ID'],
				/*%5$s*/ $status,
				/*%6$s*/ $parent ) . "<br>\r\n";
		} // foreach $gallery

		return $value;
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.70
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_mla_galleries( $item ) {
		if ( !MLAOptions::$process_mla_gallery_in ) {
			return __( 'Disabled', 'media-library-assistant' );
		}

		/*
		 * Move parent to the top of the list
		 */
		$mla_galleries = $item->mla_references['mla_galleries'];
		if ( isset( $mla_galleries[ $item->post_parent ] ) ) {
			$parent = $mla_galleries[ $item->post_parent ];
			unset( $mla_galleries[ $item->post_parent ] );
			array_unshift( $mla_galleries, $parent );
		}
		
		$value = '';
		foreach ( $mla_galleries as $gallery ) {
			$status = self::_format_post_status( $gallery['post_status'] );
			
			if ( $gallery['ID'] == $item->post_parent ) {
				$parent = ',<br>' . __( 'PARENT', 'media-library-assistant' );
			} else {
				$parent = '';
			}

			$value .= sprintf( '<a href="%1$s" title="' . __( 'Edit', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%2$s</a> (%3$s %4$s%5$s%6$s),',
				/*%1$s*/ esc_url( add_query_arg( array('post' => $gallery['ID'], 'action' => 'edit'), 'post.php' ) ),
				/*%2$s*/ esc_attr( $gallery['post_title'] ),
				/*%3$s*/ esc_attr( $gallery['post_type'] ),
				/*%4$s*/ $gallery['ID'],
				/*%5$s*/ $status,
				/*%6$s*/ $parent ) . "<br>\r\n";
		} // foreach $gallery

		return $value;
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_alt_text( $item ) {
		if ( isset( $item->mla_wp_attachment_image_alt ) ) {
			if ( is_array( $item->mla_wp_attachment_image_alt ) ) {
				$alt_text = $item->mla_wp_attachment_image_alt[0];
			} else {
				$alt_text = $item->mla_wp_attachment_image_alt;
			}
			
			return sprintf( '<a href="%1$s" title="' . __( 'Filter by', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%3$s</a>', esc_url( add_query_arg( array_merge( self::mla_submenu_arguments( false ), array(
				'page' => MLA::ADMIN_PAGE_SLUG,
				'mla-metakey' => '_wp_attachment_image_alt',
				'mla-metavalue' => urlencode( $alt_text ),
				'heading_suffix' => urlencode( __( 'ALT Text', 'media-library-assistant' ) . ': ' . $alt_text ) 
			) ), 'upload.php' ) ), esc_html( $alt_text ), esc_html( $alt_text ) );
		}

		return '';
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_caption( $item ) {
		return esc_attr( $item->post_excerpt );
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_description( $item ) {
		return esc_textarea( $item->post_content );
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.30
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_post_mime_type( $item ) {
		return sprintf( '<a href="%1$s" title="' . __( 'Filter by', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%2$s</a>', esc_url( add_query_arg( array_merge( self::mla_submenu_arguments( false ), array(
			'page' => MLA::ADMIN_PAGE_SLUG,
			'post_mime_type' => urlencode( $item->post_mime_type ),
			'heading_suffix' => urlencode( __( 'MIME Type', 'media-library-assistant' ) . ': ' . $item->post_mime_type ) 
		) ), 'upload.php' ) ), esc_html( $item->post_mime_type ), esc_html( $item->post_mime_type ) );
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_file_url( $item ) {
		$attachment_url = wp_get_attachment_url( $item->ID );

		return $attachment_url ? $attachment_url : __( 'None', 'media-library-assistant' );
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_base_file( $item ) {
		return sprintf( '<a href="%1$s" title="' . __( 'Filter by', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%2$s</a>', esc_url( add_query_arg( array_merge( self::mla_submenu_arguments( false ), array(
			'page' => MLA::ADMIN_PAGE_SLUG,
			'mla-metakey' => urlencode( '_wp_attached_file' ),
			'mla-metavalue' => urlencode( $item->mla_references['base_file'] ),
			'heading_suffix' => urlencode( __( 'Base File', 'media-library-assistant' ) . ': ' . $item->mla_references['base_file'] ) 
		) ), 'upload.php' ) ), esc_html( $item->mla_references['base_file'] ) );
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_date( $item ) {
		global $post;
		
		if ( '0000-00-00 00:00:00' == $item->post_date ) {
			$h_time = __( 'Unpublished', 'media-library-assistant' );
		} else {
			$post = $item; // Resolve issue with "The Events Calendar"
			$m_time = $item->post_date;
			$time = get_post_time( 'G', true, $item, false );

			if ( ( abs( $t_diff = time() - $time ) ) < 86400 ) {
				if ( $t_diff < 0 ) {
					/* translators: 1: upload/last modified date and time */
					$h_time = sprintf( __( '%1$s from now', 'media-library-assistant' ), human_time_diff( $time ) );
				} else {
					/* translators: 1: upload/last modified date and time */
					$h_time = sprintf( __( '%1$s ago', 'media-library-assistant' ), human_time_diff( $time ) );
				}
			} else {
				/* translators: format for upload/last modified date */
				$h_time = mysql2date( __( 'Y/m/d', 'media-library-assistant' ), $m_time );
			}
		}

		return $h_time;
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.30
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_modified( $item ) {
		if ( '0000-00-00 00:00:00' == $item->post_modified ) {
			$h_time = __( 'Unpublished', 'media-library-assistant' );
		} else {
			$m_time = $item->post_modified;
			$time = get_post_time( 'G', true, $item, false );

			if ( ( abs( $t_diff = time() - $time ) ) < 86400 ) {
				if ( $t_diff < 0 ) {
					$h_time = sprintf( __( '%1$s from now', 'media-library-assistant' ), human_time_diff( $time ) );
				} else {
					$h_time = sprintf( __( '%1$s ago', 'media-library-assistant' ), human_time_diff( $time ) );
				}
			} else {
				$h_time = mysql2date( __( 'Y/m/d', 'media-library-assistant' ), $m_time );
			}
		}

		return $h_time;
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.30
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_author( $item ) {
		$user = get_user_by( 'id', $item->post_author );

		if ( isset( $user->data->display_name ) ) {
			return sprintf( '<a href="%s" title="' . __( 'Filter by Author ID', 'media-library-assistant' ) . '">%s</a>', esc_url( add_query_arg( array_merge( self::mla_submenu_arguments( false ), array(
				 'page' => MLA::ADMIN_PAGE_SLUG,
				'author' => $item->post_author,
				'heading_suffix' => urlencode( __( 'Author', 'media-library-assistant' ) . ': ' . $user->data->display_name ) 
			) ), 'upload.php' ) ), esc_html( $user->data->display_name ) );
		}

		return 'unknown';
	}

	/**
	 * Supply the content for a custom column
	 *
	 * @since 0.1
	 * 
	 * @param	array	A singular attachment (post) object
	 * @return	string	HTML markup to be placed inside the column
	 */
	function column_attached_to( $item ) {
		if ( isset( $item->parent_title ) ) {
			$parent_title = sprintf( '<a href="%1$s" title="' . __( 'Edit', 'media-library-assistant' ) . ' &#8220;%2$s&#8221;">%3$s</a>', esc_url( add_query_arg( array(
				'post' => $item->post_parent,
				'action' => 'edit'
			), 'post.php' ) ), esc_attr( $item->parent_title ), esc_attr( $item->parent_title ) );

			if ( isset( $item->parent_date ) ) {
				$parent_date = $item->parent_date;
			} else {
				$parent_date = '';
			}

			if ( isset( $item->parent_type ) ) {
				$parent_type = '(' . $item->parent_type . ' ' . (string) $item->post_parent . self::_format_post_status( $item->parent_status ) . ')';
			} else {
				$parent_type = '';
			}

			$parent =  sprintf( '%1$s<br>%2$s<br>%3$s', /*%1$s*/ $parent_title, /*%2$s*/ mysql2date( __( 'Y/m/d', 'media-library-assistant' ), $parent_date ), /*%3$s*/ $parent_type ); // . "<br>\r\n";
		} else {
			$parent = '(' . _x( 'Unattached', 'post_mime_types_singular', 'media-library-assistant' ) . ')';
		}

		$set_parent = sprintf( '<a class="hide-if-no-js" id="mla-child-%2$s" onclick="mla.inlineEditAttachment.tableParentOpen( \'%1$s\',\'%2$s\',\'%3$s\' ); return false;" href="#the-list">%4$s</a><br>', /*%1$s*/ $item->post_parent, /*%2$s*/ $item->ID, /*%3$s*/ esc_attr( $item->post_title ), /*%4$s*/ __( 'Set Parent', 'media-library-assistant' ) );

		return $parent . "<br>\n" . $set_parent . "\n";
	}

	/**
	 * Display the pagination, adding view, search and filter arguments
	 *
	 * @since 1.42
	 * 
	 * @param	string	'top' | 'bottom'
	 * @return	void
	 */
	function pagination( $which ) {
		$save_uri = $_SERVER['REQUEST_URI'];
		$_SERVER['REQUEST_URI'] = add_query_arg( self::mla_submenu_arguments(), $save_uri );
		parent::pagination( $which );
		$_SERVER['REQUEST_URI'] = $save_uri;
	}

	/**
	 * This method dictates the table's columns and titles
	 *
	 * @since 0.1
	 * 
	 * @return	array	Column information: 'slugs'=>'Visible Titles'
	 */
	function get_columns( ) {
		return self::mla_manage_columns_filter();
	}

	/**
	 * Returns the list of currently hidden columns from a user option or
	 * from default values if the option is not set
	 *
	 * @since 0.1
	 * 
	 * @return	array	Column information,e.g., array(0 => 'ID_parent, 1 => 'title_name')
	 */
	function get_hidden_columns( ) {
		$columns = get_user_option( 'managemedia_page_' . MLA::ADMIN_PAGE_SLUG . 'columnshidden' );

		if ( is_array( $columns ) ) {
			foreach ( $columns as $index => $value ){
				if ( empty( $value ) ) {
					unset( $columns[ $index ] );
				}
			}
		} else {
			$columns = self::$default_hidden_columns;
		}
		
		return apply_filters( 'mla_list_table_get_hidden_columns', $columns );
	}

	/**
	 * Returns an array where the  key is the column that needs to be sortable
	 * and the value is db column (or other criteria) to sort by.
	 *
	 * @since 0.1
	 * 
	 * @return	array	Sortable column information,e.g.,
	 * 					'slug' => array('data_value', (boolean) initial_descending )
	 */
	function get_sortable_columns( ) {
		return apply_filters( 'mla_list_table_get_sortable_columns', self::$default_sortable_columns );
	}

	/**
	 * Print column headers, adding view, search and filter arguments
	 *
	 * @since 1.42
	 *
	 * @param bool $with_id Whether to set the id attribute or not
	 */
	function print_column_headers( $with_id = true ) {
		$save_uri = $_SERVER['REQUEST_URI'];
		$_SERVER['REQUEST_URI'] = add_query_arg( self::mla_submenu_arguments(), $save_uri );
		parent::print_column_headers( $with_id );
		$_SERVER['REQUEST_URI'] = $save_uri;
	}

	/**
	 * Returns HTML markup for one view that can be used with this table
	 *
	 * @since 1.40
	 *
	 * @param	string	View slug, key to MLA_POST_MIME_TYPES array 
	 * @param	string	Slug for current view 
	 * 
	 * @return	string | false	HTML for link to display the view, false if count = zero
	 */
	function _get_view( $view_slug, $current_view ) {
		global $wpdb;
		static $mla_types = NULL, $posts_per_type, $post_mime_types, $avail_post_mime_types, $matches, $num_posts;

		/*
		 * Calculate the common values once per page load
		 */
		if ( is_null( $mla_types ) ) {
			$query_types = MLAMime::mla_query_view_items( array( 'orderby' => 'menu_order' ), 0, 0 );
			if ( ! is_array( $query_types ) ) {
				$query_types = array ();
			}

			$mla_types = array ();
			foreach ( $query_types as $value ) {
				$mla_types[ $value->slug ] = $value;
			}

			$posts_per_type = (array) wp_count_attachments();
			$post_mime_types = get_post_mime_types();
			$avail_post_mime_types = self::_avail_mime_types( $posts_per_type );
			$matches = wp_match_mime_types( array_keys( $post_mime_types ), array_keys( $posts_per_type ) );

			foreach ( $matches as $type => $reals ) {
				foreach ( $reals as $real ) {
					$num_posts[ $type ] = ( isset( $num_posts[ $type ] ) ) ? $num_posts[ $type ] + $posts_per_type[ $real ] : $posts_per_type[ $real ];
				}
			}
		}

		$class = ( $view_slug == $current_view ) ? ' class="current"' : '';
		$base_url = 'upload.php?page=' . MLA::ADMIN_PAGE_SLUG;

		/*
		 * Handle the special cases: all, unattached and trash
		 */
		switch( $view_slug ) {
			case 'all':
				$total_items = array_sum( $posts_per_type ) - $posts_per_type['trash'];
				return "<a href='{$base_url}'$class>" . sprintf( _nx( 'All', 'All', $total_items, 'uploaded files', 'media-library-assistant' ) . ' <span class="count">(%1$s)</span></a>', number_format_i18n( $total_items ) );
			case 'unattached':
				$total_items = $wpdb->get_var(
						"
						SELECT COUNT( * ) FROM {$wpdb->posts}
						WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent < 1
						"
				);

				if ( $total_items ) {
					$value = MLAOptions::$mla_option_definitions[ MLAOptions::MLA_POST_MIME_TYPES ]['std']['unattached'];
					$singular = sprintf('%s <span class="count">(%%s)</span>', $value['singular'] );
					$plural = sprintf('%s <span class="count">(%%s)</span>', $value['plural'] );
					return '<a href="' . add_query_arg( array( 'detached' => '1' ), $base_url ) . '"' . $class . '>' . sprintf( _nx( $singular, $plural, $total_items, 'detached files', 'media-library-assistant' ), number_format_i18n( $total_items ) ) . '</a>';
				}

				return false;
			case 'trash':
				if ( $posts_per_type['trash'] ) {
					$value = MLAOptions::$mla_option_definitions[ MLAOptions::MLA_POST_MIME_TYPES ]['std']['trash'];
					$singular = sprintf('%s <span class="count">(%%s)</span>', $value['singular'] );
					$plural = sprintf('%s <span class="count">(%%s)</span>', $value['plural'] );
					return '<a href="' . add_query_arg( array(
						 'status' => 'trash' 
					), $base_url ) . '"' . $class . '>' . sprintf( _nx( $singular, $plural, $posts_per_type['trash'], 'uploaded files', 'media-library-assistant' ), number_format_i18n( $posts_per_type['trash'] ) ) . '</a>';
				}

				return false;
		} // switch special cases

		/*
		 * Make sure the slug is in our list
		 */
		if ( array_key_exists( $view_slug, $mla_types ) ) {
			$mla_type = $mla_types[ $view_slug ];
		} else {
			return false;
		}

		/*
		 * Handle post_mime_types
		 */
		if ( $mla_type->post_mime_type ) {
			if ( !empty( $num_posts[ $view_slug ] ) ) {
				return "<a href='" . add_query_arg( array(
					 'post_mime_type' => $view_slug 
				), $base_url ) . "'$class>" . sprintf( translate_nooped_plural( $post_mime_types[ $view_slug ][ 2 ], $num_posts[ $view_slug ], 'media-library-assistant' ), number_format_i18n( $num_posts[ $view_slug ] ) ) . '</a>';
			}

			return false;
		}

		/*
		 * Handle extended specification types
		 */
		if ( empty( $mla_type->specification ) ) {
			$query = array ( 'post_mime_type' => $view_slug );
		} else {
			$query = MLAMime::mla_prepare_view_query( $view_slug, $mla_type->specification );
		}

		$total_items = MLAData::mla_count_list_table_items( $query );
		if ( $total_items ) {
			$singular = sprintf('%s <span class="count">(%%s)</span>', $mla_type->singular );
			$plural = sprintf('%s <span class="count">(%%s)</span>', $mla_type->plural );
			$nooped_plural = _n_noop( $singular, $plural, 'media-library-assistant' );

			if ( isset( $query['post_mime_type'] ) ) {
				$query['post_mime_type'] = urlencode( $query['post_mime_type'] );
			} else {
				$query['meta_slug'] = $view_slug;
				$query['meta_query'] = urlencode( serialize( $query['meta_query'] ) );
			}

			return "<a href='" . add_query_arg( $query, $base_url ) . "'$class>" . sprintf( translate_nooped_plural( $nooped_plural, $total_items, 'media-library-assistant' ), number_format_i18n( $total_items ) ) . '</a>';
		}

		return false;
	} // _get_view

	/**
	 * Returns an associative array listing all the views that can be used with this table.
	 * These are listed across the top of the page and managed by WordPress.
	 *
	 * @since 0.1
	 * 
	 * @return	array	View information,e.g., array ( id => link )
	 */
	function get_views( ) {
		/*
		 * Find current view
		 */
		if ( $this->detached  ) {
			$current_view = 'unattached';
		} elseif ( $this->is_trash ) {
			$current_view = 'trash';
		} elseif ( empty( $_REQUEST['post_mime_type'] ) ) {
			if ( isset( $_REQUEST['meta_query'] ) ) {
				$query = unserialize( stripslashes( $_REQUEST['meta_query'] ) );
				$current_view = $query['slug'];
			} else {
				$current_view = 'all';
			}
		} else {
			$current_view = $_REQUEST['post_mime_type'];
		}

		$mla_types = MLAMime::mla_query_view_items( array( 'orderby' => 'menu_order' ), 0, 0 );
		if ( ! is_array( $mla_types ) ) {
			$mla_types = array ();
		}

		/*
		 * Filter the list, generate the views
		 */
		$view_links = array();
		foreach ( $mla_types as $value ) {
			if ( $value->table_view ) {
				if ( $current_view == $value->specification ) {
					$current_view = $value->slug;
				}

				if ( $link = self::_get_view( $value->slug, $current_view ) ) {
					// WPML Media looks for "detached", not "unattached"
					if ( 'unattached' == $value->slug ) {
						$view_links[ 'detached' ] = $link;
					} else {
						$view_links[ $value->slug ] = $link;
					}
				}
			}
		}

		return $view_links;
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @since 0.1
	 * 
	 * @return	array	Contains all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	function get_bulk_actions( ) {
		$actions = array();

		if ( $this->is_trash ) {
			$actions['restore'] = __( 'Restore', 'media-library-assistant' );
			$actions['delete'] = __( 'Delete Permanently', 'media-library-assistant' );
		} else {
			$actions['edit'] = __( 'Edit', 'media-library-assistant' );

			if ( EMPTY_TRASH_DAYS && MEDIA_TRASH ) {
				$actions['trash'] = __( 'Move to Trash', 'media-library-assistant' );
			} else {
				$actions['delete'] = __( 'Delete Permanently', 'media-library-assistant' );
			}
		}

		return apply_filters( 'mla_list_table_get_bulk_actions', $actions );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * Modeled after class-wp-posts-list-table.php in wp-admin/includes.
	 *
	 * @since 0.1
	 * 
	 * @param	string	'top' or 'bottom', i.e., above or below the table rows
	 *
	 * @return	array	Contains all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	function extra_tablenav( $which ) {
		echo ( '<div class="alignleft actions">' );

		if ( 'top' == $which ) {
			$this->months_dropdown( 'attachment' );

			echo self::mla_get_taxonomy_filter_dropdown( isset( $_REQUEST['mla_filter_term'] ) ? $_REQUEST['mla_filter_term'] : 0 );

			submit_button( __( 'Filter', 'media-library-assistant' ), 'secondary', 'mla_filter', false, array(
				 'id' => 'post-query-submit' 
			) );

			submit_button( __( 'Terms Search', 'media-library-assistant' ), 'secondary', 'mla_filter', false, array(
				 'id' => 'mla-terms-search-open', 'onclick' => 'mlaTaxonomy.termsSearch.open()' 
			) );
		}

		if ( self::mla_submenu_arguments( true ) != self::mla_submenu_arguments( false ) ) {
			submit_button( __( 'Clear Filter-by', 'media-library-assistant' ), 'button apply', 'clear_filter_by', false );
		}

		if ( $this->is_trash && current_user_can( 'edit_others_posts' ) ) {
			submit_button( __( 'Empty Trash', 'media-library-assistant' ), 'button apply', 'delete_all', false );
		}

		echo ( '</div>' );
	}

	/**
	 * Prepares the list of items for displaying
	 *
	 * This is where you prepare your data for display. This method will usually
	 * be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args().
	 *
	 * @since 0.1
	 *
	 * @return	void
	 */
	function prepare_items( ) {
		// Initialize $this->_column_headers
		$this->get_column_info();

		/*
		 * Calculate and filter pagination arguments.
		 */
		$user = get_current_user_id();
		$option = $this->screen->get_option( 'per_page', 'option' );
		$per_page = (integer) get_user_meta( $user, $option, true );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = (integer) $this->screen->get_option( 'per_page', 'default' );
		}

		$current_page = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;

		$pagination = apply_filters_ref_array( 'mla_list_table_prepare_items_pagination', array( compact( array( 'per_page', 'current_page' ) ), &$this ) );
		$per_page = isset( $pagination[ 'per_page' ] ) ? $pagination[ 'per_page' ] : $per_page;
		$current_page = isset( $pagination[ 'current_page' ] ) ? $pagination[ 'current_page' ] : $current_page;

		/*
		 * Assign sorted and paginated data to the items property, where 
		 * it can be used by the rest of the class.
		 */
		$total_items = apply_filters_ref_array( 'mla_list_table_prepare_items_total_items', array( NULL, &$this ) );
		if ( is_null( $total_items ) ) {
			$total_items = MLAData::mla_count_list_table_items( $_REQUEST, ( ( $current_page - 1 ) * $per_page ), $per_page );
		}
		
		/*
		 * Register the pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page' => $per_page, //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page ) //WE have to calculate the total number of pages
		) );

		$this->items = apply_filters_ref_array( 'mla_list_table_prepare_items_the_items', array( NULL, &$this ) );
		if ( is_null( $this->items ) ) {
			$this->items = MLAData::mla_query_list_table_items( $_REQUEST, ( ( $current_page - 1 ) * $per_page ), $per_page );
		}

		do_action_ref_array( 'mla_list_table_prepare_items', array( &$this ) );
	}

	/**
	 * Generates (echoes) content for a single row of the table
	 *
	 * @since .20
	 *
	 * @param object the current item
	 *
	 * @return void Echoes the row HTML
	 */
	function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr id="attachment-' . $item->ID . '"' . $row_class . '>';
		echo parent::single_row_columns( $item );
		echo '</tr>';
	}
} // class MLA_List_Table

/*
 * Some actions and filters are added here, when the source file is loaded, because the
 * MLA_List_Table object is created too late to be useful.
 */
add_action( 'admin_init', 'MLA_List_Table::mla_admin_init_action' );
 
add_filter( 'get_user_option_managemedia_page_' . MLA::ADMIN_PAGE_SLUG . 'columnshidden', 'MLA_List_Table::mla_manage_hidden_columns_filter', 10, 3 );
add_filter( 'manage_media_page_' . MLA::ADMIN_PAGE_SLUG . '_columns', 'MLA_List_Table::mla_manage_columns_filter', 10, 0 );
?>