<?php
/**
 * Plugin Name: GazChap's WooCommerce Auto Category Product Thumbnails
 * Plugin URI: https://www.gazchap.com/posts/woocommerce-category-product-thumbnails/
 * Version: 1.2.1
 * Author: Gareth 'GazChap' Griffiths
 * Author URI: https://www.gazchap.com/
 * Description: Automatically use a product thumbnail as a category thumbnail if no category thumbnail is set
 * Tested up to: 5.4.2
 * WC requires at least: 3.0.0
 * WC tested up to: 4.2.0
 * Text Domain: gazchaps-woocommerce-auto-category-product-thumbnails
 * Domain Path: /lang
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Donate link: https://paypal.me/gazchap
 */

namespace GazChap;

class WC_Category_Product_Thumbnails {

	private $shuffle;
	private $recurse_category_ids;
	private $image_size;

	/**
	 * WC_Category_Product_Thumbnails constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'replace_wc_actions' ) );
		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
		add_action( 'admin_init', array( $this, 'check_woocommerce_is_activated' ) );
	}

	public function activation() {
		update_option( 'gazchaps-woocommerce-auto-category-product-thumbnails_shuffle', 'yes' );
		update_option( 'gazchaps-woocommerce-auto-category-product-thumbnails_recurse', 'yes' );
		update_option( 'gazchaps-woocommerce-auto-category-product-thumbnails_category-size', 'shop_thumbnail' );
	}

	public function add_settings_link( $links ) {
		if ( !is_array( $links ) ) {
			$links = array();
		}
		$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=products&section=gazchaps-woocommerce-auto-category-product-thumbnails' ) . '">' . __( 'Settings', 'gazchaps-woocommerce-auto-category-product-thumbnails' ) . '</a>';
		return $links;
	}

	/**
	 * Check if WooCommerce is active - if not, then deactivate this plugin and show a suitable error message
	 */
	public function check_woocommerce_is_activated(){
	    if ( is_admin() ) {
	        if ( !class_exists( 'WooCommerce' ) ) {
	            add_action( 'admin_notices', array( $this, 'woocommerce_deactivated_notice' ) );
	            deactivate_plugins( plugin_basename( __FILE__ ) );
	        }
	    }
	}

	public function woocommerce_deactivated_notice() {
	    ?>
	    <div class="notice notice-error"><p><?php esc_html_e( 'GazChap\'s WooCommerce Auto Category Product Thumbnails requires WooCommerce to be installed and activated.', 'gazchaps-woocommerce-auto-category-product-thumbnails' ) ?></p></div>
	    <?php
	}

