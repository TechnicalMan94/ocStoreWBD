<?php
class ModelDynamicSection extends Model {
	public function addSection($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_section SET code = '" . $this->db->escape($data['code']) . "', name = '" . $this->db->escape($data['name']) . "', status = '" . (int)$data['status'] . "', sort_order = '" . (int)$data['sort_order'] . "', settings = '" . $this->db->escape(isset($data['settings']) ? json_encode($data['settings']) : '') . "'");

		$this->cache->delete('dynamic_section');

		return $this->db->getLastId();
	}

	public function editSection($section_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "dynamic_section SET code = '" . $this->db->escape($data['code']) . "', name = '" . $this->db->escape($data['name']) . "', status = '" . (int)$data['status'] . "', sort_order = '" . (int)$data['sort_order'] . "', settings = '" . $this->db->escape(isset($data['settings']) ? json_encode($data['settings']) : '') . "' WHERE section_id = '" . (int)$section_id . "'");

		$this->cache->delete('dynamic_section');
	}

	public function deleteSection($section_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_section WHERE section_id = '" . (int)$section_id . "'");

		$this->cache->delete('dynamic_section');
	}

	public function getSection($section_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_section WHERE section_id = '" . (int)$section_id . "'");

		if ($query->row && $query->row['settings']) {
			$query->row['settings'] = json_decode($query->row['settings'], true);
		}

		return $query->row;
	}

	public function getSectionByCode($code) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_section WHERE code = '" . $this->db->escape($code) . "'");

		if ($query->row && $query->row['settings']) {
			$query->row['settings'] = json_decode($query->row['settings'], true);
		}

		return $query->row;
	}

	public function getSections($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "dynamic_section";

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " WHERE status = '" . (int)$data['filter_status'] . "'";
		}

		$sort_data = array('name', 'code', 'sort_order', 'status');

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY sort_order";
		}

		$sql .= (isset($data['order']) && $data['order'] == 'DESC') ? " DESC" : " ASC";

		if (isset($data['start']) || isset($data['limit'])) {
			$start = max(0, (int)$data['start']);
			$limit = max(1, (int)$data['limit']);
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);

		foreach ($query->rows as &$row) {
			if ($row['settings']) {
				$row['settings'] = json_decode($row['settings'], true);
			}
		}

		return $query->rows;
	}

	public function getTotalSections() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "dynamic_section");
		return $query->row['total'];
	}
}
