<?php 
  
	/*
	
	treba dohvatiti proizvode, napraviti csv i onda importati csv  u magento 

	*/


	$data = array("user" => "email@email.com", "password" => "pass");
	$post = json_encode($data);

	// getLogin 
	// First we need to login, and request token to access all methods

	$url = "http://drop.novaengel.com/api/login"; 

	$opts = array('http'=>
		array(
			'method' => 'POST',
			'header' => 'Content-type: application/json',
			'content' => $post
		)
	);  

	$context = stream_context_create($opts);
	$result = file_get_contents($url, false, $context); 

 	// Decodes JSON into a PHP token array
	$token_array = json_decode($result, true);

	$token = $token_array["Token"]; 

	// getProducts: 
	$api_url = 'http://drop.novaengel.com/api/products/availables/';
	$lang = '/en';

	$getProducts = $api_url . $token . $lang;
  	
  	$request_options = array('http'=>
		array(
			'method' => 'GET',
			'header' => 'Content-type: application/json'
		)
	);

	$context = stream_context_create($request_options);
	$products = file_get_contents($getProducts, false, $context); 

	// Decodes JSON into a PHP array
	$products_array = json_decode($products, true);  

	if (!file_exists('downloaded')) {
	    mkdir('downloaded', 0777, true);
	}

	//print all data about product:
	//print_r($products_array['0']);
	//print all families:
	//print_r($products_array['0']['Families']);
	//print only the first family: (should echo Perfume)
	//print_r($products_array['0']['Families']['0']);
 
	//Prepare CSV:
 	$fp = fopen('./csv/data.csv', 'w') or die("Unable to open file!");
 
 	//loop all products
	foreach ($products_array as $key1 => $value1) {
		//loop all families to find products with Family "Perfume"
		foreach ($products_array[$key1]['Families'] as $AAA => $value) { 
			//if product has family "Perfume"...  (3933 total products with that family)
			if ($value == 'Perfume') {
				//print_r($products_array[$key1]['Id']);
				$productId = $products_array[$key1]['Id'];
				$productDescription = $products_array[$key1]['Description'];
				$productSetContent = $products_array[$key1]['SetContent'];
				$productPrice = $products_array[$key1]['Price'];
				$productPVR = $products_array[$key1]['PVR'];
				$productStock = $products_array[$key1]['Stock'];
				$productBrandId = $products_array[$key1]['BrandId'];
				$productBrandName = $products_array[$key1]['BrandName'];
 				$productGender = $products_array[$key1]['Gender']; 
 
 				$row = array();
 				array_push($row, $productId, $productDescription, $productPrice, $productStock);
     			fputcsv($fp, $row);

 
/*				$product_image = 'http://drop.novaengel.com/api/products/image/' . $token . '/' . $productId;
			 	$options = array('http' => 
			 		array(
			 			'method' => 'GET',
			 			'Content-type: application/json'
			 		)
			 	);
				$context = stream_context_create($options);
				$urlImage = file_get_contents($product_image, false, $context); 

				$urlImage = substr_replace($urlImage, "", -1);
				$urlImage = substr($urlImage, 1);

				$fp = fopen('./downloaded/' . $productId . '.jpg',"w");
				$c = curl_init($urlImage);
				curl_setopt($c, CURLOPT_FILE, $fp);
				curl_setopt($c, CURLOPT_HEADER, 0);
				curl_setopt($c, CURLOPT_POST, false);
				curl_setopt($c, CURLOPT_BINARYTRANSFER, true);
				curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
				$rawdata = curl_exec($c);
				fwrite($fp, $rawdata);
				fclose($fp);*/
 
			}
		} 
	}
 
  
 
	 /*
	// function defination to convert array to xml
	function array_to_xml( $data, &$xml_data ) {
	    foreach( $data as $key => $value ) {
	        if( is_numeric($key) ){
	            $key = 'product_'.$key; //dealing with <0/>..<n/> issues
	        }
	        if( is_array($value) ) {
	            $subnode = $xml_data->addChild($key);
	            array_to_xml($value, $subnode);
	        } else {
	            $xml_data->addChild("$key",htmlspecialchars("$value"));
	        }

	     }
	}

	// initializing or creating array
	$data = array($products_array);

	// creating object of SimpleXMLElement
	$xml_data = new SimpleXMLElement('<?xml version="1.0"?><products></products>');

	// function call to convert array to xml
	array_to_xml($data,$xml_data);

	//create dir for xml if not exists

	if (!file_exists('xml')) {
	    mkdir('xml', 0755, true);
	}

	//saving generated xml file; 
	$result = $xml_data->asXML("xml/FranelaArtikli-" . date('d-m-Y') . "-" . date('H-i-s') . ".xml");

	echo "xml created successful";

	*/
 
 	/* BARE MINIMUM COLUMS: 

 	sku, name, price, qty, categories, url_key, type, grouped_skus, cs_skus, us_skus, short_description, description, image, small_image, thumbnail

 	*/
 
	set_time_limit(0);
	ini_set('memory_limit', '1024M');
	include_once "app/Mage.php";
	include_once "downloader/Maged/Controller.php";

	Mage::init();

	$app = Mage::app('default');


	//The category names should be exactly the same name from the csv file where the id is the corresponding category id in magento. This is done when the csv file doesn't contain ids for categories but the name of categories.
	$categories = array(
	    'Category 1' => 3,
	    'Category 2' => 4,
	    'Category 3' =>5,
	    'Category 4'=>6,	    
	);
	$row = 0;

	if (($handle = fopen("csv/data.csv", "r")) !== FALSE) {
	    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	        echo 'Importing product: '.$data[0].'<br />';
	        foreach($data as $d)
	        {
	           // echo $d.'<br />';
	        }
	        $num = count($data);
	        //echo "<p> $num fields in line $row: <br /></p>\n";
	        $row++;
	    
	        if($row == 1) continue;
	        
	        $product = Mage::getModel('catalog/product');
	 
	        $product->setSku($data[0]);
	        $product->setName($data[0]);
	        $product->setDescription($data[1]);
	        $product->setShortDescription('');
	       // $product->setManufacturer($data[0]);
	        $product->setPrice($data[2]);
	        $product->setTypeId('simple');
	        /*
	        $fullpath = 'media/catalog/product/thumb/';
	        $ch = curl_init ($data[0]);
	        curl_setopt($ch, CURLOPT_HEADER, 0);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	        $rawdata=curl_exec($ch);
	        curl_close ($ch);
	        $fullpath = $fullpath.$data[0].'.jpg';
	        if(file_exists($fullpath)) {
	            unlink($fullpath);
	        }
	        $fp = fopen($fullpath,'x');
	        fwrite($fp, $rawdata);
	        fclose($fp);
	        $product->addImageToMediaGallery($fullpath, 'thumbnail', false);
	        
	        $fullpath = 'media/catalog/product/small/';
	        $ch = curl_init ($data[0]);
	        curl_setopt($ch, CURLOPT_HEADER, 0);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	        $rawdata=curl_exec($ch);
	        curl_close ($ch);
	        $fullpath = $fullpath.$data[0].'.jpg';
	        if(file_exists($fullpath)) {
	            unlink($fullpath);
	        }
	        $fp = fopen($fullpath,'x');
	        fwrite($fp, $rawdata);
	        fclose($fp);
	        $product->addImageToMediaGallery($fullpath, 'small-image', false);
	        
	        $fullpath = 'media/catalog/product/high/';
	        $ch = curl_init ($data[0]);
	        curl_setopt($ch, CURLOPT_HEADER, 0);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	        $rawdata=curl_exec($ch);
	        curl_close ($ch);
	        $fullpath = $fullpath.$data[13].'.jpg';
	        if(file_exists($fullpath)) {
	            unlink($fullpath);
	        }
	        $fp = fopen($fullpath,'x');
	        fwrite($fp, $rawdata);
	        fclose($fp);
	        $product->addImageToMediaGallery($fullpath, 'image', false);
	        
	        */
	        
	        $product->setAttributeSetId(4); // need to look this up. 4 je default.
	        //$product->setCategoryIds(array($categories[$data[11]])); // need to look these up
	       //	$product->setCategoryIds(array(2)); // need to look these up

	        $product->setWeight(0);
	        $product->setTaxClassId(2); // taxable goods
	        $product->setVisibility(4); // catalog, search
	        $product->setStatus(1); // enabled
	        
	        // assign product to the default website
	        $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
	         
	        
/*	        $stockData = $product->getStockData();
	        $stockData['qty'] = $data[18]; //18
	        $stockData['is_in_stock'] = $data[17]=="In Stock"?1:0; //17
	        $stockData['manage_stock'] = 1;
	        $stockData['use_config_manage_stock'] = 0;
*/
 

	        $product->save();   

	        $stockItem = Mage::getModel('cataloginventory/stock_item');
			$stockItem->assignProduct($product);
			$stockItem->setData('is_in_stock', 1);
			$stockItem->setData('stock_id', 1);
			$stockItem->setData('store_id', 1);
			$stockItem->setData('manage_stock', 1);
			$stockItem->setData('use_config_manage_stock', 0);
			$stockItem->setData('min_sale_qty', 1);
			$stockItem->setData('use_config_min_sale_qty', 0);
			$stockItem->setData('max_sale_qty', 1000);
			$stockItem->setData('use_config_max_sale_qty', 0);
			$stockItem->setData('qty', $data[3]);
			$stockItem->save(); 
	       
	        
	    }

	    fclose($handle);

	}

	//exec("php -f /var/www/import/web/shell/indexer.php reindexall");            // reindex all (apsolute path)
  