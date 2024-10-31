<?php


class PokyImport
{
	public function insert_product( $product_data, $new_item = true ){
		$product_id = wc_get_product_id_by_sku( $product_data['sku'] );
		if( $product_id && $new_item ){
			return false;
		}elseif( $new_item ){
			if( $product_data['type'] == 'variable' ){
				$product = new WC_Product_Variable();
			}else{
				$product = new WC_Product();
			}
		}else{
			$product = wc_get_product( $product_id );
		}

		$product->set_name( $product_data['title'] );
		$product->set_status( $product_data['published'] );
		$product->set_catalog_visibility( 'visible' );
		$product->set_description( $product_data['description'] );

		$product->set_backorders('no');
        $product->set_stock_status($product_data['instock']);
        $product->set_reviews_allowed(true);
        $product->set_sold_individually(false);

        if( !empty( $product_data['image'] ) ) {
        	$image = $this->upload_media( $product_data['image'] );

        	$imgIds=array();

        	for ($i=1; $i<count($product_data['images']); $i++) {
                $imgIds[]=$this->upload_media($product_data['images'][$i]);
            }

            $product->set_image_id( $image );
            $product->set_gallery_image_ids( $imgIds );
        }

		if($product_data['type'] == 'simple') {
            //$product->set_sku($product_data['sku']);

            if (!empty($product_data['regular_price'])) {
                $product->set_regular_price($product_data['regular_price']);
            } else {
                $product->set_regular_price($product_data['price']);
            }

            if (!empty($product_data['regular_price']) && !empty($product_data['price'])) {
                $product->set_sale_price($product_data['price']);
            }

            $product->set_manage_stock(false);
            $product->set_stock_quantity($product_data['qty']);

            $product_id = $product->save();
        }

		if( $product_data['type'] == 'variable' ) {
			$att_array = array();

			//Save Attributes
			foreach( $product_data['attribute_name'] as $product_attributes ){
				$option_name	= key( $product_attributes );
				$slug			= sanitize_title($option_name);
				if(!get_taxonomy("pa_" . $slug)) {
					$result = wc_create_attribute(array(
						"name" => $option_name,
						"slug" => $slug,
						"type" => "select",
					));
				}

				foreach( $product_attributes[$option_name] as $value ){
					if( !term_exists( $value, wc_attribute_taxonomy_name($option_name) ) ){
						wp_insert_term( $value, wc_attribute_taxonomy_name( $option_name ), array( 'slug' => sanitize_title( $value ) ) );
					}
				}

				$attribute = new WC_Product_Attribute();
				$attribute->set_id( 0 );
				$attribute->set_name( $option_name );
				$attribute->set_options( $product_attributes[$option_name] );
				$attribute->set_position( 0 );
				$attribute->set_visible( true );
				$attribute->set_variation( true );
				$att_array[] = $attribute;
			}
			$product->set_attributes( $att_array );
			$product_id = $product->save();

			foreach( $product_data['attributes'] as $product_variant ){
				$variation_id = wc_get_product_id_by_sku( $product_variant['sku'] );
				if( $variation_id && $new_item ){
					continue;
				}

				if( $new_item ) {
					$variation_post = array(
						'post_title' => $product->get_title(),
						'post_name' => 'product-' . $product_id . '-variation',
						'post_status' => 'publish',
						'post_parent' => $product_id,
						'post_type' => 'product_variation',
						'guid' => $product->get_permalink()
					);

					$variation_id = wp_insert_post( $variation_post );
				}

				$variation = new WC_Product_Variation( $variation_id );
				$variation->set_regular_price((empty($product_variant['regular_price']))? $product_variant['price']:$product_variant['regular_price']);
				$variation->set_sku($product_variant['sku']);
				$variation->set_sale_price($product_variant['price']);
				$variation->set_stock_quantity($product_variant['qty']);
				$variation->set_manage_stock(false);
				$variation->set_parent_id($product_id);

				if( !empty( $product_variant['image'] ) ) {
					$image = $this->upload_media( $product_variant['image'] );
					$variation->set_image_id( $image );
				}

				$var_attrs = array();
				foreach( $product_variant['attributes'] as $variant_attr ){
					$slug				= sanitize_title($variant_attr['name']);
					$var_attrs[$slug]	= $variant_attr['options'];
					$term_slug = get_term_by('name', $variant_attr['options'], $variant_attr['name'])->slug;

					$post_term_names = (array) wp_get_post_terms($product_id, "pa_" . $slug, array('fields' => 'names'));
					if (!in_array($variant_attr['options'], $post_term_names))
						wp_set_post_terms($product_id, $variant_attr['options'], $variant_attr['name'], true);

					update_post_meta($variation_id, 'attribute_' . $slug, $term_slug);
				}

				$variation->set_attributes($var_attrs);
				$variation_id = $variation->save();
			}
		}

		return $product_id;
	}

	private function upload_media($image_url){
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		$media = media_sideload_image( $image_url, 0, null, 'id' );
		return $media;
	}
}

// N E X T - A F L A