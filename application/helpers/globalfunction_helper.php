<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');


if (!function_exists('sendEmails')) {
    function sendEmails($emailData) {
        /*$config = Array(
                            'mailtype' => 'html',
                            'wordwrap' => TRUE,
                            'charset' => 'iso-8859-1',
                        );*/

        //SMTP & mail configuration
        $config = array(
                            'protocol'  => 'smtp',
                            'smtp_host' => 'ssl://smtp.googlemail.com',
                            'smtp_port' => 465,
                            'smtp_user' => 'ritika1wayit@gmail.com',
                            'smtp_pass' => 'Ritika@123',
                            'mailtype'  => 'html',
                            'charset'   => 'iso-8859-1'
                        );

        $ci = & get_instance();
        $ci->load->library('email', $config);
        $ci->email->initialize($config);
        $ci->email->set_mailtype("html");
        $ci->email->set_newline("\r\n");
        if (!empty($emailData['from'])) {
            $fromEmail = $emailData['from'];
        } else {
            $fromEmail = "no-reply@tcc.com";
        }

        $ci->email->from($fromEmail, 'Tcc Admin');
        $ci->email->to($emailData['to']);
        if (!empty($emailData['cc'])) {
            $ci->email->cc($emailData['cc']);
        }
        $ci->email->subject($emailData['subject']);
        $ci->email->message($emailData['message']);
        if ($ci->email->send()) {
            return TRUE;
        } else {
            echo show_error($ci->email->print_debugger());die;
            return FALSE;
        }
    }
}

if (!function_exists('checkExits')) {
    function checkExits($table,$where)
    {
        $ci = & get_instance();
        $ci->db->select('*');
        $ci->db->where($where);
        $query = $ci->db->get($table);
        $num = $query->num_rows();
        $result = 0;
        if ($num > 0) {
            $result = 1;
        }
        return $result;
    }
}

if (!function_exists('updatedata')) {
    function updatedata($tbname, $data, $parm)
    {
        $ci = & get_instance();
        return $ci->db->update($tbname, $data, $parm);
    }
}

if (!function_exists('insertdata')) {
    function insertdata($tbname, $data)
    {
        $ci = & get_instance();
        return $ci->db->insert($tbname, $data);
    }
}

if (!function_exists('getTimeZoneDateTime')) {
    function getTimeZoneDateTime($get_posted_date, $marketplaceName)
    {
        date_default_timezone_set('UTC');
        $dateTime = new DateTime ($get_posted_date);
        $dateTime->setTimezone(new DateTimeZone('America/Los_Angeles'));
        $posted_date = $dateTime->format('Y-m-d H:i:s');

        if (trim($marketplaceName)=="Amazon.co.uk" || trim($marketplaceName)=="SI UK Prod Marketplace") {
            $dateTime = new DateTime ($get_posted_date);
            $dateTime->setTimezone(new DateTimeZone('GMT'));
            $posted_date = $dateTime->format('Y-m-d H:i:s');
        } elseif (trim($marketplaceName)=="Amazon.de" || trim($marketplaceName)=="SI DE Prod Marketplace") {
            $dateTime = new DateTime ($get_posted_date);
            $dateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
            $posted_date = $dateTime->format('Y-m-d H:i:s');
        } elseif (trim($marketplaceName)=="Amazon.es" || trim($marketplaceName)=="SI ES Prod Marketplace") {
            $dateTime = new DateTime ($get_posted_date);
            $dateTime->setTimezone(new DateTimeZone('Europe/Madrid'));
            $posted_date = $dateTime->format('Y-m-d H:i:s');
        } elseif (trim($marketplaceName)=="Amazon.fr" || trim($marketplaceName)=="SI FR Prod Marketplace") {
            $dateTime = new DateTime ($get_posted_date);
            $dateTime->setTimezone(new DateTimeZone('Europe/Paris'));
            $posted_date = $dateTime->format('Y-m-d H:i:s');
        } elseif (trim($marketplaceName)=="Amazon.it" || trim($marketplaceName)=="SI It Prod Marketplace") {
            $dateTime = new DateTime ($get_posted_date);
            $dateTime->setTimezone(new DateTimeZone('Europe/Rome'));
            $posted_date = $dateTime->format('Y-m-d H:i:s');
        }
        return $posted_date;
    }
}