<?php

/**
* Plugin Name: WooSalsify
* Plugin URI: 
* Description: The WooSalsify plugin allows you to actively sync your Salsify product listings with your WooCommerce products. Using your Salsify feed as your GDSM you can now have your products listed with the power of WooCommerce.
* Version: 1.0.0
* Author: Sod
* Author URI:
* Text Domain:
* Domain Path:
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SALSIFY_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define( 'SALSIFY_PLUGIN_PATH', plugin_dir_path(__FILE__));

define( 'SALSIFY_OPT_TIME', 'salisy-time' );
define( 'SALSIFY_OPT_REVISION', 'salsify-rivision' );

final class Salsify_Plugin {

    public $version = '1.0.0';
    protected static $_instance = null;
    public $feed = null;
    protected $api_url = "";

    public function __construct() {
		$this->load_dependencies();
		$this->add_hooks();
    }
    
    public function load_dependencies() {
        require_once(SALSIFY_PLUGIN_PATH . "/lib/feed.php");
        require_once(SALSIFY_PLUGIN_PATH . "/lib/product.php");
        $this->feed = new SOD_Feed();
    }

    public function add_hooks() {
        add_action( 'admin_menu', array($this, 'salsify_add_settings_page') );
        add_action( 'admin_init', array($this, 'admin_init'));
        add_action( 'init', array($this, 'wp_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_front_end'));

        add_action( 'wp_ajax_salsify_product', array( $this, 'salsify_product' ) );
        add_action( 'wp_ajax_nopriv_salsify_product', array( $this, 'salsify_product' ) );
        add_action( 'woocommerce_thankyou', array($this, 'woocommerce_thankyou'));
        add_filter('cron_schedules', array($this, 'my_cron_schedules'));
    }

    public function my_cron_schedules($schedules) {

        $options = self::get_options();
        $time = isset($options["hour"]) ? $options["hour"] : 1;
        
        if(!isset($schedules[$time . "hour"])){
            $schedules[$time . "hour"] = array(
                'interval' => $time *60 * 60,
                'display' => __("Once every $time hour"));
        }

        return $schedules;
    }

    public function set_schedule() {
        wp_clear_scheduled_hook( 'woocommerce_salsify_event' );

        if ( ! wp_next_scheduled( 'woocommerce_salsify_event' ) ) {
            $options = self::get_options();

            $name = isset($options["hour"]) ? $options["hour"] : 1;
            $time = time();
            
            wp_schedule_event( $time, $name . "hour", 'woocommerce_salsify_event');
            
        } else {
        }
    }

    public function enqueue_scripts_front_end() {
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_style( 'sod-salsify-feed', SALSIFY_PLUGIN_URL . "admin/assets/style.css", array(), $this->version);
        wp_enqueue_script( 'sod-salsify-drag', SALSIFY_PLUGIN_URL . "admin/assets/script.js", array('jquery'));
    }

    public function woocommerce_thankyou($order_id) {
        if ( ! $order_id )
            return;


        if( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {

            // Get an instance of the WC_Order object
            $order = wc_get_order( $order_id );

            $options = self::get_options();
            // Loop through order items
            foreach ( $order->get_items() as $item_id => $item ) {

                // Get the product object
                $product = $item->get_product();
                $stock = $product->get_stock_quantity();
                // Get the product Id
                $product_id = $product->get_id();
                
                $selected = get_post_meta($product_id, "_product_feed_account", true);

                if ($selected !== false)
                    $this->feed->update_product($selected, $product_id, $stock);
            }
        }

    }

    public function getCustomTaxonomies() {

        if (!defined("WPCF_OPTION_NAME_CUSTOM_TAXONOMIES")) {
            return [];
        }

        $data = array();
        global $wp_post_types;

        $custom_taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());
        


        foreach( $custom_taxonomies as $slug => $taxonomy ) {
            $supports = array();
          
            if( isset( $taxonomy['supports'] ) ) {

                if (in_array("product", array_keys($taxonomy['supports'] ))) {
                    $data[$taxonomy["slug"]] = isset($taxonomy['labels']['name']) ? wp_kses_post(stripslashes($taxonomy['labels']['name'])) : '';
                }
            }
        }
        
        return $data;
    }

    public function wp_init() {

            // var_dump ($taxonomy["slug"]);

            // $one = array(
            //     'description' => isset($taxonomy['description'])? wp_kses_post($taxonomy['description']):'',
            //     'supports' => $supports,
            //     'slug' => $taxonomy['slug'],
            //     'status' => (isset($taxonomy['disabled']) && $taxonomy['disabled'])? 'inactive':'active',
            //     'title' => isset($taxonomy['labels']['name']) ? wp_kses_post(stripslashes($taxonomy['labels']['name'])) : '',
            //     WPCF_AUTHOR => isset($taxonomy[WPCF_AUTHOR])? $taxonomy[WPCF_AUTHOR]:0,
            //     'type' => 'custom',
            //     '_builtin' => false,
            // );
            // $add_one = true;
            // if ( $s ) {
            //     $add_one = false;
            //     foreach( array('description', 'slug', 'title' ) as $key ) {
            //         if ( $add_one || empty( $one[$key] ) ) {
            //             continue;
            //         }
            //         if ( is_numeric(strpos(mb_strtolower($one[$key]), $s))) {
            //             $add_one = true;
            //         }
            //     }
            // }

            // if ( $add_one ) {
            //     $data[$one['slug']] = $one;
            // }
        
        // var_dump ($data);

        // $custom_taxonomies = get_option(WPCF_OPTION_NAME_CUSTOM_TAXONOMIES, array());

        // foreach( $custom_taxonomies as $tax ) {
        //     // if( in_array( "product", $tax["object_type"] ) )
        //         var_dump ($tax["name"]);
        // }

        // // var_dump ($custom_taxonomies);
        // exit;
        // $taxonomies = array();
        // $all_taxonomies = get_taxonomies( '', 'objects' );
        // // $all_taxonomies = get_object_taxonomies('product');
        // // echo count($all_taxonomies);

        // foreach( $all_taxonomies as $tax ) {
        //     if( $tax->show_ui && in_array( "product", $tax->object_type ) )
        //         var_dump ($tax->name);
        // }
        // exit;

        // if (isset($_POST["salsify-feed"])) {
        //     $options = self::get_options();

        //     if (empty($options)) {
        //         return false;
        //     }
            
        //     // $data = $this->feed->get_product();
        //     // var_dump ($data);
        //     // exit;
        // }
        // if (isset($_POST["start-feed"])) {
        //     update_option(SALSIFY_OPT_TIME, 0);
        // }

        // $time = time();
        // $old = get_option(SALSIFY_OPT_TIME, 0);
        // $options = self::get_options();

        // $hour = isset($options["hour"]) ? $options["hour"] : false;

        // if ($hour) {
        //     $second = $hour * 3600;
            
        //     if ( $time - $old >= $second ) {
        //         $script = SALSIFY_PLUGIN_PATH . "script.php";

        //         /// putenv("PATH=" . SALSIFY_PLUGIN_PATH);
        //         exec("php $script /dev/null 2>&1 &", $output, $return_var);
        //         var_dump ($output);
        //         exit;
                
        //         // exit;
        //     }    
        // }
    }

    public function admin_init() {
        register_setting( 'salsify_settings', 'salsify', array( $this, 'save_general_settings' ) );
    }

    public static function get_options() {
        $options  = (array) get_option( 'salsify', array() );
        return $options;
    }

    public function save_general_settings($settings) {
		
		$current = self::get_options();
        // var_dump ($current);
        // var_dump ($settings);
        
        $account = isset($settings["account"]) ? $settings["account"] : [];

        foreach ($account as $key => $a) {

            if (isset($a["activate"]))
                $this->feed->get_property_from_api($key, 1, $settings);            
        }

        $is_hour = isset($settings["hour"]) ? true : false;
        

        $settings = array_merge( $current, $settings );
        if ( $is_hour && !empty($settings["product"])) {

            $this->set_schedule();
        }

        // $current["feed"] = isset($current["feed"]) ? $current["feed"] : array();
        // // $current["account"] = isset($current["account"]) ? $current["account"] : array();
        // $current["filter"] = isset($current["filter"]) ? $current["filter"] : array();
        // $current["product"] = isset($current["product"]) ? $current["product"] : array();

        // $settings["feed"] = isset($settings["feed"]) ? $settings["feed"]: array();
        // // $settings["account"] = isset($settings["account"]) ? $settings["account"]: array();
        // $settings["product"] = isset($settings["product"]) ? $settings["product"]: array();
        // $settings["filter"] = isset($settings["filter"]) ? $settings["filter"]: array();

        // $settings1["feed"] = array_merge($current["feed"], $settings["feed"]);
        // $settings1["filter"] = array_merge($current["filter"], $settings["filter"]);
        // $settings1["product"] = array_merge($current["product"], $settings["product"]);

        // foreach ($settings["account"] as $key => $val) {
        //     $settings["feed"][$key] = $current["feed"][$key];
        //     $settings["filter"][$key] = $current["filter"][$key];
        // }

        // var_dump ($settings);
        // exit;

        $selected = $settings["selected"];
        foreach ($settings["account"] as $key => $a) {
            $filter = $this->feed->filter_maker($settings, $key);
            $settings["filter"][$key] = $filter;
        }
        
        
		return $settings;
	}

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
          self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function salsify_add_settings_page() {

        add_menu_page( 'WooSalsify', __( 'WooSalsify', 'woosalsify' ), 'moderate_comments', __FILE__, array($this, 'new_plugin_page'),   plugin_dir_url( __FILE__ ) . '/img/refresh.png' );

        add_submenu_page( __FILE__, __( 'Feeds', 'woosalsify' ), __( 'Feeds', 'woosalsify' ), 'manage_options', 'feed', array($this, 'salsify_render_plugin_settings_page') );

        add_submenu_page( __FILE__, __( 'Filters', 'woosalsify' ), __( 'Filters', 'woosalsify' ), 'manage_options', 'filters', array($this, 'salsify_add_filters') );

        add_submenu_page( __FILE__, __( 'Fields', 'woosalsify' ), __( 'Fields', 'woosalsify' ), 'manage_options', 'fields', array($this, 'salsify_add_fields') );

    }

    public function new_plugin_page() {
        require_once( SALSIFY_PLUGIN_PATH . '/admin/template/plugin.php' );
    }

    public function salsify_add_filters() {
        require_once( SALSIFY_PLUGIN_PATH . '/admin/template/multiple-feed.php' );
    }

    public function salsify_add_fields() {
        require_once( SALSIFY_PLUGIN_PATH . "/admin/template/matched-fields.php" );
    }

    public function salsify_render_plugin_settings_page() {

        $options = self::get_options();
        
        $accounts = isset($options["account"]) ? $options["account"] : array(array("api" => "", "org" => "", "activate" => 1));

        ?>

        <!-- 
        <?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'salsify-settings'; ?>

        <h2 class="nav-tab-wrapper">
            <a href="?page=Salsify&tab=salsify-settings" class="nav-tab <?php echo $active_tab == 'salsify-settings' ? 'nav-tab-active' : ''; ?>">Salsify Feeds</a>
            <a href="?page=Salsify&tab=multiple-feeds" class="nav-tab <?php echo $active_tab == 'multiple-feeds' ? 'nav-tab-active' : ''; ?>">Feed Filters</a>
            <a href="?page=Salsify&tab=matched-fields" class="nav-tab <?php echo $active_tab == 'matched-fields' ? 'nav-tab-active' : ''; ?>">Fields</a>
        </h2> -->

            <h2 class="salsify-sub-title">Salsify Feeds</h2>
            <form action="<?php echo admin_url( 'options.php' ); ?>" method="post">
                    <?php settings_fields( 'salsify_settings' ); ?>
                   
            <div class="accounts-wrapper">
            <?php
            foreach ($accounts as $key => $account):
            ?>
            <div class="account-item" data-id="<?php echo $key; ?>">
                <figure>
                    <?php 
                    $api = isset($account["api"]) ? $account["api"] : "";
                    $org = isset($account["org"]) ? $account["org"] : "";
                    $name = isset($account["name"]) ? $account["name"] : "";

                    $activate = isset($account["activate"]) ? $account["activate"] : 0;
                    
                    ?>
                    <table class="form-table">
                        <tr>
                            <th>Feed Name</th>
                            <th>Api Key</th>
                            <th>Org ID</th>
                            <th>Actions</th>
                        </tr>
                        <tr>                            
                            <td><input type="text" name="salsify[account][<?php echo $key; ?>][name]" value="<?php echo $name; ?>"/></td>
                            <td><input type="text" name="salsify[account][<?php echo $key; ?>][api]" value="<?php echo $api; ?>"/></td>                            
                            <td>
                                <input type="text" name="salsify[account][<?php echo $key; ?>][org]" value="<?php echo $org; ?>"/>
                            </td>
                            <td>
                                 <div class="account-action">
                    <label><input type="checkbox" class="button-activate-feed" name="salsify[account][<?php echo $key; ?>][activate]" <?php echo ($activate) ? 'checked="checked"' : ''; ?> value="<?php echo $activate; ?>"/> Activate</label>                    
                    <button type="button" class="button button-secondary button-delete-account">Delete Feed</button>
                    <button type="button"  class="button button-primary button-add-account">Add Feed</button>

                </div>
                            </td>
                        </tr>
                    </table>

               
                </figure>
            </div>
            <?php endforeach; ?>
            </div>
            <?php submit_button(); ?>

           
            </form>


            <script type="text/template" id="tmpl-s-account">
                <div class="account-item" data-id="{{data.id}}">
                    <figure>
                        <table class="form-table">
                            <tr>
                                <th>Feed Name</th>
                                <th>Api Key</th>
                                <th>Org ID</th>
                                <th>Actions</th>
                            </tr>
                            <tr>
                            
                                <td><input type="text" name="salsify[account][{{data.id}}][name]" class="sal-name" value=""/></td>
                            
                                <td><input type="text" name="salsify[account][{{data.id}}][api]" class="sal-api" value=""/></td>
                            
                                <td>
                                    <input type="text" name="salsify[account][{{data.id}}][org]" class="sal-org" value=""/>
                                </td>
                                <td>
                                    <div class="account-action">
                        <label><input type="checkbox" class="button-activate-feed" checked="true" name="salsify[account][{{data.id}}][activate]" value="1"/> Activate</label>                        
                        <button type="button" class="button button-secondary button-delete-account">Delete Feed</button>                        
                        <button type="button" class="button button-primary button-add-account">Add Feed</button>
                    </div>
                                </td>
                            </tr>
                        </table>
                    
                    </figure>
                </div>

            </script>

            <script type="text/javascript">
                jQuery(document).ready(function(e) {
                    jQuery(".accounts-wrapper").off("click").on("click", ".button-add-account", function(e) {
                        
                        var template = wp.template( 's-account' );
                        var id = jQuery(".accounts-wrapper .account-item:last-child").attr('data-id');
                        jQuery(".accounts-wrapper").append(template({id: parseInt(id) + 1}));
                    });

                    jQuery(".accounts-wrapper").on("click", ".button-delete-account", function(e) {
                        jQuery(this).parents(".account-item").remove();
                        jQuery(".accounts-wrapper").find(".account-item").each(function(index, e) {
                            jQuery(this).attr('data-id', index);
                            jQuery(this).find(".sal-api").attr('name', 'salsify[account][' + index + '][api]');
                            jQuery(this).find(".sal-org").attr('name', 'salsify[account][' + index + '][org]');
                        });
                    });

                    jQuery(".accounts-wrapper").on("click", ".button-activate-feed", function(e) {
                        var activate = jQuery(this).parent().find(".button-activate-feed").prop("checked");
                        
                        if (activate) {
                            jQuery(this).parent().find(".button-activate-feed").val(1);
                        } else {
                            jQuery(this).parent().find(".button-activate-feed").val(0);
                        }
                        
                    });                    
                });
            </script>
        <?php
       
        
    }

    public function salsify_product() {
        set_time_limit(0);
        error_reporting(0);
        
        $options = self::get_options();
        $product_opt = isset($options["product"]) ? $options["product"] : [];

        $offset = isset($_POST["page"]) ? $_POST["page"] : 1;
        $product_size = 10;
        
        $res = $this->feed->get_product(array('page' => $offset, 'per_page' => $product_size));
        $data = $res["data"];
        $product_args = SOD_Product::product_fields();
        $meta = $res["meta"];

        global $wpdb;
        
        foreach ($data as $r) {
            

            $args = [];
            foreach ($product_args as $a) {
                if (!empty($product_opt[$a])) {
                    
                    $args[$a] = isset($r[$product_opt[$a]]) ? $r[$product_opt[$a]] : '';
                   
                }
            }

            $_pro_id = $r["salsify:id"];
            $sql = "SELECT p.ID as id 
                From {$wpdb->posts} as p
                LEFT JOIN {$wpdb->postmeta} as m
                ON p.ID = m.post_id
                WHERE m.meta_key = '_product_id'
                AND m.meta_value = '$_pro_id'
                AND p.post_type = 'product' OR p.post_type = 'product_variation'";

            $res = $wpdb->get_results($sql);

            if (count($res) > 0) {

            } else {
               
                $args["type"] = "";
                // $args["category_ids"] = $this->get_category($args["categories"], "product_cat");
                $args["gallery_ids"] = $this->add_attachment($r["salsify:digital_assets"]);
                $args["image_id"] = $args["gallery_ids"][0];
                
                // if ($args["category_ids"]) {
                //     $args["category_ids"] = array($args["category_ids"]);
                // } else {
                //     $args["category_ids"] = [];
                // }

                $args["category_ids"] = [];

                $product_id = $this->create_product($args);
                
                if ($product_id) {
                    update_post_meta($product_id, "_product_id", $r["salsify:id"]);
                    //
                    // foreach ($this->)
                    
                    // $this->add_attachment($data["salsify:digital_assets"], $product_id);
                }
            }
        }

        echo json_encode($meta);
        exit;
    }

    public function add_attachment($imgs) {
        
        $types = ["jpg", "png", "jpeg", "bmp", "gif"];
        $attach_ids = [];

        if (is_array($imgs)) {
            foreach ($imgs as $img) {

            if (!isset($img["salsify:url"])) {
                continue;
            }

            $url = $img["salsify:url"];
            $name = $img["salsify:name"];
            $format = $img["salsify:format"];
            $iid = $img["salsify:id"] . $name;
            
            if (in_array($format, $types)) {
                $id = $this->get_product_image($url, $name, $iid);

                if ($id) {
                    $attach_ids[] = $id;
                }
            } else {
                continue;
            }
            }    
        }        

        return $attach_ids;
    }

    public function create_product( $args, $product = null ) {

        if (!$product && !( $product = $this->wc_get_product_object_type( $args['type'] ) )) {            
            return false; 
        }
        
        // Product name (Title) and slug
        $product->set_name( $args['name'] );
        if( isset( $args['slug'] ) )
            $product->set_name( $args['slug'] );
    
        // Description and short description:
        $product->set_description( $args['description'] );
        //$product->set_short_description( $args['short_description'] );
    
        // Status ('publish', 'pending', 'draft' or 'trash')
        $product->set_status( 'publish' );
    
        // Visibility ('hidden', 'visible', 'search' or 'catalog')
        $product->set_catalog_visibility( isset($args['visibility']) ? $args['visibility'] : 'visible' );
    
        // Featured (boolean)
        $product->set_featured(  isset($args['featured']) ? $args['featured'] : false );
        
        // Virtual (boolean)
        $product->set_virtual( isset($args['virtual']) ? $args['virtual'] : false );
        
        // Prices
        $product->set_regular_price( $args['regular_price'] );
        $product->set_sale_price( isset( $args['sale_price'] ) ? $args['sale_price'] : '' );
        $product->set_price( isset( $args['sale_price'] ) ? $args['sale_price'] :  $args['regular_price'] );
        
        // Taxes
        if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' ) {
            $product->set_tax_status(  isset($args['tax_status']) ? $args['tax_status'] : 'taxable' );
            $product->set_tax_class(  isset($args['tax_class']) ? $args['tax_class'] : '' );
        }
    
       
        $product->set_sku( isset( $args['sku'] ) ? $args['sku'] : '' );
        $product->set_manage_stock( isset( $args['manage_stock'] ) ? $args['manage_stock'] : false );
        $product->set_stock_status( isset( $args['in_stock'] ) ? $args['in_stock'] : 'instock' );
        if( isset( $args['manage_stock'] ) && $args['manage_stock'] ) {
            $product->set_stock_quantity( $args['stock_quantity'] );
            $product->set_backorders('yes');
            // $product->set_backorders( isset( $args['backorders'] ) ? $args['backorders'] : 'no' ); // 'yes', 'no' or 'notify'
        }
        
        // Sold Individually
        $product->set_sold_individually( isset( $args['sold_individually'] ) ? $args['sold_individually'] : false );
    
        // Weight, dimensions and shipping class
        $product->set_weight( isset( $args['weight'] ) ? $args['weight'] : '' );
        $product->set_length( isset( $args['length'] ) ? $args['length'] : '' );
        $product->set_width( isset(  $args['width'] ) ?  $args['width']  : '' );
        $product->set_height( isset( $args['height'] ) ? $args['height'] : '' );

        if( isset( $args['shipping_class_id'] ) )
            $product->set_shipping_class_id( $args['shipping_class_id'] );
    
        // // Upsell and Cross sell (IDs)
        // $product->set_upsell_ids( isset( $args['upsells'] ) ? $args['upsells'] : '' );
        // $product->set_cross_sell_ids( isset( $args['cross_sells'] ) ? $args['upsells'] : '' );
    
        // // Attributes et default attributes
        // if( isset( $args['attributes'] ) )
        //     $product->set_attributes( wc_prepare_product_attributes($args['attributes']) );
        // if( isset( $args['default_attributes'] ) )
        //     $product->set_default_attributes( $args['default_attributes'] ); // Needs a special formatting
    
        // Reviews, purchase note and menu order
        $product->set_reviews_allowed( isset( $args['reviews'] ) ? $args['reviews'] : false );
        $product->set_purchase_note( isset( $args['note'] ) ? $args['note'] : '' );
        
        // Product categories and Tags
        if( isset( $args['category_ids'] ) )
            $product->set_category_ids( $args['category_ids'] );
        
        // if( isset( $args['tag_ids'] ) )
        //     $product->set_tag_ids( 'tag_ids' );
         
        // Images and Gallery
        $product->set_image_id( isset( $args['image_id'] ) ? $args['image_id'] : "" );
        $product->set_gallery_image_ids( isset( $args['gallery_ids'] ) ? $args['gallery_ids'] : array() );
    
        ## --- SAVE PRODUCT --- ##
        $product_id = $product->save();
    
        return $product_id;
    }
    
    
    public function wc_get_product_object_type( $type ) {
       
        $product = null;
          

        $product = new WC_Product_Simple();
        
        if( ! is_a( $product, 'WC_Product' ) )
            return false;
        else
            return $product;
    }

    public function get_category($name, $term_tax) {

        if (wp_cache_get($name . $term_tax)) {
            return wp_cache_get($name . $term_tax);
        }

        // // global $wpdb;
        // // $terms = $wpdb->prefix ."terms";
        // // $tax = $wpdb->prefix . "term_taxonomy";

        // // $sql = "SELECT A.term_id as term_id FROM $terms as A LEFT JOIN $tax as B on A.`term_id`= B.term_id WHERE B.taxonomy='$term_tax' and A.name='$name'";
        // // $result = $wpdb->get_results($sql);

        // if (count($result) > 0) {
        //     wp_cache_set($name . $term_tax, $result[0]->term_id);
        //     return $result[0]->term_id;
        // }

        $term = get_term_by('name', $name, $term_tax);

        if ($term) {
            wp_cache_set($name . $term_tax, $term->term_id);
            return $term->term_id;
        }

        $cid = wp_insert_term(
            $name,
            $term_tax
        );

        if (!is_wp_error($cid)) {
            $cat_id = isset( $cid['term_id'] ) ? $cid['term_id'] : 0;
            wp_cache_set($name . $term_tax, $cat_id);          
            return $cat_id;
        }

        return 0;
    }
    
    public function wp_get_attachment_by_post_name( $post_name ) {
        $args           = array(
            'posts_per_page' => 1,
            'post_type'      => 'attachment',
            'name'           => trim( $post_name ),
        );

        $get_attachment = new WP_Query( $args );

        if ( ! $get_attachment || ! isset( $get_attachment->posts, $get_attachment->posts[0] ) ) {
            return false;
        }

        return $get_attachment->posts[0];
    }

    public function get_product_image($url, $name, $filename) {

        $attachment = $this->wp_get_attachment_by_post_name( $filename );
        if ( $attachment ) {
            return $attachment->ID;
        }

        $image_url        = $url; // Define the image URL here
        $image_name       = $name;
        $upload_dir       = wp_upload_dir(); // Set upload folder
        $image_data       = file_get_contents($image_url); // Get image data
        $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
        // $filename         = basename( $unique_file_name ); // Create image file name

        // Check folder permission and define file location
        if( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        // Create the image  file on the server
        file_put_contents( $file, $image_data );

        // Check image file type
        $wp_filetype = wp_check_filetype( $filename, null );

        // Set attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Create the attachment
        $attach_id = wp_insert_attachment( $attachment, $file );
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );


        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;
        // Include image.php
        // require_once(ABSPATH . 'wp-admin/includes/image.php');

        // $attach_data = wp_generate_attachment_metadata( $attach_id, $file );


        // wp_update_attachment_metadata( $attach_id, $attach_data );

        // set_post_thumbnail( $post_id, $attach_id );
    }

    public function install() {
        update_option( SALSIFY_OPT_TIME, time());
    }
}

$plugin = Salsify_Plugin::instance();

register_activation_hook( __FILE__, array( $plugin, 'install' ) );

add_action( 'woocommerce_salsify_event', 'woocommerce_salsify_event');

function woocommerce_salsify_event() {
    // include("test.php");
    // $file = fopen("D:/WorkSpace/2020/wordpress/wp-content/plugins/sod-salsify/t.txt", "a");
    // fwrite($file, time() . "\n");
    // fclose($file);
    require_once("script.php");

}