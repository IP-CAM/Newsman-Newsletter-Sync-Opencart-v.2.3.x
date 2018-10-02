<?php

require_once($_SERVER['DOCUMENT_ROOT'] . "/library/Newsman/Client.php");

//Admin Controller
class ControllerExtensionModuleNewsman extends Controller
{
	private $error = array();

	private $restCall = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}";
	private $restCallParams = "https://ssl.newsman.app/api/1.2/rest/{{userid}}/{{apikey}}/{{method}}{{params}}";

	public
	function index()
	{
		$this->document->addStyle('./view/stylesheet/newsman.css');

		$this->load->model('setting/setting');

		$setting = $this->model_setting_setting->getSetting('newsman');

		$data = array();

		$data["message"] = "";
		$data["userid"] = "";
		$data["apikey"] = "";

		$data["list"] = "<option value=''>Select List</option>";

		if (isset($_POST["newsmanSubmit"]))
		{
			if (empty($_POST["userid"]) || empty($_POST["apikey"]))
			{
				$data["message"] = "Please insert User Id and Api Key";
				$this->SetOutput($data);
				return;
			}

			$this->restCall = str_replace("{{userid}}", $_POST["userid"], $this->restCall);
			$this->restCall = str_replace("{{apikey}}", $_POST["apikey"], $this->restCall);
			$this->restCall = str_replace("{{method}}", "list.all.json", $this->restCall);

			$settings = $setting;
			$settings["newsmanuserid"] = $_POST["userid"];
			$settings["newsmanapikey"] = $_POST["apikey"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$_data = json_decode(file_get_contents($this->restCall), true);

			$data["list"] = "";

			foreach ($_data as $list)
			{
				$data["list"] .= "<option value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
			}

			$data["message"] = "Credentials are valid";
		}

		if (isset($_POST["newsmanSubmitSaveList"]))
		{
			if (empty($_POST["list"]))
			{
				$data["message"] = "Please select a list";
				$this->SetOutput($data);
				return;
			}

			$settings = $setting;
			$settings["newsmanlistid"] = $_POST["list"];

			$this->model_setting_setting->editSetting('newsman', $settings);

			$data["message"] = "List is saved";
		}

		//List Import
		if (isset($_POST["newsmanSubmitList"]))
		{
			$this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
			$this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);
			$this->restCallParams = str_replace("{{method}}", "import.csv.json", $this->restCallParams);

			$client = new Newsman_Client($setting["newsmanuserid"], $setting["newsmanapikey"]);

			$csvdata = array();

			$this->load->model('customer/customer');


			$csvdata = $this->model_customer_customer->getCustomers();

			if (empty($csvdata))
			{
				$data["message"] .= PHP_EOL . "No customers in your store";
				$this->SetOutput($data);
				return;
			}

			//Import
			$batchSize = 5000;

			$customers_to_import = array();

			foreach ($csvdata as $item)
			{
				$customers_to_import[] = array(
					"email" => $item["email"],
					"firstname" => $item["firstname"]
				);

				if ((count($customers_to_import) % $batchSize) == 0)
				{
					$this->_importData($customers_to_import, $setting["newsmanlistid"], null, $client);
				}
			}

			if (count($customers_to_import) > 0)
			{
				$this->_importData($customers_to_import, $setting["newsmanlistid"], null, $client);
			}

			unset($customers_to_import);

			$data["message"] .= PHP_EOL . "Customer Newsletter subscribers imported successfully";

			//Subscribers table

			try
			{

				$csvdata = $this->getSubscribers();

				if (empty($csvdata))
				{
					$data["message"] .= PHP_EOL . "No subscribers in your store";
					$this->SetOutput($data);
					return;
				}

				$batchSize = 5000;

				$customers_to_import = array();

				foreach ($csvdata as $item)
				{
					$customers_to_import[] = array(
						"email" => $item["email"]
					);

					if ((count($customers_to_import) % $batchSize) == 0)
					{
						$this->_importDatas($customers_to_import, $setting["newsmanlistid"], null, $client);
					}
				}

				if (count($customers_to_import) > 0)
				{
					$this->_importDatas($customers_to_import, $setting["newsmanlistid"], null, $client);
				}

				unset($customers_to_import);

				$data["message"] .= PHP_EOL . "Subscribers imported successfully";

			}
			catch(Exception $ex)
			{
				$this->SetOutput($data);
			}

			//Subscribers table

			//Import

			/*$max = (count($edata["email"]) <= 5000) ? count($edata["email"]) : 5000;

			for ($int = 0; $int < count($edata["email"]); $int++)
			{
				$csv .= $edata['email'][$int] . ","
					. $edata['firstname'][$int] . " " . $edata["lastname"][$int] . ","
					. "opencart newsman plugin"
					. PHP_EOL;

				if ($int == $max-1)
				{
					$max += (count($edata["email"]) - $int <= 5000) ? count($edata["email"]) - $int : 5000;

					$ret = $client->import->csv($setting["newsmanlistid"], array(), $csv);

					$csv = "";
					$csv = "email,name,source" . PHP_EOL;
				}
			}*/

			/*$this->restCallParams = str_replace("{{params}}", "?list_id=" . $_POST["list"] . "&segments=" . "&csv_data=" . $csv, $this->restCallParams);
die($this->restCallParams);
			$_data = json_decode(file_get_contents($this->restCallParams), true);
			*/
		}
		//List Import

		$this->SetOutput($data);
	}

	public
	function validate()
	{
	}

	public
	function install()
	{
	}

	public
	function uninstall()
	{
	}

	public static function safeForCsv($str)
	{
		return '"' . str_replace('"', '""', $str) . '"';
	}

	public function _importData(&$data, $list, $segments = null, $client)
	{
		$csv = '"email","firstname","source"' . PHP_EOL;

		$source = self::safeForCsv("opencart 2.3 newsman plugin");
		foreach ($data as $_dat)
		{
			$csv .= sprintf(
				"%s,%s,%s",
				self::safeForCsv($_dat["email"]),
				self::safeForCsv($_dat["firstname"]),
				$source
			);
			$csv .= PHP_EOL;
		}

		$ret = null;
		try
		{
			$ret = $client->import->csv($list, array(), $csv);

			if ($ret == "")
			{
				throw new Exception("Import failed");
			}
		} catch (Exception $e)
		{

		}

		$data = array();
	}

	public function _importDatas(&$data, $list, $segments = null, $client)
	{
		$csv = '"email","source"' . PHP_EOL;

		$source = self::safeForCsv("opencart 2.3 newsman plugin");
		foreach ($data as $_dat)
		{
			$csv .= sprintf(
				"%s,%s,%s",
				self::safeForCsv($_dat["email"]),
				$source
			);
			$csv .= PHP_EOL;
		}

		$ret = null;
		try
		{
			$ret = $client->import->csv($list, array(), $csv);

			if ($ret == "")
			{
				throw new Exception("Import failed");
			}
		} catch (Exception $e)
		{

		}

		$data = array();
	}

	public function getSubscribers($data = array())
	{
		$sql = "SELECT * FROM " . DB_PREFIX . "subscribers";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function SetOutput($data)
	{
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$setting = $this->model_setting_setting->getSetting('newsman');

		if (!empty($setting["newsmanuserid"]) && !empty($setting["newsmanapikey"]))
		{
			$this->restCall = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCall);
			$this->restCall = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCall);
			$this->restCall = str_replace("{{method}}", "list.all.json", $this->restCall);

			$_data = json_decode(file_get_contents($this->restCall), true);

			$data["list"] = "";

			foreach ($_data as $list)
			{
				if (!empty($setting["newsmanlistid"]) && $setting["newsmanlistid"] == $list["list_id"])
				{
					$data["list"] .= "<option selected value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
				} else
				{
					$data["list"] .= "<option value='" . $list["list_id"] . "'>" . $list["list_name"] . "</option>";
				}
			}

			$data["userid"] = (empty($setting["newsmanuserid"])) ? "" : $setting["newsmanuserid"];
			$data["apikey"] = (empty($setting["newsmanapikey"])) ? "" : $setting["newsmanapikey"];
		}

		$this->response->setOutput($this->load->view('extension/module/newsman', $data));
	}
}

?>