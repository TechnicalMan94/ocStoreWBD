<?php
class ModelDynamicField extends Model {
	public function addField($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_field SET section_id = '" . (int)$data['section_id'] . "', code = '" . $this->db->escape($this->normalizeCode($data['code'])) . "', name = '" . $this->db->escape($data['name']) . "', type = '" . $this->db->escape($data['type']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "'");

		$this->cache->delete('dynamic_section');
	}

	public function editField($field_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "dynamic_field SET section_id = '" . (int)$data['section_id'] . "', code = '" . $this->db->escape($this->normalizeCode($data['code'])) . "', name = '" . $this->db->escape($data['name']) . "', type = '" . $this->db->escape($data['type']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "' WHERE field_id = '" . (int)$field_id . "'");

		$this->cache->delete('dynamic_section');
	}

	public function deleteField($field_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_field WHERE field_id = '" . (int)$field_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_field_value WHERE field_id = '" . (int)$field_id . "'");

		$this->cache->delete('dynamic_section');
	}

	public function getField($field_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_field WHERE field_id = '" . (int)$field_id . "'");
		return $query->row;
	}

	public function getFields($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "dynamic_field";

		if (!empty($data['filter_section_id'])) {
			$sql .= " WHERE section_id = '" . (int)$data['filter_section_id'] . "'";
		}

		$sort_data = array('name', 'code', 'type', 'sort_order', 'status');

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
		return $query->rows;
	}

	public function getTotalFields($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "dynamic_field";

		if (!empty($data['filter_section_id'])) {
			$sql .= " WHERE section_id = '" . (int)$data['filter_section_id'] . "'";
		}

		$query = $this->db->query($sql);
		return $query->row['total'];
	}

	public function getFieldByCode($code, $section_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_field WHERE code = '" . $this->db->escape($this->normalizeCode($code)) . "' AND section_id = '" . (int)$section_id . "'");
		return $query->row;
	}

	public function normalizeCode($code) {
		return preg_replace('/[^a-z0-9_]/', '', utf8_strtolower(trim((string)$code)));
	}
}
