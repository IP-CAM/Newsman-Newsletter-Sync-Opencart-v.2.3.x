<?php



class ControllerExtensionAnalyticsNewsmanremarketing extends Controller

{

	protected function getCategoryPath($category_id)
	{
		$path = '';

		$category = $this->model_catalog_category->getCategory($category_id);

		if ($category['parent_id'] != 0)
		{
			$path .= $this->getCategoryPath($category['parent_id']) . ' / ';
		}

		$path .= $category['name'];

		return $path;
	}



	// Maps Opencart product data to Google Analytics product structure

	protected function getProduct($order_id, $product)

	{
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('checkout/order');

		$oc_product = $this->model_catalog_product->getProduct($product["product_id"]);

		// get product options
		
		$product["variant"] = '';

		/*
		$variants = $this->model_checkout_order->getOrderOptions($order_id, $product["order_product_id"]);

		foreach ($variants as $variant)

			$product["variant"] = $variant["value"] . " | ";

		if ($product["variant"])

		{

			$product["variant"] = substr($product["variant"], 0, -3);

		}*/

		// get category path	

		$oc_categories = $this->model_catalog_product->getCategories($product["product_id"]);

		$oc_category = [];

		if (sizeof($oc_categories) > 0)
		{
			$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);

			if (sizeof($oc_category) > 0)
			{
				$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);

			} else
			{
				$oc_category["path"] = '';
			}
		}

		// $this->log->write(print_r($this->model_checkout_order->getOrderOptions($order_id, $product["order_product_id"]), TRUE));

		$ga_product = [

			"id" => $product["product_id"],

			"name" => $product["name"],

			"SKU" => $oc_product["sku"],

			"brand" => $oc_product["manufacturer"],

			"category" => $oc_category["path"],

			"variant" => $product["variant"],

			"quantity" => $product["quantity"],

			"price" => $product["price"]

		];

		return $ga_product;
	}

	protected function getShipping($totals)
	{
		$shipping = 0.00;	

	if(!empty($totals))
	{

		if(is_array($totals))
		{

			foreach ($totals as $total)

			{

				if ($total["code"] == 'shipping')

				{

					$shipping += $total["value"];

				}

			}
		}
		else{
			$shipping = $totals;
		}

	}

		return $shipping;

	}

	protected function getTax($totals)

	{

		$tax = 0.00;
	
		if(!empty($totals))
		{

			if(is_array($totals))
			{

				foreach ($totals as $total)

				{

					if ($total["code"] == 'tax')

					{

						$tax += $total["value"];

					}

				}

			}
			else{
				$tax = $totals;
			}

		}

		return $tax;

	}

	public function index()

	{

		$this->load->model('checkout/order');



		$endpoint = "https://retargeting.newsmanapp.com/js/retargeting/track.js";

		$endpointHost = "https://retargeting.newsmanapp.com";



		$tag = "";



		// get Route

		$route = '';

		if (isset($this->request->get['route']))

		{

			$route = (string)$this->request->get['route'];

		}	


		// get Tracking ID

		$tracking_id = $this->config->get('analytics_newsmanremarketing_trackingid');



		// If not Purchase

		if ($route != 'checkout/success')

		{

			$tag .= <<<TAG

				<script>
/*if jquery is mandatory
if (typeof jQuery == 'undefined') {
var script = document.createElement('script');
script.src = 'https://code.jquery.com/jquery-3.4.1.min.js';
script.type = 'text/javascript';
document.getElementsByTagName('head')[0].appendChild(script);
}
*/

		let newsmanLoadCartEvents = function(){};

		var _nzmPluginInfo = '1.0:Opencart 2.3.x';
		var _nzm = _nzm || []; var _nzm_config = _nzm_config || []; _nzm_config['disable_datalayer']=1; _nzm_tracking_server = '$endpointHost';

        (function() {var a, methods, i;a = function(f) {return function() {_nzm.push([f].concat(Array.prototype.slice.call(arguments, 0)));

        }};methods = ['identify', 'track', 'run'];for(i = 0; i < methods.length; i++) {_nzm[methods[i]] = a(methods[i])};

        s = document.getElementsByTagName('script')[0];var script_dom = document.createElement('script');script_dom.async = true;

        script_dom.id = 'nzm-tracker';script_dom.setAttribute('data-site-id', '$tracking_id');

        script_dom.src = '$endpoint';s.parentNode.insertBefore(script_dom, s);})();       

        _nzm.run( 'require', 'ec' );

		//remove from cart widget
		
		newsmanLoadCartEvents = function()
		{	
			$('#cart .table button').each(function(element, item) {			
		
				$(this).unbind('click');

				$(this).click(function(){

					var qty = $(this).parent().parent().find('.text-right:first').html();
					qty = qty.replace("x ", "");
					
					var id = $(this).parent().parent().find('.text-left a').attr("href");
					id = id.split("product_id=");
					for(const item of id)
					{
						if (typeof parseInt(item) === 'number') {
							id = item;
						}
					}

					if (isNaN(id)) {
						id = 0;
					}					

					//unsupported event
					if(id != 0)
					{
					_nzm.run("ec:addProduct", {
						id: id,
						quantity: qty,
					  });
		
					  _nzm.run("ec:setAction", "remove");
					  _nzm.run("send", "event", "UX", "click", "remove from cart");
					}
					else{
						console.log("unsupported event, remove from cart widget");
					}

					setTimeout(function(){
						newsmanLoadCartEvents();
					}, 2000);

				});		
			
			});

		}
		
		setTimeout(function(){
			newsmanLoadCartEvents();
		}, 2000);

				</script>

TAG;

			switch ($route)

			{
				//home start

				case "":

					$tag .= "
					<script>
	var _items = jQuery('.product-layout');

					for (var x = 0; x <= _items.length; x++) {				
						
						$('.product-layout:eq(' + x + ') .btn-cart').on('click',function () {
								
							var _c = $(this).closest('.product-layout');

							var id = _c.find('.button-group a').last().attr('onclick');
							id = id.split('\'');	
							id = id[1];					

							var name = _c.find('.caption .name').text();
							name = $.trim(name);						

							var category = '';
							var price = _c
								.find('.caption .price .price-new')
								.text();
                            								
                            if(price == '' || price == undefined)
                                price = _c
								.find('.caption .price .price-normal')
								.text();
								
							price = price.trim();
							price = price.split(' ').join('');
							price = price.split(',').join('.');
							price = price.split('Lei').join('');							
							price = price.split('lei').join('');															
					
							_nzm.run('ec:addProduct', {
								id: id,
								name: name,
								category: category,
								price: price,
								quantity: '1',
							});
							_nzm.run('ec:setAction', 'add');
							_nzm.run('send', 'event', 'UX', 'click', 'add to cart');

							setTimeout(function(){
								newsmanLoadCartEvents();
							}, 2000);
							
						});

					}
					</script>
					";

					break;

				case "common/home":
					
					$tag .= "
					<script>
	var _items = jQuery('.product-layout');

					for (var x = 0; x <= _items.length; x++) {				
						
						$('.product-layout:eq(' + x + ') .btn-cart').on('click',function () {
								
							var _c = $(this).closest('.product-layout');

							var id = _c.find('.button-group a').last().attr('onclick');
							id = id.split('\'');	
							id = id[1];					

							var name = _c.find('.caption .name').text();
							name = $.trim(name);						

							var category = '';
							var price = _c
								.find('.caption .price .price-new')
								.text();
                            								
                            if(price == '' || price == undefined)
                                price = _c
								.find('.caption .price .price-normal')
								.text();
								
							price = price.trim();
							price = price.split(' ').join('');
							price = price.split(',').join('.');
							price = price.split('Lei').join('');							
							price = price.split('lei').join('');															
					
							_nzm.run('ec:addProduct', {
								id: id,
								name: name,
								category: category,
								price: price,
								quantity: '1',
							});
							_nzm.run('ec:setAction', 'add');
							_nzm.run('send', 'event', 'UX', 'click', 'add to cart');

							setTimeout(function(){
								newsmanLoadCartEvents();
							}, 2000);
							
						});

					}
					</script>
					";

					break;

					//home end

				case "product/product":

					$this->load->model('catalog/product');

					$this->load->model('catalog/category');

					$this->load->model('checkout/order');



					$id = $this->request->get['product_id'];



					$oc_product = $this->model_catalog_product->getProduct($id);

					$oc_categories = $this->model_catalog_product->getCategories($id);

					$oc_category = [];

					if (sizeof($oc_categories) > 0)

					{

						$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);

						if (sizeof($oc_category) > 0)

						{

							$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);

						} else

						{

							$oc_category["path"] = '';

						}

					}



					$tag .= "

					<script>

					 _nzm.run('ec:addProduct', {

                    'id': " . $oc_product['product_id'] . ",

                    'name': '" . $oc_product['name'] . "',

                    'category': '" . $oc_category['path'] . "',

                    price: " . $oc_product['price'] . ",

                    list: 'Product Page'});
					_nzm.run('ec:setAction', 'detail');


//window.onload = function() {
document.addEventListener('DOMContentLoaded', function(event) {

 jQuery('#button-cart').click(function(){

	var variationBool = false;
	var variationCount = false;								
	
	$('#product .required input[type=radio]').each(function(element, item) {
	
		variationCount = true;
	
		if($(this).is(':checked'))
		{
			variationBool = true;
		}
	
	});

    var _select = $('#product .required select').val();
	if (_select === undefined)
{

}
else{
		variationCount = true;
	
		if(_select != '')
		{
			variationBool = true;
		}	
}
	
		if(variationCount == true)
		{
			if(variationBool == false)
			{		
				return;
			}		
		}

		var _classQty = '';
		var validate = jQuery('#input-quantity').val();

		if(validate != '' && validate != undefined)
		{
			_classQty = '#input-quantity';
		}
	
		if(validate == '' || validate == undefined)
		{
			validate = jQuery('#product-quantity').val();
		
			if(validate != '')
			{
				_classQty = '#product-quantity';
			}
		}
		
					_nzm.run('ec:addProduct', {

                    'id': " . $oc_product['product_id'] . ",

                    'name': '" . $oc_product['name'] . "',

                    'category': '" . $oc_category['path'] . "',

                    price: " . $oc_product['price'] . ",

                    quantity: $(_classQty).val()

                    });


                    _nzm.run('ec:setAction', 'add');

                    _nzm.run('send', 'event', 'UX', 'click', 'add to cart');

					setTimeout(function(){
						newsmanLoadCartEvents();
					}, 2000);


                    });                 

//}

});

                 </script>

                 ";

					break;



				case "checkout/cart":



					$tag .= "

					<script>

//window.onload = function() {
document.addEventListener('DOMContentLoaded', function(event) {


/*jQuery(document).ready(function () {


	$(\".cart_quantity_delete\").each(function () {

    	jQuery(this).bind(\"click\", {\"elem\": jQuery(this)}, function (ev) {

            var _c = $(this).closest('.cart_item');

            var id = ev.data.elem.attr('id');

            id = id.substr(0, id.indexOf('_'));

            var qty = _c.find('.cart_quantity_input').val();



            _nzm.run('ec:addProduct', {

                'id': id,

                'quantity': qty

            });

            _nzm.run('ec:setAction', 'remove');

            _nzm.run('send', 'event', 'UX', 'click', 'remove from cart');

		});

    });

});*/

//}

});

					</script>

					";



					break;

				case "checkout/checkout":

					$this->load->model('catalog/product');

					$this->load->model('catalog/category');

					$this->load->model('checkout/order');



					$products = $this->cart->getProducts();



					foreach ($products as $item)

					{

						$oc_categories = $this->model_catalog_product->getCategories($item["product_id"]);

						$oc_category = [];

						if (sizeof($oc_categories) > 0)

						{

							$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);

							if (sizeof($oc_category) > 0)

							{

								$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);

							} else

							{

								$oc_category["path"] = '';

							}

						}



						$tag .= "

					<script>

 _nzm.run('ec:addProduct', {

                    'id': " . $item['product_id'] . ",

                    'name': '" . $item['name'] . "',

                    'category': '" . $oc_category['path'] . "',

                    price: " . $item['price'] . ",

                    quantity: '" . $item['quantity'] . "'

                    });

					</script>";

					}



					$tag .= "<script>

    _nzm.run('ec:setAction', 'checkout');

