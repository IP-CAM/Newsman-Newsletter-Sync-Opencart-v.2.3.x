<?php
//Catalog Controller

require_once($_SERVER['DOCUMENT_ROOT'] . "/library/Newsman/Client.php");

class ControllerExtensionmoduleNewsman extends Controller
{

    private $restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";

    public function index()
    {
        $data = array();

        $this->load->model('setting/setting');

        $setting = $this->model_setting_setting->getSetting('newsman');

        $cron = (empty($_GET["cron"]) ? "" : $_GET["cron"]);

        //cron
        if (!empty($cron)) {

            if(empty($setting["newsmanuserid"]) || empty($setting["newsmanapikey"]) || empty($setting["newsmantype"]))
            {             
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode("403"));
                return;
            }

            $this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
            $this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);
            $this->restCallParams = str_replace("{{method}}", "import.csv.json", $this->restCallParams);

            $client = new Newsman_Client($setting["newsmanuserid"], $setting["newsmanapikey"]);
         
            $csvcustomers = $this->getCustomers();

            $csvdata = $this->getOrders();

            if (empty($csvdata)) {
                $data["message"] .= PHP_EOL . "No data present in your store";
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($data));
                return;
            }

            $segments = null;

            if (array_key_exists("newsmansegment", $setting)) {
                if ($setting["newsmansegment"] != "1" && $setting["newsmansegment"] != null) {
                    $segments = array($setting["newsmansegment"]);
                }
            }

            //Import

            if ($setting["newsmantype"] == "customers") { 
                
                //Customers who ordered
                
                $batchSize = 9000;

                $customers_to_import = array();

                foreach ($csvdata as $item) {
                    $customers_to_import[] = array(
                        "email" => $item["email"],
                        "firstname" => $item["firstname"]
                    );

                    if ((count($customers_to_import) % $batchSize) == 0) {
                        $this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                    }
                }

                if (count($customers_to_import) > 0) {
                    $this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                }

                unset($customers_to_import);
                
                //Customers who ordered

            } else {
                    $batchSize = 9000;

                    //Customers table

                    $customers_to_import = array();

                    foreach ($csvcustomers as $item) {
                        if ($item["newsletter"] == 0) {
                            continue;
                        }

                        $customers_to_import[] = array(
                            "email" => $item["email"],
                            "firstname" => $item["firstname"]
                        );

                        if ((count($customers_to_import) % $batchSize) == 0) {
                            $this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                        }
                    }

                    if (count($customers_to_import) > 0) {
                        $this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                    }

                    unset($customers_to_import);
                    
                    //Customers table

                    //Subscribers table
                    
                    try{
                    
                    $csvdata = $this->getSubscribers();

                    if (empty($csvdata)) {
                        $data["message"] .= PHP_EOL . "No subscribers in your store";
                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode($data["message"]));
                        return;
                    }

                    $batchSize = 9000;

                    $customers_to_import = array();

                    foreach ($csvdata as $item) {
                        $customers_to_import[] = array(
                            "email" => $item["email"]
                        );

                        if ((count($customers_to_import) % $batchSize) == 0) {
                            $this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                        }
                    }

                    if (count($customers_to_import) > 0) {
                        $this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                    }

                    unset($customers_to_import);
                    
                    }
                    catch(Exception $e)
                    {
                        echo "\nMissing " . DB_PREFIX . "newsletter table, continuing import without issues";
                    }
                    
                    //Subscribers table
                    
                    //OC journal framework table
                    
                    try{
                    
                    $csvdata = $this->getSubscribersOcJournal();

                    if (empty($csvdata)) {
                        $data["message"] .= PHP_EOL . "No subscribers in your store";
                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode($data["message"]));
                        return;
                    }

                    $batchSize = 9000;

                    $customers_to_import = array();

                    foreach ($csvdata as $item) {
                        $customers_to_import[] = array(
                            "email" => $item["email"]
                        );

                        if ((count($customers_to_import) % $batchSize) == 0) {
                            $this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                        }
                    }

                    if (count($customers_to_import) > 0) {
                        $this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
                    }

                    unset($customers_to_import);
                    
                    }
                    catch(Exception $e)
                    {
                        echo "\nMissing oc_journal3_newsletter table, continuing import without issues";
                    }
                    
                    //OC journal framework table
            }
            //Import

            echo "Cron successfully done";
        }
        //cron
        //webhooks   
        elseif(!empty($_GET["webhook"]) && $_GET["webhook"] == true)
        {           
            $var = file_get_contents('php://input');      

            $newsman_events = urldecode($var);   
            $newsman_events = str_replace("newsman_events=", "", $newsman_events);                     
            $newsman_events = json_decode($newsman_events, true);

            foreach($newsman_events as $event)
            {                  
                if($event['type'] == "spam" || $event['type'] == "unsub")
                {                         
                    $sql = "UPDATE  " . DB_PREFIX . "customer SET `newsletter`='0' WHERE `email`='" . $event["data"]["email"] . "'";

                    $query = $this->db->query($sql);                               
                }
            }
        }        
        else {

            //fetch   
            if(!empty($_GET["newsman"]))
            {
                if(empty($setting["newsmanapiallow"]) || $setting["newsmanapiallow"] != "on")
                {
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode("403"));
                    return;
                }

                $this->newsmanFetchData($setting["newsmanapikey"]);
            }
        }      

        return $this->load->view('extension/module/newsman', $data);
    }

    public function newsmanFetchData($_apikey)
    {
        $apikey = (empty($_GET["apikey"])) ? "" : $_GET["apikey"];
        $newsman = (empty($_GET["newsman"])) ? "" : $_GET["newsman"];
        $productId = (empty($_GET["product_id"])) ? "" : $_GET["product_id"];
        $orderId = (empty($_GET["order_id"])) ? "" : $_GET["order_id"];
        $start = (!empty($_GET["start"]) && $_GET["start"] >= 0) ? $_GET["start"] : 1;
        $limit = (empty($_GET["limit"])) ? 1000 : $_GET["limit"];

        if (!empty($newsman) && !empty($apikey)) {
            $apikey = $_GET["apikey"];
            $currApiKey = $_apikey;

            if ($apikey != $currApiKey) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode("403"));
                return;
            }

            switch ($_GET["newsman"]) {
                case "orders.json":

                    $ordersObj = array();

                    $this->load->model('catalog/product');
                    $this->load->model('checkout/order');

                    $orders = $this->getOrders(array("start" => $start, "limit" => $limit));                    
                    
                    if(!empty($orderId))
                    {
                        $orders = $this->model_checkout_order->getOrder($orderId);                        
                        $orders = array(
                            $orders
                        );
                    }                    

                    foreach ($orders as $item) {

                        $products = $this->getProductsByOrder($item["order_id"]);
                        $productsJson = array();

                        foreach ($products as $prodOrder) {
                            
                            $prod = $this->model_catalog_product->getProduct($prodOrder["product_id"]);

                            $image = "";

                            if(!empty($prod["image"]))
                            {
                                $image = explode(".", $prod["image"]);
                                $image = $image[1];  
                                $image = str_replace("." . $image, "-500x500" . '.' . $image, $prod["image"]);    
                                $image = 'https://' . $_SERVER['SERVER_NAME'] . '/image/cache/' . $image;                                
                            }

                            $productsJson[] = array(
                                "id" => $prodOrder['product_id'],
                                "name" => $prodOrder['name'],
                                "quantity" => $prodOrder['quantity'],
                                "price" => $prodOrder['price'],
                                "price_old" => (empty($prodOrder["special"]) ? "" : $prodOrder["special"]),
                                "image_url" => $image,
                                "url" => 'https://' . $_SERVER['SERVER_NAME'] . '/index.php?route=product/product&product_id=' . $prodOrder["product_id"]
                            );
                        }

                        $ordersObj[] = array(
                            "order_no" => $item["order_id"],
                            "date" => "",
                            "status" => "",
                            "lastname" => "",
                            "firstname" => $item["firstname"],
                            "email" => $item["email"],
                            "phone" => "",
                            "state" => "",
                            "city" => "",
                            "address" => "",
                            "discount" => "",
                            "discount_code" => "",
                            "shipping" => "",
                            "fees" => 0,
                            "rebates" => 0,
                            "total" => $item["total"],
                            "products" => $productsJson
                        );
                    }

					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($ordersObj, JSON_PRETTY_PRINT));
                    return;

                    break;

                case "products.json":

                    $this->load->model('catalog/product');

                    $products = $this->model_catalog_product->getProducts(array("start" => $start, "limit" => $limit));

                    if(!empty($productId))
                    {
                        $products = $this->model_catalog_product->getProduct($productId);
                        $products = array(
                            $products
                        );
                    }

                    $productsJson = array();

                    foreach ($products as $prod) {

                        $image = "";

                        //price old special becomes price
                        $price = (!empty($prod["special"])) ? $prod["special"] : $prod["price"];
                        //price becomes price old
                        $priceOld = (!empty($prod["special"])) ? $prod["price"] : "";

                        if(!empty($prod["image"]))
                        {
                            $image = explode(".", $prod["image"]);
                            $image = $image[1];  
                            $image = str_replace("." . $image, "-500x500" . '.' . $image, $prod["image"]);    
                            $image = 'https://' . $_SERVER['SERVER_NAME'] . '/image/cache/' . $image;                                
                        }

                        $productsJson[] = array(
                            "id" => $prod["product_id"],
                            "name" => $prod["name"],
                            "stock_quantity" => $prod["quantity"],
                            "price" => $price,
                            "price_old" => $priceOld,
                            "image_url" => $image,
                            "url" => 'https://' . $_SERVER['SERVER_NAME'] . '/index.php?route=product/product&product_id=' . $prod["product_id"]
                        );
                    }

					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($productsJson, JSON_PRETTY_PRINT));
                    return;

                    break;

                case "customers.json":

                    $wp_cust = $this->getCustomers(array("start" => $start, "limit" => $limit));
                    $custs = array();

                    foreach ($wp_cust as $users) {
                        $custs[] = array(
                            "email" => $users["email"],
                            "firstname" => $users["name"],
                            "lastname" => ""
                        );
                    }

					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($custs, JSON_PRETTY_PRINT));
                    return;

                    break;

                case "subscribers.json":

                    $wp_subscribers = $this->getCustomers(array("start" => $start, "limit" => $limit, "filter_newsletter" => 1));
                    $subs = array();

                    foreach ($wp_subscribers as $users) {
                        $subs[] = array(
                            "email" => $users["email"],
                            "firstname" =>$users["name"],
                            "lastname" => ""
                        );
                    }

					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($subs, JSON_PRETTY_PRINT));
                    return;

                    break;
                case "version.json":
                    $version = array(
                    "version" => "Opencart 2.3.x"
                    );

                    $this->response->addHeader('Content-Type: application/json');
                            $this->response->setOutput(json_encode($version, JSON_PRETTY_PRINT));
                    return;
            
                    break;

            }
        } else {
           //allow
        }
    }

    public function getOrders($data = array())
    {
        $sql = "SELECT o.order_id, o.email, o.firstname, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o";

        if (isset($data['filter_order_status'])) {
            $implode = array();

            $order_statuses = explode(',', $data['filter_order_status']);

            foreach ($order_statuses as $order_status_id) {
                $implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
            }

            if ($implode) {
                $sql .= " WHERE (" . implode(" OR ", $implode) . ")";
            }
        } else {
            $sql .= " WHERE o.order_status_id > '0'";
        }

        if (!empty($data['filter_order_id'])) {
            $sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
        }

        if (!empty($data['filter_customer'])) {
            $sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
        }

        if (!empty($data['filter_date_added'])) {
            $sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        if (!empty($data['filter_date_modified'])) {
            $sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
        }

        if (!empty($data['filter_total'])) {
            $sql .= " AND o.total = '" . (float)$data['filter_total'] . "'";
        }

        $sort_data = array(
            'o.order_id',
            'customer',
            'order_status',
            'o.date_added',
            'o.date_modified',
            'o.total'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY o.order_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductsByOrder($order_id)
    {
        $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        return $order_product_query->rows;
    }	

	public function getSubscribers()
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "newsletter";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function _importDatas(&$data, $list, $segments = null, $client)
    {
        $csv = '"email","source"' . PHP_EOL;

        $source = self::safeForCsv("opencart 2.3 subscribers newsman plugin");
        foreach ($data as $_dat) {
            $csv .= sprintf(
                "%s,%s",
                self::safeForCsv($_dat["email"]),
                $source
            );
            $csv .= PHP_EOL;
        }

        $ret = null;
        try {
            $ret = $client->import->csv($list, $segments, $csv);

            if ($ret == "") {
                throw new Exception("Import failed");
            }
        } catch (Exception $e) {

        }

        $data = array();
    }

    public static function safeForCsv($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    public function _importData(&$data, $list, $segments = null, $client)
    {
        $csv = '"email","fullname","source"' . PHP_EOL;

        $source = self::safeForCsv("opencart 2.3 customers with newsletter newsman plugin");
        foreach ($data as $_dat) {
            $csv .= sprintf(
                "%s,%s,%s",
                self::safeForCsv($_dat["email"]),
                self::safeForCsv($_dat["firstname"]),
                $source
            );
            $csv .= PHP_EOL;
        }

        $ret = null;
        try {
            $ret = $client->import->csv($list, $segments, $csv);

            if ($ret == "") {
                throw new Exception("Import failed");
            }
        } catch (Exception $e) {

        }

        $data = array();
    }

    public function getCustomers($data = array())
    {
        $sql = "SELECT *, CONCAT(c.firstname, ' ', c.lastname) AS name, cgd.name AS customer_group FROM " . DB_PREFIX . "customer c LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (c.customer_group_id = cgd.customer_group_id)";

        if (!empty($data['filter_affiliate'])) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "customer_affiliate ca ON (c.customer_id = ca.customer_id)";
        }

        $sql .= " WHERE cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $implode = array();

        if (!empty($data['filter_name'])) {
            $implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_email'])) {
            $implode[] = "c.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
        }

        if (isset($data['filter_newsletter']) && !is_null($data['filter_newsletter'])) {
            $implode[] = "c.newsletter = '" . (int)$data['filter_newsletter'] . "'";
        }

        if (!empty($data['filter_customer_group_id'])) {
            $implode[] = "c.customer_group_id = '" . (int)$data['filter_customer_group_id'] . "'";
        }

        if (!empty($data['filter_affiliate'])) {
            $implode[] = "ca.status = '" . (int)$data['filter_affiliate'] . "'";
        }

        if (!empty($data['filter_ip'])) {
            $implode[] = "c.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer_ip WHERE ip = '" . $this->db->escape($data['filter_ip']) . "')";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $implode[] = "c.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_date_added'])) {
            $implode[] = "DATE(c.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        if ($implode) {
            $sql .= " AND " . implode(" AND ", $implode);
        }

        $sort_data = array(
            'name',
            'c.email',
            'customer_group',
            'c.status',
            'c.ip',
            'c.date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    //order hooks
    public function eventAddOrderHistory($route,$data) {        
      
        $this->load->model('setting/setting');

        $setting = $this->model_setting_setting->getSetting('newsman');
        
        if(empty($setting["newsmanuserid"]) || empty($setting["newsmanlistid"]))
            return;

        $userId = $setting["newsmanuserid"];
        $apiKey = $setting["newsmanapikey"];
        $list = $setting["newsmanlistid"];

        $status = $this->getOrderStatus($data[1]);
            
        $url = "https://ssl.newsman.app/api/1.2/rest/" . $userId . "/" . $apiKey . "/remarketing.setPurchaseStatus.json?list_id=" . $list . "&order_id=" . $data[0] . "&status=" . $status;        
     
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $url);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);        
        $ret = curl_exec($cURLConnection);
        curl_close($cURLConnection);              

    }

    public function getOrderStatus($id){
        $status = "";

        switch($id)
        {
            case 7:
                $status = "Canceled";
            break;
            case 9:
                $status = "Canceled Reversal";
            break;
            case 13:
                $status = "Chargeback";
            break;
            case 5:
                $status = "Complete";
            break;
            case 8:
                $status = "Denied";
            break;
            case 14:
                $status = "Expired";
            break;
            case 10:
                $status = "Failed";
            break;
            case 1:
                $status = "Pending";
            break;
            case 15:
                $status = "Processed";
            break;
            case 2:
                $status = "Processing";
            break;
            case 11:
                $status = "Refunded";
            break;
            case 12:
                $status = "Reversed";
            break;
            case 2:
                $status = "Processing";
            break;
            case 3:
                $status = "Shipped";
            break;
            case 16:
                $status = "Voided";
            break;  
	        default:
	        	$status = "New";
	        break;                                                
        }

        return $status;
    }

}

?>
