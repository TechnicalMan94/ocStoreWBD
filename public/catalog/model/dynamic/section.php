<?php
class ModelDynamicSection extends Model {
	public function getSection($section_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_section WHERE section_id = '" . (int)$section_id . "' AND status = '1'");

		if ($query->num_rows) {
			$settings = !empty($query->row['settings']) ? json_decode($query->row['settings'], true) : array();
			return array(
				'section_id' => $query->row['section_id'],
				'code'       => $query->row['code'],
				'name'       => $query->row['name'],
				'settings'   => $settings
			);
		}

		return array();
	}
}
