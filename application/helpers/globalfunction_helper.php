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