	/**
	 * Removes the action that puts the thumbnail before the subcategory title, and replaces it with our version
	 */
	public function replace_wc_actions() {
		remove_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail', 10 );
		add_action( 'woocommerce_before_subcategory_title', array( $this, 'auto_subcategory_thumbnail' ) );
		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_setting_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'add_settings_to_section' ), 10, 2 );
	}

	/**
	 * The function that does all the donkey work.
	 * @param \WP_Term $category - the category that we're dealing with
	 */
	public function auto_subcategory_thumbnail( $category ) {

		$this->shuffle = ( get_option('gazchaps-woocommerce-auto-category-product-thumbnails_shuffle') == 'yes' ) ? true : false;
		$this->recurse_category_ids = ( get_option('gazchaps-woocommerce-auto-category-product-thumbnails_recurse') == 'yes' ) ? true : false;
		$this->image_size = get_option('gazchaps-woocommerce-auto-category-product-thumbnails_category-size');

		// does this category already have a thumbnail defined? if so, use that instead
		if ( get_term_meta( $category->term_id, 'thumbnail_id', true ) ) {
			woocommerce_subcategory_thumbnail( $category );
			return;
		}

		// get a list of category IDs inside this category (so we're fetching products from all subcategories, not just the top level one)
		if ( $this->recurse_category_ids ) {
			$category_ids = $this->get_sub_category_ids( $category );
		} else {
			$category_ids = array( $category->term_id );
		}

		$query_args = array(
			'posts_per_page' => 1,
			'post_status' => 'publish',
			'post_type' => 'product',
			'meta_query' => array(
				array(
					'key' => '_thumbnail_id',
					'value' => '',
					'compare' => '!=',
				),
			),
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field' => 'term_id',
					'terms' => $category_ids,
					'operator' => 'IN',
				),
			),
		);
		if ( $this->shuffle ) {
			$query_args['orderby'] = 'rand';
		}

		$products = get_posts( $query_args );
		if ( $products ) {
			$product = current( $products );
			echo get_the_post_thumbnail( $product->ID, $this->image_size );
		} else {
			// show the default placeholder category image if there's no products inside this one
			woocommerce_subcategory_thumbnail( $category );
		}
	}

	/**
	 * Recursive function to fetch a list of child category IDs for the one passed
	 *
	 * @param \WP_Term $start - the category to start from
	 * @param array $results - this just stores the results as they're being built up
	 *
	 * @return array - an array of term IDs for each product_cat inside the original one
	 */
	private function get_sub_category_ids( $start, $results = array() ) {
		if ( !is_array( $results ) ) $results = array();

		$results[] = $start->term_id;
		$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => $start->term_id ) );
		if ( is_array( $cats ) ) {
			foreach( $cats as $cat ) {
				$results = $this->get_sub_category_ids( $cat, $results );
			}
		}

		return $results;
	}

	function add_setting_section( $sections ) {

		$sections['gazchaps-woocommerce-auto-category-product-thumbnails'] = __( 'Auto Category Thumbnails', 'gazchaps-woocommerce-auto-category-product-thumbnails' );
		return $sections;

	}

	function add_settings_to_section( $settings, $current_section ) {
		/**
		 * Check the current section is what we want
		 **/
		if ( $current_section == 'gazchaps-woocommerce-auto-category-product-thumbnails' ) {
			$new_settings = array();
			// Add Title to the Settings
			$new_settings[] = array( 'name' => __( 'Auto Category Thumbnails Settings', 'gazchaps-woocommerce-auto-category-product-thumbnails' ), 'type' => 'title', 'id' => 'gazchaps-woocommerce-auto-category-product-thumbnails' );

			$temp = $this->_get_all_image_sizes();
			$image_sizes = array();
			foreach( $temp as $image_size => $image_spec_array ) {
				$image_spec = "";
				$image_spec .= ($image_spec_array['width'] > 0) ? $image_spec_array['width'] : __('auto', 'gazchaps-woocommerce-auto-category-product-thumbnails' );
				$image_spec .= ' x ';
				$image_spec .= ($image_spec_array['height'] > 0) ? $image_spec_array['height'] : __('auto', 'gazchaps-woocommerce-auto-category-product-thumbnails' );
				if ( $image_spec_array['crop'] ) {
					$image_spec .= ", " . __('cropped', 'gazchaps-woocommerce-auto-category-product-thumbnails' );
				}
				$image_sizes[ $image_size ] = $image_size . " (" . $image_spec . ")";
			}

			$new_settings[] = array(
				'name'     => __( 'Thumbnail Size', 'gazchaps-woocommerce-auto-category-product-thumbnails' ),
				'id'       => 'gazchaps-woocommerce-auto-category-product-thumbnails_category-size',
				'type'     => 'select',
				'options' => $image_sizes,
				'desc'     => __( 'Choose the image size to use for the thumbnails', 'gazchaps-woocommerce-auto-category-product-thumbnails' ),
			);

			$new_settings[] = array(
				'name'     => __( 'Go into Child Categories', 'gazchaps-woocommerce-auto-category-product-thumbnails' ),
				'id'       => 'gazchaps-woocommerce-auto-category-product-thumbnails_recurse',
				'type'     => 'checkbox',
				'desc'     => __( 'If ticked, the plugin will also search for product thumbnails in any child categories. If not ticked, it will stay on the same level.', 'gazchaps-woocommerce-auto-category-product-thumbnails' ),
			);

			$new_settings[] = array(
				'name'     => __( 'Random Thumbnail', 'gazchaps-woocommerce-auto-category-product-thumbnails' ),
				'id'       => 'gazchaps-woocommerce-auto-category-product-thumbnails_shuffle',
				'type'     => 'checkbox',
				'desc'     => __( 'If ticked, the plugin will pick a thumbnail at random from those available. If not ticked, it will always use the first one it finds.', 'gazchaps-woocommerce-auto-category-product-thumbnails' ),
			);

			$new_settings[] = array( 'type' => 'sectionend', 'id' => 'gazchaps-woocommerce-auto-category-product-thumbnails' );
			return $new_settings;

		/**
		 * If not, return the standard settings
		 **/
		} else {
			return $settings;
		}
	}

	private function _get_all_image_sizes() {
	    global $_wp_additional_image_sizes;

	    $default_image_sizes = get_intermediate_image_sizes();

	    foreach ( $default_image_sizes as $size ) {
	        $image_sizes[ $size ][ 'width' ] = intval( get_option( "{$size}_size_w" ) );
	        $image_sizes[ $size ][ 'height' ] = intval( get_option( "{$size}_size_h" ) );
	        $image_sizes[ $size ][ 'crop' ] = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
	    }

	    if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
	        $image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
	    }

	    return $image_sizes;
	}

}

new WC_Category_Product_Thumbnails();