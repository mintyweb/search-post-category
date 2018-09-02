<?php
/*
Plugin Name: Search Post Category
Description: Enables searching categories and other hierarchical taxonomies when posting a post/product
Version:     1.0
Plugin URI:  #
Author:      Minty
Author URI:  https://minty.co.il
Text Domain: wpsearchcat
*/

/*  Copyright 2018 Minty (email: info@minty.co.il)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class spc_settings {

    public $spc_posts_value;
    public $spc_products_value;

    public function __construct() {

        add_action( 'admin_menu', array( $this, 'spc_add_options_page' ) );
        register_activation_hook( __FILE__, array( $this, 'spc_add_default_settings' ) );

        $this -> spc_posts_value = get_option( 'spc_posts' );
        $this -> spc_products_value = get_option( 'spc_products' );

    }

    function spc_add_default_settings() {
        if ($this -> spc_posts_value === false && $this -> spc_products_value == false) {
            update_option( 'spc_posts', '1' );
            update_option( 'spc_products', '1' );
        }
    }

    function spc_add_options_page() {
        add_options_page( 'Search Post Category', 'Search Post Category', 'manage_options', 'searchpostcategory', array( $this, 'spc_options_page' ) );
    }

    function spc_options_page() {
        if ( current_user_can( 'manage_options' ) ) {

            $safe_values = array( '0', '1' );
            $spc_hidden_field_name = 'spc_submit_hidden';
            $change_flag = '0';

            if ( ! empty( $_POST[ $spc_hidden_field_name ] ) ) {
                if ( in_array( $_POST[ $spc_hidden_field_name ], $safe_values, true ) ) {
                    $change_flag = $_POST[ $spc_hidden_field_name ];
                } else {
                    wp_die( 'Invalid data' );
                }
            }

            if ( ! empty( $_POST ) && check_admin_referer( 'change-settings', 'settings-nonce-field' ) ) {

                if ( $change_flag === '1' ) {

                    if ( ! empty( $_POST[ 'spc_posts' ] ) ) {
                        if ( in_array( $_POST[ 'spc_posts' ], $safe_values, true ) ) {
                            $this -> spc_posts_value = $_POST[ 'spc_posts' ];
                        } else {
                            wp_die( 'Invalid data' );
                        }
                    } else {
                        $this -> spc_posts_value = 0;
                    }
                    update_option( 'spc_posts', $this -> spc_posts_value );

                    if ( ! empty( $_POST[ 'spc_products' ] ) ) {
                        if ( in_array( $_POST[ 'spc_products' ], $safe_values, true ) ) {
                            $this -> spc_products_value = $_POST[ 'spc_products' ];
                        } else {
                            wp_die( 'Invalid data' );
                        }
                    } else {
                        $this -> spc_products_value = 0;
                    }
                    update_option( 'spc_products', $this -> spc_products_value );

                    ?>
                    <div class="updated"><p><strong><?php _e('Options saved.', 'spc_trans_domain' ); ?></strong></p></div>
                    <?php
                }
            }
            ?>
            <div class="wrap">

                <h2><?php _e( 'Search Post Category Settings', 'spc_trans_domain' ) ?></h2>

                <hr>

                <form name="spc-settings-form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
                    <?php wp_nonce_field( 'change-settings', 'settings-nonce-field' ); ?>

                    <input type="hidden" name="<?php echo $spc_hidden_field_name; ?>" value="1">

                    <div>
                        <p class="spc-input-title"><?php _e("Posts", 'spc_trans_domain' ); ?></p>
                        <input type="checkbox" class="spc-checkbox" name="spc_posts" value="1" <?php if ($this -> spc_posts_value === '1') echo 'checked' ?>>
                    </div>

                    <div>
                        <p class="spc-input-title"><?php _e("Products", 'spc_trans_domain' ); ?></p>
                        <input type="checkbox" class="spc-checkbox" name="spc_products" value="1" <?php if ($this -> spc_products_value === '1') echo 'checked' ?>>
                    </div>

                    <div class="submit">
                        <input type="submit" name="Submit" class="button button-primary" value="<?php _e('Save', 'spc_trans_domain' ) ?>" />
                    </div>

                </form>

                <hr>

                <div>
                    <p class="spc-url-container">
                        <a class="spc-url" target="_blank" href="https://minty.co.il">Minty.co.il</a>
                    </p>

                    <hr>

                    <div class="postbox spc-message">
                        <p><?php _e('Minty develops plugins and other Wordpress solutions. If you enjoyed this one, show your appreciation by giving a 5 star review!', 'spc_trans_domain' ) ?></p>
                    </div>

                    <hr>
                </div>

            </div>
            <?php
        }
    }

    /**
     * Create instance.
     *
     * @return spc_settings instance.
     */
    public static function get_instance() {
        static $instance;

        if ( ! isset( $instance ) ) {
            $instance = new spc_settings;
        }

        return $instance;
    }
}

class spc_search {

    private $text_domain = 'wpsearchtax';
    private $nonce = 'wpsearchtax_nonce';

    public $metaboxes;
    public $metaboxes_ids;

    public $spc_posts_value;
    public $spc_products_value;

    public function __construct() {

        add_action( 'admin_menu' , array( $this, 'spc_remove_metaboxes' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'spc_enqueue_assets' ) );
        add_action( 'save_post', array( $this, 'spc_save_terms' ) );

        $this -> spc_posts_value = get_option( 'spc_posts' );
        $this -> spc_products_value = get_option( 'spc_products' );

        $this -> metaboxes_ids = array(
            'category' => 'categorydiv',
            'product_cat' => 'product_catdiv',
        );

        $this -> metaboxes = array(
            'category' => array()
        );

        if ($this -> spc_posts_value === '1') {
            array_push($this -> metaboxes['category'], "post");
        }
        if ($this -> spc_products_value === '1') {
            array_push($this -> metaboxes['category'], "product");
        }
    }

