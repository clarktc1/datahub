<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api_error_log_model extends CI_Model
{
    protected $table = 'mws_new_data_log';

    public function  __construct() {
        parent::__construct();
    }

    public function get_count() {
        return $this->db->count_all($this->table);
    }

    public function get_all_api_error($limit, $start) {
        $this->db->limit($limit, $start);
        $this->db->order_by("id", "desc");
        $query = $this->db->get($this->table);
        return $query->result();
    }
}
?>
