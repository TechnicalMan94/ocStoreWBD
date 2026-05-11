<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ModelServiceService extends Model {
	public function updateViewed($service_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "service SET viewed = (viewed + 1) WHERE service_id = '" . (int)$service_id . "'");
	}
	
	public function getService($service_id) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
				
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review_service r1 WHERE r1.service_id = p.service_id AND r1.status = '1' GROUP BY r1.service_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review_service r2 WHERE r2.service_id = p.service_id AND r2.status = '1' GROUP BY r2.service_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_description pd ON (p.service_id = pd.service_id) LEFT JOIN " . DB_PREFIX . "service_to_store p2s ON (p.service_id = p2s.service_id)  WHERE p.service_id = '" . (int)$service_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		if ($query->num_rows) {
			return array(
				'meta_title'       => $query->row['meta_title'],
				'noindex'          => $query->row['noindex'],
				'meta_h1'          => $query->row['meta_h1'],
				'service_id'       => $query->row['service_id'],
				'name'             => $query->row['name'],
				'description'      => $query->row['description'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'image'            => $query->row['image'],
				'rating'           => round($query->row['rating'] ? $query->row['rating'] : 0),
				'reviews'          => $query->row['reviews'],
				'sort_order'       => $query->row['sort_order'],
				'service_review'   => $query->row['service_review'],
				'status'           => $query->row['status'],
				'gstatus'           => $query->row['gstatus'],
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
				'viewed'           => $query->row['viewed'],
				'fields'           => $this->getServiceFields($service_id)
			);
		} else {
			return false;
		}
	}

	public function getServices($data = array()) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
		
		$cache = 'service.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . md5(http_build_query($data));
		
		$service_data = $this->cache->get($cache);
		
		if (!$service_data) {
			$sql = "SELECT p.service_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review_service r1 WHERE r1.service_id = p.service_id AND r1.status = '1' GROUP BY r1.service_id) AS rating FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_description pd ON (p.service_id = pd.service_id) LEFT JOIN " . DB_PREFIX . "service_to_store p2s ON (p.service_id = p2s.service_id)"; 
						
			if (!empty($data['filter_service_category_id'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "service_to_service_category a2c ON (p.service_id = a2c.service_id)";			
			}
			
			$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'"; 
			
			if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
				$sql .= " AND (";
				
				if (!empty($data['filter_name'])) {					
					if (!empty($data['filter_description'])) {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%' OR MATCH(pd.description) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "')";
					} else {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
					}
				}
				
				if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
					$sql .= " OR ";
				}
				
				if (!empty($data['filter_tag'])) {
					$sql .= "MATCH(pd.tag) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_tag'])) . "')";
				}
			
				$sql .= ")";
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}	
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}					
			}
			
			if (!empty($data['filter_service_category_id'])) {
				if (!empty($data['filter_sub_category'])) {
					$implode_data = array();
					
					$implode_data[] = (int)$data['filter_service_category_id'];
					
					$this->load->model('service/category');
					
					$categories = $this->model_service_category->getCategoriesByParentId($data['filter_service_category_id']);
										
					foreach ($categories as $service_category_id) {
						$implode_data[] = (int)$service_category_id;
					}
								
					$sql .= " AND a2c.service_category_id IN (" . implode(', ', $implode_data) . ")";	
				} else {
					$sql .= " AND a2c.service_category_id = '" . (int)$data['filter_service_category_id'] . "'";
				}
			}		
					
			$sql .= " GROUP BY p.service_id";
			
			$sort_data = array(
				'pd.name',
				//OCSTORE.COM
				'p.viewed',
				//OCSTORE.COM
				'rating',
				'p.sort_order',
				'p.date_added'
			);	
			
			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model' || $data['sort'] == 'p.date_added') {
					$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
				} else {
					$sql .= " ORDER BY " . $data['sort'];
				}
			} else {
				$sql .= " ORDER BY p.sort_order";	
			}
			
			if (isset($data['order']) && ($data['order'] == 'DESC')) {
				$sql .= " DESC, LCASE(pd.name) DESC";
			} else {
				$sql .= " ASC, LCASE(pd.name) ASC";
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
			
			$service_data = array();
					
			$query = $this->db->query($sql);
		
			foreach ($query->rows as $result) {
				$service_data[$result['service_id']] = $this->getService($result['service_id']);
			}
			
			$this->cache->set($cache, $service_data);
		}
		
		return $service_data;
	}
		
	public function getLatestServices($limit) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
				
		$cache = 'service.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $customer_group_id . '.' . (int)$limit;
		$service_data = $this->cache->get($cache);

		if (!$service_data) { 
			$query = $this->db->query("SELECT p.service_id FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_to_store p2s ON (p.service_id = p2s.service_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC LIMIT " . (int)$limit);
		 	 
			foreach ($query->rows as $result) {
				$service_data[$result['service_id']] = $this->getService($result['service_id']);
			}
			
			$this->cache->set($cache, $service_data);
		}
		
		return $service_data;
	}
	
	public function getPopularServices($limit) {
		$service_data = array();
		
		$query = $this->db->query("SELECT p.service_id FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_to_store p2s ON (p.service_id = p2s.service_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);
		
		foreach ($query->rows as $result) { 		
			$service_data[$result['service_id']] = $this->getService($result['service_id']);
		}
					 	 		
		return $service_data;
	}
		
	public function getServiceImages($service_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_image WHERE service_id = '" . (int)$service_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}
	
	public function getServiceRelated($service_id) {
		$service_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_related pr LEFT JOIN " . DB_PREFIX . "service p ON (pr.related_id = p.service_id) LEFT JOIN " . DB_PREFIX . "service_to_store p2s ON (p.service_id = p2s.service_id) WHERE pr.service_id = '" . (int)$service_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		foreach ($query->rows as $result) { 
			$service_data[$result['related_id']] = $this->getService($result['related_id']);
		}
		
		return $service_data;
	}
	
	public function getServiceRelatedByProduct($data) {
		
		$service_data = array();
		
		$this->load->model('service/service');
		
		$sql = "SELECT * FROM " . DB_PREFIX . "product_related_service np LEFT JOIN " . DB_PREFIX . "service p ON (np.service_id = p.service_id) LEFT JOIN " . DB_PREFIX . "service_to_store p2s ON (p.service_id = p2s.service_id) WHERE np.product_id = '" . (int)$data['product_id'] . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' LIMIT " . (int)$data['limit'];

		$query = $this->db->query($sql);

		foreach ($query->rows as $result) { 
			$service_data[$result['service_id']] = $this->model_service_service->getService($result['service_id']);
		}

		return $service_data;
	}
	
	//category manuf
	public function getServiceRelatedByCategory($data) {

		$service_data = array();
				
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_related_wb pr LEFT JOIN " . DB_PREFIX . "service p ON (pr.service_id = p.service_id) LEFT JOIN " . DB_PREFIX . "service_to_store p2s ON (p.service_id = p2s.service_id) WHERE pr.category_id = '" . (int)$data['category_id'] . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' LIMIT " . (int)$data['limit']); 

		foreach ($query->rows as $result) { 
			$service_data[$result['service_id']] = $this->getService($result['service_id']);
		}

		return $service_data;

	}
	
	public function getServiceRelatedByManufacturer($data) {

		$service_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_related_mn pr LEFT JOIN " . DB_PREFIX . "service p ON (pr.service_id = p.service_id) LEFT JOIN " . DB_PREFIX . "service_to_store p2s ON (p.service_id = p2s.service_id) WHERE pr.manufacturer_id = '" . (int)$data['manufacturer_id'] . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' LIMIT " . (int)$data['limit']); 

		foreach ($query->rows as $result) { 
			$service_data[$result['service_id']] = $this->getService($result['service_id']);
		}

		

		return $service_data;

	}
	//category manuf
	
	public function getServiceRelatedProduct($service_id) {
		$product_data = array();
		$this->load->model('catalog/product');
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_related_product np LEFT JOIN " . DB_PREFIX . "product p ON (np.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE np.service_id = '" . (int)$service_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		foreach ($query->rows as $result) { 
			$product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
		}

		return $product_data;
	}
		
	public function getServiceLayoutId($service_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_to_layout WHERE service_id = '" . (int)$service_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return  $this->config->get('config_layout_service');
		}
	}
	
	public function getCategories($service_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_to_service_category WHERE service_id = '" . (int)$service_id . "'");
		
		return $query->rows;
	}

	public function getDownloads($service_id) {

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_to_download pd LEFT JOIN " . DB_PREFIX . "download d ON(pd.download_id=d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON(pd.download_id=dd.download_id) WHERE service_id = '" . (int)$service_id . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id')."'");

		return $query->rows;
	}

	public function getDownload($service_id, $download_id) {
	$download="";
	if($download_id!=0)$download=" AND d.download_id=".(int)$download_id;
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_to_download pd LEFT JOIN " . DB_PREFIX . "download d ON(pd.download_id=d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON(pd.download_id=dd.download_id) WHERE service_id = '" . (int)$service_id . "' ".$download." AND dd.language_id = '" . (int)$this->config->get('config_language_id')."'");

		return $query->row;
	}

	public function getServiceFields($service_id) {
		$field_data = array();

		$query = $this->db->query("SELECT sf.code, sf.type, sfv.value FROM " . DB_PREFIX . "service_field sf LEFT JOIN " . DB_PREFIX . "service_field_value sfv ON (sf.service_field_id = sfv.service_field_id AND sfv.service_id = '" . (int)$service_id . "') WHERE sf.status = '1' ORDER BY sf.sort_order, sf.name");

		foreach ($query->rows as $result) {
			$value = (string)$result['value'];

			if ($result['type'] == 'number' && $value !== '') {
				$field_data[$result['code']] = (float)$value;
			} else {
				$field_data[$result['code']] = $value;
			}
		}

		return $field_data;
	}
		
	public function getTotalServices($data = array()) {
		if ($this->customer->isLogged()) {
			$customer_group_id = $this->customer->getGroupId();
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}	
				
		$cache = md5(http_build_query($data));
		
		$service_data = $this->cache->get('service.total.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache);
		
		$service_data = [];
		
		if (!$service_data) {
			$sql = "SELECT COUNT(DISTINCT p.service_id) AS total FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_description pd ON (p.service_id = pd.service_id) LEFT JOIN " . DB_PREFIX . "service_to_store p2s ON (p.service_id = p2s.service_id)";
	
			if (!empty($data['filter_service_category_id'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "service_to_service_category a2c ON (p.service_id = a2c.service_id)";		
			}
						
			$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
			
			if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
				$sql .= " AND (";
				
				if (!empty($data['filter_name'])) {					
					if (!empty($data['filter_description'])) {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%' OR MATCH(pd.description) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "')";
					} else {
						$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
					}
				}
				
				if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
					$sql .= " OR ";
				}
				
				if (!empty($data['filter_tag'])) {
					$sql .= "MATCH(pd.tag) AGAINST('" . $this->db->escape(utf8_strtolower($data['filter_tag'])) . "')";
				}
			
				$sql .= ")";
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}	
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}

				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}		
				
				if (!empty($data['filter_name'])) {
					$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				}				
			}
						
			if (!empty($data['filter_service_category_id'])) {
				if (!empty($data['filter_sub_service_category'])) {
					$implode_data = array();
					
					$implode_data[] = (int)$data['filter_service_category_id'];
					
					$this->load->model('service/category');
					
					$categories = $this->model_service_category->getCategoriesByParentId($data['filter_category_id']);
										
					foreach ($categories as $service_category_id) {
						$implode_data[] = (int)$service_category_id;
					}
								
					$sql .= " AND a2c.service_category_id IN (" . implode(', ', $implode_data) . ")";		
				} else {
					$sql .= " AND a2c.service_category_id = '" . (int)$data['filter_service_category_id'] . "'";
				}
			}		
			
			$query = $this->db->query($sql);
			
			$service_data = $query->row['total']; 
			
			$this->cache->set('service.total.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . (int)$customer_group_id . '.' . $cache, $service_data);
		}
		
		return $service_data;
	}
		
}
?>
