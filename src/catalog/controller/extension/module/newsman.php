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

		//List Import
		if ($_GET["cron"] == "true")
		{
			$this->restCallParams = str_replace("{{userid}}", $setting["newsmanuserid"], $this->restCallParams);
			$this->restCallParams = str_replace("{{apikey}}", $setting["newsmanapikey"], $this->restCallParams);
			$this->restCallParams = str_replace("{{method}}", "import.csv.json", $this->restCallParams);

			$client = new Newsman_Client($setting["newsmanuserid"], $setting["newsmanapikey"]);

			$csvdata = array();

			$csvcustomers = $this->getCustomers();

			$csvdata = $this->getOrders();

			if (empty($csvdata))
			{
				$data["message"] .= PHP_EOL . "No data present in your store";
				$this->SetOutput($data);
				return;
			}

			$segments = null;

			if (array_key_exists("newsmansegment", $setting))
			{
				if ($setting["newsmansegment"] != "1" && $setting["newsmansegment"] != null)
				{
					$segments = array($setting["newsmansegment"]);
				}
			}

			//Import

			if ($setting["newsmantype"] == "customers")
			{
				//Customers
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
						$this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
					}
				}

				if (count($customers_to_import) > 0)
				{
					$this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
				}

				unset($customers_to_import);

			} else
			{
				//Subscribers table
				try
				{
					$batchSize = 5000;

					$customers_to_import = array();

					foreach ($csvcustomers as $item)
					{
						if ($item["newsletter"] == 0)
						{
							continue;
						}

						$customers_to_import[] = array(
							"email" => $item["email"],
							"firstname" => $item["firstname"]
						);

						if ((count($customers_to_import) % $batchSize) == 0)
						{
							$this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
						}
					}

					if (count($customers_to_import) > 0)
					{
						$this->_importData($customers_to_import, $setting["newsmanlistid"], $segments, $client);
					}

					unset($customers_to_import);

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
							$this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
						}
					}

					if (count($customers_to_import) > 0)
					{
						$this->_importDatas($customers_to_import, $setting["newsmanlistid"], $segments, $client);
					}

					unset($customers_to_import);

				} catch (Exception $ex)
				{
					$this->SetOutput($data);
				}

				$data["message"] .= PHP_EOL . "Subscribers imported successfully";

				//Subscribers table
			}
			//Import


			echo "Cron successfully done";
		} //List Import
		else
		{
			echo "Incorrect params, follow instructions for cron url";
		}
		return $this->load->view('extension/module/newsman', $data);
	}

	public function getOrders($data = array())
	{
		$sql = "SELECT o.order_id, o.email, o.firstname, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status, o.shipping_code, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified FROM `" . DB_PREFIX . "order` o";

		if (isset($data['filter_order_status']))
		{
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id)
			{
				$implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode)
			{
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} else
		{
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (!empty($data['filter_order_id']))
		{
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer']))
		{
			$sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added']))
		{
			$sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified']))
		{
			$sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total']))
		{
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

		if (isset($data['sort']) && in_array($data['sort'], $sort_data))
		{
			$sql .= " ORDER BY " . $data['sort'];
		} else
		{
			$sql .= " ORDER BY o.order_id";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC'))
		{
			$sql .= " DESC";
		} else
		{
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit']))
		{
			if ($data['start'] < 0)
			{
				$data['start'] = 0;
			}

			if ($data['limit'] < 1)
			{
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
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

		$source = self::safeForCsv("opencart 2.3 newsman plugin");
		foreach ($data as $_dat)
		{
			$csv .= sprintf(
				"%s,%s",
				self::safeForCsv($_dat["email"]),
				$source
			);
			$csv .= PHP_EOL;
		}

		$ret = null;
		try
		{
			$ret = $client->import->csv($list, $segments, $csv);

			if ($ret == "")
			{
				throw new Exception("Import failed");
			}
		} catch (Exception $e)
		{

		}

		$data = array();
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
			$ret = $client->import->csv($list, $segments, $csv);

			if ($ret == "")
			{
				throw new Exception("Import failed");
			}
		} catch (Exception $e)
		{

		}

		$data = array();
	}

	public function getCustomers($data = array())
	{
		$sql = "SELECT *, CONCAT(c.firstname, ' ', c.lastname) AS name, cgd.name AS customer_group FROM " . DB_PREFIX . "customer c LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (c.customer_group_id = cgd.customer_group_id)";

		if (!empty($data['filter_affiliate']))
		{
			$sql .= " LEFT JOIN " . DB_PREFIX . "customer_affiliate ca ON (c.customer_id = ca.customer_id)";
		}

		$sql .= " WHERE cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		$implode = array();

		if (!empty($data['filter_name']))
		{
			$implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_email']))
		{
			$implode[] = "c.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
		}

		if (isset($data['filter_newsletter']) && !is_null($data['filter_newsletter']))
		{
			$implode[] = "c.newsletter = '" . (int)$data['filter_newsletter'] . "'";
		}

		if (!empty($data['filter_customer_group_id']))
		{
			$implode[] = "c.customer_group_id = '" . (int)$data['filter_customer_group_id'] . "'";
		}

		if (!empty($data['filter_affiliate']))
		{
			$implode[] = "ca.status = '" . (int)$data['filter_affiliate'] . "'";
		}

		if (!empty($data['filter_ip']))
		{
			$implode[] = "c.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer_ip WHERE ip = '" . $this->db->escape($data['filter_ip']) . "')";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '')
		{
			$implode[] = "c.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added']))
		{
			$implode[] = "DATE(c.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if ($implode)
		{
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

		if (isset($data['sort']) && in_array($data['sort'], $sort_data))
		{
			$sql .= " ORDER BY " . $data['sort'];
		} else
		{
			$sql .= " ORDER BY name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC'))
		{
			$sql .= " DESC";
		} else
		{
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit']))
		{
			if ($data['start'] < 0)
			{
				$data['start'] = 0;
			}

			if ($data['limit'] < 1)
			{
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

}

?>