</script>";

					break;



				case "product/category":

					$this->load->model('catalog/product');

					$this->load->model('catalog/category');



					$prod = (!empty($this->session->data['ga_orderDetails'])) ? $this->session->data['ga_orderDetails'] : array();



					$tag .= "";



					$pos = 1;

					//start script
					$tag .= "

					<script>";
					
					foreach ($prod as $item)

					{

						$oc_categories = $this->model_catalog_product->getCategories($item["product_id"]);

						$oc_category = [];

						if (sizeof($oc_categories) > 0)

						{

							$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);

							if (sizeof($oc_category) > 0)

							{

								$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);

							} else

							{

								$oc_category["path"] = '';

							}

						}
						
						$price = str_replace(',', '.', $item["price"]);
						$price = str_replace('Lei', '', $price);

						$tag .= "				

 _nzm.run('ec:addImpression', {

                    'id': " . $item['product_id'] . ",

                    'name': '" . $item['name'] . "',

                    'category': '" . $oc_category['path'] . "',

                    price: " . $price . ",

                    list: 'Category Page',

                    position: '" . $pos . "'

                    });";



						$pos++;

					}

					//end script
					$tag .= "

					//setTimeout(function(){

					var _items = jQuery('.product-layout');

					for (var x = 0; x <= _items.length; x++) {				
						
						$('.product-layout:eq(' + x + ') .btn-cart').on('click',function () {
								
							var _c = $(this).closest('.product-layout');

							var id = _c.find('.button-group a').last().attr('onclick');
							id = id.split('\'');	
							id = id[1];					

							var name = _c.find('.caption .name').text();
							name = $.trim(name);						

							var category = '';
							var price = _c
								.find('.caption .price .price-new')
								.text();
                            								
                            if(price == '' || price == undefined)
                                price = _c
								.find('.caption .price .price-normal')
								.text();
								
							price = price.trim();
							price = price.split(' ').join('');
							price = price.split(',').join('.');
							price = price.split('Lei').join('');							
							price = price.split('lei').join('');															
					
							_nzm.run('ec:addProduct', {
								id: id,
								name: name,
								category: category,
								price: price,
								quantity: '1',
							});
							_nzm.run('ec:setAction', 'add');
							_nzm.run('send', 'event', 'UX', 'click', 'add to cart');

							setTimeout(function(){
								newsmanLoadCartEvents();
							}, 2000);
							
						});

					}

				//}, 1500);

					</script>";



					break;

			}



			$tag .= <<<TAG



