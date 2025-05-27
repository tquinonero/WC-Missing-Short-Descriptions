<?php
/**
 * Plugin Name: WC Missing Short Descriptions
 * Description: Lists WooCommerce products that do not have a short description in a custom admin page.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wc-missing-short-desc
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Main Plugin Class
class WC_Missing_Short_Desc {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function register_admin_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Missing Short Descriptions', 'wc-missing-short-desc' ),
            __( 'Missing Short Descriptions', 'wc-missing-short-desc' ),
            'manage_woocommerce',
            'wc-missing-short-desc',
            [ $this, 'render_admin_page' ]
        );
    }

    public function enqueue_admin_assets($hook) {
        if ( strpos( $hook, 'wc-missing-short-desc' ) !== false ) {
            wp_enqueue_style( 'wcmsd-admin-style', plugin_dir_url( __FILE__ ) . 'assets/styles.css' );
        }
    }

    public function render_admin_page() {
        // Get current page number
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        // Get paginated products
        $products = $this->get_products_missing_short_desc($current_page, $per_page);
        $total_products = $this->get_total_products_missing_short_desc();
        $total_pages = ceil($total_products / $per_page);

        echo '<div class="wrap wcmsd-admin">';
        echo '<h1>' . esc_html__('Products Missing Short Descriptions', 'wc-missing-short-desc') . '</h1>';
        
        // Add pagination info
        echo '<div class="tablenav top">';
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . sprintf(
            _n('%s item', '%s items', $total_products, 'wc-missing-short-desc'),
            number_format_i18n($total_products)
        ) . '</span>';
        
        if ($total_pages > 1) {
            echo '<span class="pagination-links">';
            
            // First page link
            if ($current_page > 1) {
                echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1)) . '"><span class="screen-reader-text">' . __('First page', 'wc-missing-short-desc') . '</span><span aria-hidden="true">&laquo;</span></a>';
            }
            
            // Previous page link
            if ($current_page > 1) {
                echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '"><span class="screen-reader-text">' . __('Previous page', 'wc-missing-short-desc') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
            }
            
            // Current page info
            echo '<span class="paging-input">' . $current_page . ' of <span class="total-pages">' . $total_pages . '</span></span>';
            
            // Next page link
            if ($current_page < $total_pages) {
                echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '"><span class="screen-reader-text">' . __('Next page', 'wc-missing-short-desc') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
            }
            
            // Last page link
            if ($current_page < $total_pages) {
                echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages)) . '"><span class="screen-reader-text">' . __('Last page', 'wc-missing-short-desc') . '</span><span aria-hidden="true">&raquo;</span></a>';
            }
            
            echo '</span>';
        }
        echo '</div></div>';

        echo '<table class="widefat fixed striped wcmsd-table">';
        echo '<thead><tr><th>' . __( 'Thumbnail', 'wc-missing-short-desc' ) . '</th><th>' . __( 'Product Name', 'wc-missing-short-desc' ) . '</th><th>' . __( 'SKU', 'wc-missing-short-desc' ) . '</th><th>' . __( 'Edit', 'wc-missing-short-desc' ) . '</th></tr></thead><tbody>';

        if ( ! empty( $products ) ) {
            foreach ( $products as $product ) {
                $thumbnail = get_the_post_thumbnail( $product->get_id(), [50, 50], [ 'class' => 'wcmsd-thumb' ] );
                $edit_link = get_edit_post_link( $product->get_id() );
                echo '<tr>';
                echo '<td>' . $thumbnail . '</td>';
                echo '<td>' . esc_html( $product->get_name() ) . '</td>';
                echo '<td>' . esc_html( $product->get_sku() ) . '</td>';
                echo '<td><a href="' . esc_url( $edit_link ) . '" class="button button-primary">' . __( 'Edit', 'wc-missing-short-desc' ) . '</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4">' . __( 'All products have short descriptions.', 'wc-missing-short-desc' ) . '</td></tr>';
        }

        echo '</tbody></table></div>';
    }

    public function get_products_missing_short_desc($page = 1, $per_page = 20) {
        if (!class_exists('WC_Product_Query')) {
            return [];
        }

        $query = new WC_Product_Query([
            'status' => 'publish',
            'limit' => $per_page,
            'page' => $page,
            'return' => 'objects',
            'lang' => 'es',
        ]);

        $products = $query->get_products();
        $missing = [];

        foreach ($products as $product) {
            $desc = $product->get_short_description();
            if (empty($desc)) {
                $missing[] = $product;
            }
        }

        return $missing;
    }

    public function get_total_products_missing_short_desc() {
        if (!class_exists('WC_Product_Query')) {
            return 0;
        }

        $query = new WC_Product_Query([
            'status' => 'publish',
            'limit' => -1,
            'return' => 'ids',
            'lang' => 'es',
        ]);

        $products = $query->get_products();
        $missing = 0;

        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            if ($product && empty($product->get_short_description())) {
                $missing++;
            }
        }

        return $missing;
    }
}

new WC_Missing_Short_Desc();
