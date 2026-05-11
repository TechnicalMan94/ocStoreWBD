<?php
class ModelServiceField extends Model {
	public function addField($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "service_field SET code = '" . $this->db->escape($this->normalizeCode($data['code'])) . "', name = '" . $this->db->escape($data['name']) . "', type = '" . $this->db->escape($data['type']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "'");

		$this->cache->delete('service');
	}

	public function editField($service_field_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "service_field SET code = '" . $this->db->escape($this->normalizeCode($data['code'])) . "', name = '" . $this->db->escape($data['name']) . "', type = '" . $this->db->escape($data['type']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "' WHERE service_field_id = '" . (int)$service_field_id . "'");

		$this->cache->delete('service');
	}

	public function deleteField($service_field_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_field WHERE service_field_id = '" . (int)$service_field_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_field_value WHERE service_field_id = '" . (int)$service_field_id . "'");

		$this->cache->delete('service');
	}

	public function getField($service_field_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_field WHERE service_field_id = '" . (int)$service_field_id . "'");

		return $query->row;
	}

	public function getFields($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "service_field";

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

	public function getTotalFields() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "service_field");

		return $query->row['total'];
	}

	public function getFieldByCode($code) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_field WHERE code = '" . $this->db->escape($this->normalizeCode($code)) . "'");

		return $query->row;
	}

	public function normalizeCode($code) {
		return preg_replace('/[^a-z0-9_]/', '', utf8_strtolower(trim((string)$code)));
	}
}