<script>

_nzm.run('send', 'pageview');

</script>



TAG;



			return $tag;

		} // Purchase

		else

		{

			$purchase_event = null;
			$products_event = null;
			$email = null;

			if (isset($this->session->data['ga_orderDetails']))

			{

				$orderDetails = $this->session->data['ga_orderDetails'];
				$order_id = $orderDetails["order_id"];
				 
				//$order_totals = $this->model_checkout_order->getOrderTotals($order_id);	
				
				$order_totals = $orderDetails["total"];

				$ob_products = [];
				if (isset($this->session->data['ga_orderProducts']))
				{
					foreach ($this->session->data['ga_orderProducts'] as $product)
						array_push($ob_products, $this->getProduct($order_id, $product));
				}

				foreach($ob_products as $item){
					$products_event .= 
						"_nzm.run( 'ec:addProduct', {" .
							"'id': '" . $item["id"] . "'," . 
							"'name': '" . $item["name"] . "'," . 
							"'category': '" . $item["category"] . "'," . 
							"'price': '" . $item["price"] . "'," . 
							"'quantity': '" . $item["quantity"] . "'," . 
						"} );";
				}

				$email = $orderDetails["email"];

				$ob_order = [
					"id" => $order_id,
					"affiliation" => $orderDetails["store_name"],
					"value" => $orderDetails["total"],
					"tax" => $this->getTax($order_totals),
					"shipping" => $this->getShipping($order_totals)
				];

				$purchase_event = json_encode($ob_order);

			}	

			$tag = <<<TAG



					<script>

			    var _nzmPluginInfo = '1.0:Opencart 2.3.x';
				var _nzm = _nzm || []; var _nzm_config = _nzm_config || []; _nzm_config['disable_datalayer']=1; _nzm_tracking_server = '$endpointHost';

        (function() {var a, methods, i;a = function(f) {return function() {_nzm.push([f].concat(Array.prototype.slice.call(arguments, 0)));

        }};methods = ['identify', 'track', 'run'];for(i = 0; i < methods.length; i++) {_nzm[methods[i]] = a(methods[i])};

        s = document.getElementsByTagName('script')[0];var script_dom = document.createElement('script');script_dom.async = true;

        script_dom.id = 'nzm-tracker';script_dom.setAttribute('data-site-id', '$tracking_id');

        script_dom.src = '$endpoint';s.parentNode.insertBefore(script_dom, s);})();    

        _nzm.run( 'require', 'ec' );



TAG;



			$tag .= <<<TAG
_nzm.identify({'email': '$email'});
$products_event
_nzm.run('ec:setAction', 'purchase', $purchase_event);
_nzm.run('send', 'pageview');

			</script>



TAG;



			unset($this->session->data['ga_orderDetails']);

			unset($this->session->data['ga_orderProducts']);



			return $tag;

		}

	}

}



?>