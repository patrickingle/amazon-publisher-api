<?php

	$catname = '';
	
	if (isset($_POST['saveapikey'])) {
		update_option('awsapikey',$_POST['awsapikey']);
		update_option('awsapisecretkey',$_POST['awsapisecretkey']);
		update_option('awspa_markup',$_POST['markup']);
		update_option('awspa_associatetag',$_POST['awsassociatetag']);
	} elseif(isset($_POST['addprod'])) {
		$ecommerce = get_option('awspa_ecommerce');
		$products_categories = 'products_categories';
		if ($ecommerce == 'woocommerce') {
			$products_categories = 'product_cat';
		}
		
		$arryprods = $_POST['product'];
		$products = array();
		//echo '<pre>';
		foreach($arryprods as $prod) {
			//echo urldecode($prod);
			$product = json_decode(urldecode($prod));
			//$product[] = json_decode(urldecode($prod));
			$post = array(
				'ID' => 0,
				'title' => $product->title,
				'post_type' => 'product',
				'post_status' => 'publish',
				'post_category' => array(get_term_by('slug',$_POST['catname'],$products_categories)->term_id),
			);
			global $user_ID;
			$new_post = array(
			    'post_title' => $product->title,
			    'post_content' => $product->title,
			    'post_status' => 'publish',
			    'post_date' => date('Y-m-d H:i:s'),
			    'post_author' => $user_ID,
			    'post_type' => 'product',
			    'post_category' => array(0)
			);
			//print_r($product);
			//print_r($new_post);

			if ($ecommerce == 'ready-ecommerce') {
				wp_insert_term($product->manufacturer,'products_brands');
			}
			
			$postID = wp_insert_post($new_post);			
			//$postID = wp_insert_post($post);
			$category_ids = array($_POST['catname']);
			wp_set_object_terms( $postID, $category_ids, $products_categories);
			if ($ecommerce == 'ready-ecommerce') {
				wp_set_object_terms( $postID, array($product->manufacturer), 'products_brands');
			}
			
			add_post_meta($postID, 'amazon-detail-page-url', $product->detailPageURL, true);
			
			$upload_dir = wp_upload_dir();
			$image_data = file_get_contents($product->imageURL);
			$filename = basename($product->imageURL);
			if(wp_mkdir_p($upload_dir['path']))
			    $file = $upload_dir['path'] . '/' . $filename;
			else
			    $file = $upload_dir['basedir'] . '/' . $filename;
			file_put_contents($file, $image_data);

			$wp_filetype = wp_check_filetype($filename, null );
			$attachment = array(
			    'post_mime_type' => $wp_filetype['type'],
			    'post_title' => sanitize_file_name($filename),
			    'post_content' => '',
			    'post_status' => 'inherit'
			);
			$attach_id = wp_insert_attachment( $attachment, $file, $postID );
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			set_post_thumbnail( $postID, $attach_id );
			
			
			if ($ecommerce == 'ready-ecommerce') {
				$sql = "UPDATE ".$wpdb->prefix."toe_products SET price='$product->price',sku='$product->asin',quantity='$product->available' WHERE post_id='$postID'";
				//echo $sql;
				
				global $wpdb;
				$wpdb->query($sql);
			} elseif ($ecommerce == 'woocommerce') {
				// update postmeta meta_key=_sku,_price,
				add_post_meta($postID, '_sku', $product->asin);
				add_post_meta($postID, '_price', $product->price);
				add_post_meta($postID, '_stock', $product->available);
			}
		}
		//echo '</pre>';
	} elseif(isset($_POST['getamazon'])) {
		require dirname(__FILE__).'/../AmazonECS_133/Exeu-Amazon-ECS-PHP-Library-1867eaa/lib/AmazonECS.class.php';

		try {
			$awsapikey = get_option('awsapikey');
			$awsapisecretkey = get_option('awsapisecretkey');
			$awsassociatetag = get_option('awspa_associatetag');
			$markup = get_option('awspa_markup');
			$category = $_POST['category'];
			$catname = $category;
			$amazonEcs = new AmazonECS($awsapikey, $awsapisecretkey, 'com', $awsassociatetag);
		    $response = $amazonEcs->category('All')->responseGroup('ItemAttributes,Offers,Images,Reviews')->search($category);
			$products = array();
			$i=0;
			//echo '<pre>'; print_r($response); echo '</pre>';
			foreach($response->Items as $item) {
				if (is_array($item)) {
					foreach($item as $product) {
						$prod = $product->ItemAttributes->Title;
						$prodcode = $product->ASIN;
						$listprice = number_format(floatval(preg_replace('/[\$,]/', '', $product->ItemAttributes->ListPrice->FormattedPrice) * $markup),2);
						//echo 'List price = '.$listprice.'<br>';
						$avail = $product->Offers->TotalOffers; //ItemAttributes->NumberOfItems;
						$brand = $product->ItemAttributes->Brand;
						$manufacturer = $product->ItemAttributes->Manufacturer;
						$sku = $product->ItemAttributes->SKU;
						$upc = $product->ItemAttributes->UPC;
						$reviews = $product->ItemAttributes->CustomerReviews->IFrameURL;
						$detailPageURL = $product->DetailPageURL;
						$imageURL = $product->LargeImage->URL;
						$image_y = $product->LargeImage->Height->_;
						$image_x = $product->LargeImage->Width->_;
						//echo $image_x.', '.$image_y.'<br>';
						$imageFName = basename($imageURL);
						$saveFileName = $xcart_dir.'/images/T/'.$imageFName;
						
						$products[$i]['available'] = $avail;
						$products[$i]['title'] = $prod;
						$products[$i]['asin'] = $prodcode;
						$products[$i]['price'] = $listprice;
						$products[$i]['manufacturer'] = $manufacturer;
						$products[$i]['sku'] = $sku;
						$products[$i]['upc'] = $upc;
						$products[$i]['imageURL'] = $imageURL;
						$products[$i]['detailPageURL'] = $detailPageURL;
						
						$i++;
					}
				}
			}

		} catch(Exception $ex) {
			
		}
	}
	
	$awsapikey = get_option('awsapikey');
	$awsapisecretkey = get_option('awsapisecretkey');
	$awsassociatetag = get_option('awspa_associatetag');
	$markup = get_option('awspa_markup');
	
	$ecommerce = get_option('awspa_ecommerce');
	
	global $wpdb;
	
	if ($ecommerce == 'ready-ecommerce') {
		$sql = "SELECT mt.term_id,mt.name,mt.slug FROM `".$wpdb->prefix."terms` mt INNER JOIN ".$wpdb->prefix."term_taxonomy mx ON mt.term_id=mx.term_id WHERE mx.taxonomy='products_categories' ORDER BY mt.name ASC";
	} elseif ($ecommerce == 'woocommerce') {
		$sql = "SELECT mt.term_id,mt.name,mt.slug FROM `".$wpdb->prefix."terms` mt INNER JOIN ".$wpdb->prefix."term_taxonomy mx ON mt.term_id=mx.term_id WHERE mx.taxonomy='product_cat' ORDER BY mt.name ASC";
	} else {
		$sql = "SELECT mt.term_id,mt.name,mt.slug FROM `".$wpdb->prefix."terms` mt";
	}
	$categories = $wpdb->get_results($sql);
	//$args = array(
  	//	'orderby' => 'name',
  	//	'parent' => 0
  	//);
	//$args = array( 'taxonomy' => 'products_categories' );
	//$categories = get_terms();
	//$taxonomies = get_taxonomies();

