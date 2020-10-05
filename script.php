<?php

    // require("./../../../wp-load.php");
    //dirname(__FILE__) . DIRECTORY_SEPARATOR .

    $options = Salsify_Plugin::get_options();

    update_option( SALSIFY_OPT_TIME, time());
    
    $salsify = isset($options["filter"]) ? $options["filter"] : [];
    $account = isset($options["account"]) ? $options["account"] : [];

    if (count($account) == 0) {
        exit;
    }

    global $wpdb;

    $sql = "SELECT p.ID as pid, m.meta_value as sid 
                From {$wpdb->posts} as p
                LEFT JOIN {$wpdb->postmeta} as m
                ON p.ID = m.post_id
                WHERE m.meta_key = '_product_id' 
                AND p.post_type = 'product' ";
    $result = $wpdb->get_results($sql);

    $product_list = [];
    foreach ($result as $res) {
        $product_list[$res->sid] = $res->pid;
    }


    $version  = get_option('salsify-rivision', 0);

    foreach ($salsify as $key => $sal) {

        if (!isset($account[$key]["activate"])) {
            continue;
        }

        $offset = 1;    
        for ($i = 0; $i < 100; $i++) {

            if (sal_sod_update_data($key, $offset + $i, $product_list) === 2) {
                break;
            }
        }
    }

    update_option( SALSIFY_OPT_REVISION, $version + 1);

    
    // sal_sod_update_data(0);

function sal_sod_update_data($selected, $offset, &$product_list ) {
    $total = 0;

    try {
       
        $version  = get_option( SALSIFY_OPT_REVISION, 0);
        $version += 1;

        $instance =  Salsify_Plugin::instance();
        $options = Salsify_Plugin::get_options();
        $product_opt = isset($options["product"][$selected]) ? $options["product"][$selected] : [];
        $product_size = 100;
    
        $res = $instance->feed->get_product(array('page' => $offset, 'per_page' => $product_size), $selected);

        $data = $res["data"];
        

        if (!$data) {
            return false;
        }

        $meta = $res["meta"];
        $total = intval($meta["total_entries"]);

        $product_args = SOD_Product::product_fields();
        $custom_fields = SOD_Product::custom_fields();
        $tax_fields = SOD_Product::taxonomy();
        
        foreach ($data as $r) {

            $args = [];
            foreach ($product_args as $key => $a) {
                if (!empty($product_opt[$a])) {
                    

                    $args[$a] ='';
                    $args_item_data = [];
                    foreach ($product_opt[$a] as $ap) {
                        $args_item_data[] = isset($r[$ap]) ? $r[$ap] : "";
                    }

                    $args[$a] = count($args_item_data) > 1 ? $args_item_data : $args_item_data[0];                
                }
            }

            $_pro_id = $r["salsify:id"];
            
            $product_id = isset($product_list[$_pro_id]) ? $product_list[$_pro_id] : 0;

            if ($args['name'] == "") {
                continue;
            }
            
            if ( $product_id  > 0) {

                // $product_id = $res[0]->id;

                // $hashcode = md5(json_encode($r));
                // $oldhash = get_post_meta ($product_id, "_product_update_code", true);

                // if ($oldhash != $hashcode)
                // {
                $args["type"] = "";
                // $args["category_ids"] = $instance->get_category($args["categories"]);

                $args["gallery_ids"] = $instance->add_attachment($r["salsify:digital_assets"]);
                $args["image_id"] = $args["gallery_ids"][0];
                $args["manage_stock"] = true;
                $args["in_stock"] = "in_stock";

                // if ($args["category_ids"]) {
                //     $args["category_ids"] = array($args["category_ids"]);
                // }

                // /$args["name"] = "t12345";
                $product = wc_get_product( $product_id );
                $instance->create_product($args, $product);

                update_post_meta( $product_id, "_product_update_code", $hashcode);                                
                update_custom_fields( $product_id, $custom_fields, $product_opt, $r);                 
                update_custom_taxonomy($instance, $product_id, $tax_fields, $product_opt, $r);
                // }
                
                // update_post_meta($product_id, "_product_update_code", $hashcode);
            } else {
                
                $args["type"] = "";
                // $args["category_ids"] = $instance->get_category($args["categories"]);
                $args["gallery_ids"] = $instance->add_attachment($r["salsify:digital_assets"]);
                $args["image_id"] = $args["gallery_ids"][0];
                $args["manage_stock"] = true;
                $args["in_stock"] = "in_stock";

                // if ($args["category_ids"]) {
                //     $args["category_ids"] = array($args["category_ids"]);
                // }
                
                $product_id = $instance->create_product($args);
                
                if ($product_id) {
                    update_post_meta($product_id, "_product_id", $r["salsify:id"]);
                    update_custom_fields($product_id, $custom_fields, $product_opt, $r);
                    update_custom_taxonomy($instance, $product_id, $tax_fields, $product_opt, $r);
                    // foreach ($custom_fields as $key => $a) {
                    //     if (!empty($product_opt[$a])) {
                    //         $args_item_data = [];

                    //         foreach ($product_opt[$a] as $ap) {
                    //             $args_item_data[] = isset($r[$ap]) ? $r[$ap] : "";
                    //         }

                    //         $value = count($args_item_data) > 1 ? $args_item_data : $args_item_data[0];

                    //         if ($value) {
                    //             update_post_meta($product_id, $key, $value);
                    //         }
                    //     }
                    // }
                    // $this->add_attachment($data["salsify:digital_assets"], $product_id);

                    // $hashcode = md5(json_encode($r));
                    // update_post_meta($product_id, "_product_update_code", $hashcode);
                }
            }


            update_post_meta($product_id, "_product_revision", $version);
            
            update_post_meta($product_id, "_product_feed_account", $selected);
        }

        

        
    }
    catch (Exception $e) {
        echo $e->getMessage();
    }

    if ($total < $offset * $per_page) {
        return 2;
    }

    return true;
}
    
function update_custom_fields($product_id, $custom_fields, $product_opt, $r) {

    foreach ($custom_fields as $key => $a) {

        if (!empty($product_opt[$key])) {
            $args_item_data = [];

            foreach ($product_opt[$key] as $ap) {
                $args_item_data[] = isset($r[$ap]) ? $r[$ap] : "";
            }

            $value = count($args_item_data) > 1 ? $args_item_data : $args_item_data[0];
            
            if ($value) {
                
                $field = get_field_object($key);
                acf_update_value($value, $product_id, $field);

            }
        }
    }
}

function update_custom_taxonomy($instance, $product_id, $tax_fields, $product_opt, $r) {
    // echo $product_id;
   
    foreach ($tax_fields as $key => $tax) {

        $args = [];
        
        if (isset($product_opt[$key])) {
            foreach ($product_opt[$key] as $po) {
                $kk = isset($r[$po]) ? $r[$po] : "";

                if ($kk) {
                    $args[] = $instance->get_category($kk, $key);        
                }
                
            } 
        }
        

        if (!empty($args)) {
            wp_set_post_terms($product_id, $args, $key);
        }
    }
    
}
    
?>