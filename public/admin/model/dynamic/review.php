<?php
class ModelDynamicReview extends Model {
	public function addReview($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_review SET page_id = '" . (int)$data['page_id'] . "', customer_id = '" . (int)$data['customer_id'] . "', author = '" . $this->db->escape($data['author']) . "', text = '" . $this->db->escape(strip_tags($data['text'])) . "', rating = '" . (int)$data['rating'] . "', status = '" . (int)$data['status'] . "', date_added = NOW()");

		$this->cache->delete('dynamic_section');
	}

	public function editReview($review_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "dynamic_review SET page_id = '" . (int)$data['page_id'] . "', customer_id = '" . (int)$data['customer_id'] . "', author = '" . $this->db->escape($data['author']) . "', text = '" . $this->db->escape(strip_tags($data['text'])) . "', rating = '" . (int)$data['rating'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE review_id = '" . (int)$review_id . "'");

		$this->cache->delete('dynamic_section');
	}

	public function deleteReview($review_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_review WHERE review_id = '" . (int)$review_id . "'");
		$this->cache->delete('dynamic_section');
	}

	public function getReview($review_id) {
		$query = $this->db->query("SELECT r.*, pd.name AS page_name FROM " . DB_PREFIX . "dynamic_review r LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (r.page_id = pd.page_id) WHERE r.review_id = '" . (int)$review_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		return $query->row;
	}

	public function getReviews($data = array()) {
		$sql = "SELECT r.*, pd.name AS page_name, p.section_id FROM " . DB_PREFIX . "dynamic_review r LEFT JOIN " . DB_PREFIX . "dynamic_page p ON (r.page_id = p.page_id) LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (r.page_id = pd.page_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_section_id'])) {
			$sql .= " AND p.section_id = '" . (int)$data['filter_section_id'] . "'";
		}

		if (!empty($data['filter_page'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_page']) . "%'";
		}

		if (!empty($data['filter_author'])) {
			$sql .= " AND r.author LIKE '" . $this->db->escape($data['filter_author']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(r.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		$sort_data = array('pd.name', 'r.author', 'r.rating', 'r.status', 'r.date_added');

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY r.date_added";
		}

		$sql .= (isset($data['order']) && $data['order'] == 'DESC') ? " DESC" : " ASC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) $data['start'] = 0;
			if ($data['limit'] < 1) $data['limit'] = 20;
			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getTotalReviews($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "dynamic_review r LEFT JOIN " . DB_PREFIX . "dynamic_page p ON (r.page_id = p.page_id) LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (r.page_id = pd.page_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_section_id'])) {
			$sql .= " AND p.section_id = '" . (int)$data['filter_section_id'] . "'";
		}

		if (!empty($data['filter_page'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_page']) . "%'";
		}

		if (!empty($data['filter_author'])) {
			$sql .= " AND r.author LIKE '" . $this->db->escape($data['filter_author']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
		}

		$query = $this->db->query($sql);
		return $query->row['total'];
	}

	public function getTotalReviewsAwaitingApproval() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "dynamic_review WHERE status = '0'");
		return $query->row['total'];
	}
}
