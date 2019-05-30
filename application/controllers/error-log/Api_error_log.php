<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api_error_log extends CI_Controller
{
    public function  __construct()
    {
        parent::__construct();
        $this->load->model('error-log/api_error_log_model');
        $this->load->library("pagination");
    }

    public function index()
    {
        $config = array();
        $config['full_tag_open'] = "<ul class='pagination'>";
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['prev_link'] = '<i class="fa fa-long-arrow-left"></i>Previous Page';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_link'] = 'Next Page<i class="fa fa-long-arrow-right"></i>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';

        $config["base_url"]    = base_url() . "api-error-log";
        $config["total_rows"]  = $this->api_error_log_model->get_count();
        $config["per_page"]    = 10;
        $config["uri_segment"] = 2;

        $this->pagination->initialize($config);
        $page = ($this->uri->segment(2)) ? $this->uri->segment(2) : 0;
        $data["links"]   = $this->pagination->create_links();
        $data['logs'] = $this->api_error_log_model->get_all_api_error($config["per_page"], $page);
        $this->load->view('error-log/index', $data);
    }
}