?>
<h2>Amazon Publisher API</h2>
<form method="post">
	<table>
		<tr>
			<td>Amazon API Access Key</td>
			<td><input type="text" name="awsapikey" id="awsapikey" value="<?php echo $awsapikey; ?>"></td>
		</tr>
		<tr>
			<td>Amazon API Secret Key</td>
			<td><input type="text" name="awsapisecretkey" id="awsapisecretkey" value="<?php echo $awsapisecretkey; ?>"></td>
		</tr>
		<tr>
			<td>Amazon Associate Tag</td>
			<td><input type="text" name="awsassociatetag" id="awsassoictaetag" value="<?php echo $awsassociatetag; ?>"></td>
		</tr>
		<tr>
			<td>Mark-UP</td>
			<td><input type="text" name="markup" id="markup" value="<?php echo $markup; ?>"></td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" name="saveapikey" id="saveapikey" value="Save"></td>
		</tr>
		<tr>
			<td>Products Categories</td>
			<td>
				<select name="category" id="category">
					<?php
						foreach($categories as $category) {
							echo '<option value="'.$category->slug.'">'.$category->name.'</option>';
						}
					?>
				</select>
				<input type="submit" name="getamazon" value="Get Amazon Products">
			</td>
		</tr>
	</table>
<br>
<input type="hidden" name="catname" value="<?php echo $catname;?>">
<table border="2">
<?php 
//print_r($categories); 
//print_r($products);
if (is_array($products) && count($products) > 0) {
	echo '<th>'.$catname.'</th>';
	foreach($products as $product) {
		echo '<tr>';
		echo '<td><input type="checkbox" name="product[]" value="'.urlencode(json_encode($product)).'"><a href="'.$product['detailPageURL'].'" target="_blank" title="click to open original product page in a new window"><img src="'.$product['imageURL'].'" width="50" height="50"></a>'.$product['title'].'('.$product['available'].'/'.$product['price'].'/'.$product['manufacturer'].')</td>';
		echo '</tr>';
	}	
	echo '<tr><td><input type="submit" name="addprod" value="Add Selected"></td></tr>';
}
?>
</table>
</form>