    /**
     * Removing the default meta boxes. Adding them later
     */
    public function spc_remove_metaboxes() {
        $added_post_type = '';
        $get_post_type = '';

        if ( ! empty( $_GET[ 'post' ] ) ) {
            if ( is_numeric( $_GET[ 'post' ] ) ) {
                $added_post_type = get_post($_GET['post'])->post_type;
            }
        }

        if ( ! empty( $_GET[ 'post_type' ] ) ) {
            if ( $_GET[ 'post_type' ] === 'post' || $_GET[ 'post_type' ] === 'product' ) {
                $get_post_type = $_GET['post_type'];
            }
        }

        if ( empty( $this -> metaboxes ) || ! is_array( $this -> metaboxes ) ) {
            return false;
        }

        foreach ( $this -> metaboxes as $taxonomy => $post_types ) {

            if ( $get_post_type === 'product' || $added_post_type === 'product' ) {
                $taxonomy = 'product_cat';
            }

            if ( is_array( $post_types ) ) {
                foreach ( $post_types as $post_type ) {
                    if ( isset ( $this -> metaboxes_ids[ $taxonomy ] ) ) {
                        remove_meta_box( $this -> metaboxes_ids[ $taxonomy ], $post_type, 'normal' );
                        add_meta_box( $this -> metaboxes_ids[ $taxonomy ] . '-select2', __( 'Categories', $this -> text_domain ), array( $this, 'spc_metabox_display' ), $post_type, 'side', 'default', array( 'taxonomy' => $taxonomy ) );
                    }
                }

            }
        }
    }

    /**
     * Renders the html for the box
     * @param $post WP_Post, $args array
     */
    public function spc_metabox_display( $post, $args ) {
        if ( ! isset ( $args[ 'args' ] ) ) {
            return false;
        }

        $term_args = array(
            'hide_empty' => false
        );
        $terms = get_terms( $args[ 'args' ][ 'taxonomy' ], $term_args );

        if ( ! empty( $terms ) && is_array( $terms ) ) {
            wp_nonce_field( $this -> nonce, $this -> nonce );
            echo '<div class="' . $this -> text_domain . '-wrap"><select name="' . $this -> text_domain . '[' . $args[ 'args' ][ 'taxonomy' ] . '][]" multiple="multiple" class="' . $this -> text_domain . '-select2">';
            foreach ( $terms as $term ) {
                if ( has_term( $term->term_id, $args[ 'args' ][ 'taxonomy' ], $post ) ) {
                    echo '<option selected value="' . $term -> term_id . '">' . $term -> name . '</option>';
                }
                else {
                    echo '<option value="' . $term -> term_id . '">' . $term -> name . '</option>';
                }


            }
            echo '</select></div>';
            echo '<input type="hidden" name="' . $this -> text_domain . '_taxholder" value="' . $args[ 'args' ][ 'taxonomy' ] . '">';
        }

    }

    /**
     * Enqueuing select2 and plugin assets
     */
    public function spc_enqueue_assets(){

        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery') );
        wp_enqueue_script('' . $this -> text_domain, plugin_dir_url( __FILE__ ) . 'assets/js/wp-spc.js', array( 'jquery', 'select2' ) );
        wp_enqueue_style( '' . $this -> text_domain . '-css', plugin_dir_url( __FILE__ ) . 'assets/css/wp-spc.css' );

    }

    /**
     * Saves the terms
     */
    public function spc_save_terms( $post_id ) {

        if ( ! isset( $_POST[ $this -> nonce ] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST[ $this -> nonce ], $this -> nonce ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST[ 'post_type' ] ) && 'page' == $_POST[ 'post_type' ] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return;
            }

        }
        else {

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }
        if ( isset( $_POST[ $this -> nonce ] ) && ! isset( $_POST[ $this -> text_domain ] ) ) {
            // get taxonomy from helper
            if ( isset( $_POST[ $this -> text_domain . '_taxholder' ] ) ) {
                wp_delete_object_term_relationships( $post_id, $_POST[ $this -> text_domain . '_taxholder']);
            }

        }
        else if ( isset( $_POST[ $this -> text_domain ] ) && is_array( $_POST[ $this -> text_domain ] ) ) {
            foreach ( $_POST[ $this -> text_domain ] as $taxonomy => $terms ) {
                wp_set_post_terms( $post_id, $terms, $taxonomy, false );
            }
        }


    }

    /**
     * Create instance.
     *
     * @return spc_search instance.
     */
    public static function get_instance() {
        static $instance;

        if ( ! isset( $instance ) ) {
            $instance = new spc_search;
        }

        return $instance;
    }
}

/**
 * Settings instance.
 *
 * Returns the settings instance of plugin to prevent the need to use globals.
 *
 * @return spc_settings
 */
if(!function_exists('wp_spc_settings')) {
    function wp_spc_settings() {
        return spc_settings::get_instance();
    }

    wp_spc_settings();
}

/**
 * Search instance.
 *
 * Returns the search instance of plugin to prevent the need to use globals.
 *
 * @return spc_search
 */
if(!function_exists('wp_spc_search')) {
    function wp_spc_search() {
        return spc_search::get_instance();
    }

    wp_spc_search();
}