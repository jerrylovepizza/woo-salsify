<?php

class SOD_Product {
    public static function product_fields() {
        $data = array(
			'name',			
			'status',
			'featured',
			'description',
			'short_description',
			'sku',
			'price',
			'regular_price',
			'sale_price',					
			// 'purchasable',
			// 'total_sales',			
			// 'tax_status',
			// 'tax_class',
			// 'manage_stock',
			'stock_quantity',
			// 'in_stock',
			// 'backorders',
			// 'backorders_allowed',
			'backordered',
			'sold_individually',
			'weight',
			'length',				
			'width',
			'height',
			// 'shipping_required',
			// 'shipping_taxable', 
			// 'shipping_class',
			// 'shipping_class_id',
			// 'parent_id',
			// 'tags',
			// 'images',
        );
        
        return $data;
	}
	
	public static function custom_fields() {

		$custom_fields = [];
		if (function_exists('acf_get_field_groups')) {
			$field_groups = acf_get_field_groups(array(
				// 'post_id'	=> 0, 
				'post_type'	=> 'product'
			));

			if( $field_groups ) {
				foreach( $field_groups as $i => $field_group ) {
					
					// // vars
					// $item = array(
					// 	'id'		=> 'acf-' . $field_group['key'],
					// 	'key'		=> $field_group['key'],
					// 	'title'		=> $field_group['title'],
					// 	'position'	=> $field_group['position'],
					// 	'style'		=> $field_group['style'],
					// 	'label'		=> $field_group['label_placement'],
					// 	'edit'		=> acf_get_field_group_edit_link( $field_group['ID'] ),
					// 	'html'		=> ''
					// );
					
					// // append html if doesnt already exist on page
					// if( !in_array($field_group['key']) ) {
						
						// load fields
						$fields = acf_get_fields( $field_group );
						foreach ($fields as $field) {
							$custom_fields[$field["key"]] = $field["label"];
						}
						
						// // get field HTML
						// ob_start();
						
						// // render
						// acf_render_fields( $fields, $args['post_id'], 'div', $field_group['instruction_placement'] );
						
						// $item['html'] = ob_get_clean();
					// }
					
					// append
					// $response['results'][] = $item;
				}
				
				// Get style from first field group.
				// $response['style'] = acf_get_field_group_style( $field_groups[0] );
			}


			// var_dump ($field_groups);
		} else {
			return [];
		}

		return $custom_fields;
	}

	public static function taxonomy() {

		

		$data = array();
		$data["product_cat"] = "Categories";
		$data["product_tag"] = "Tags";
		if (!defined("WPCF_OPTION_NAME_CUSTOM_TAXONOMIES")) {
			return $data;
		}
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
}