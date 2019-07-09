<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Finance_api extends CI_Controller
{
    private $startDate;
    public function  __construct()
    {
        parent::__construct();
        $this->load->model('new_cron/process_finance_api','product_api');
    }

    public function checkExits($table,$where)
    {
        $this->db->select('*');
        $this->db->where($where);
        $query = $this->db->get($table);
        $num = $query->num_rows();
        $result = 0;
        if ($num > 0) {
            $result = 1;
        }
        return $result;
    }

    public function updatedata($tbname, $data, $parm)
    {
        return $this->db->update($tbname, $data, $parm);
    }

    public function insertdata($tbname, $data)
    {
        return $this->db->insert($tbname, $data);
    }

    public function product_match($user_id='')
    {
        // die("hello dsfsdfds dsfds");
        $users=$this->product_api->get_seller_for_process();
        // echo "<pre>";
        // print_r($users);
        // die();
        $checkExitsFinanceDataApiArray = array(
                                                'save_data'  => 'n'
                                              );
        $limitApi = "1";
        $checkExits = checkExitData('finance_data_api', $checkExitsFinanceDataApiArray, $limitApi);
        if (empty($checkExits)) {
            if(count($users) > 0)
            {
                foreach($users as $usr)
                {
                    $this->product_api->set_credentials($usr);
                    $prod_list=$this->product_api->get_product_to_match($usr['profile_id'],$usr['country_code']);
                    $res = $this->product_api->fetch_product_details($usr['profile_id'],null,null,$usr['country_code']);
                    if ($res['status_code']==0) {
                        if (isset($res['mwsNewDataLog']) && !empty($res['mwsNewDataLog'])) {
                            foreach ($res['mwsNewDataLog'] as $mwsNewDataLog) {
                                insertdata('mws_new_data_log',$mwsNewDataLog);
                            }
                        }
                    }
                    // echo "<pre>"; print_r($res);
                    // die;
                    // $this->saveFinanceData($res, $usr);
                    // $responseArray = array();
                    // $responseArray['api'] = "Finances / ListFinancialEvent";
                    // $responseArray['status'] = "Successfully Fetched";
                    // echo "<prE>"; print_r($responseArray); die();
                }
            }
        }
    }

    public function saveFinanceDataByDatabase()
    {
        $this->finance_order_data_summary();
        $this->finance_data_api_delete_data();
        $get_finance_api_data_match_key = array('save_data' => 'n');
        $totalRecord = 5;
        $get_finance_api_data = checkExitData('finance_data_api', $get_finance_api_data_match_key, $totalRecord);
        // $get_finance_api_data = checkExitData('finance_data_api', $get_finance_api_data_match_key);
        if (!empty($get_finance_api_data)) {
            foreach ($get_finance_api_data as $get_finance_api) {
                $resDataArray = $this->product_api->fetch_product_details_merge_data($get_finance_api,$get_finance_api['user_id']);
                $this->saveFinanceDataByDatabaseTable($resDataArray,$get_finance_api);
            }
        }
        $this->rerun_finace_empty();
        // $this->finance_data_api_delete_data();
        // $this->finance_order_data_summary();
        // send_error_mail();
    }

    public function saveFinanceDataByDatabaseTable($res,$usr)
    {
        if (!empty($res['payload']))
        {
            // echo "<pre>";
            // print_r($res);
            // echo "</pre>";
            // die();
            $apiLastDate     = $usr['end_date'];
            $apiStartDate    = $usr['start_date'];
            $user_profile_id = $usr['user_id'];
            $country_code    = $usr['fin_country'];
            $start_date      = $usr['start_date'];
            $end_date        = $usr['end_date'];
            $apiTableId      = $usr['id'];
            $date_now = date('Y-m-d');
            $apiGetDateToData = $start_date." to ".$end_date;

            if ($res['payload']['mwsNewDataLog'] && !empty($res['payload']['mwsNewDataLog'])) {
                foreach ($res['payload']['mwsNewDataLog'] as $mwsNewDataLog) {
                    $this->insertdata('mws_new_data_log',$mwsNewDataLog);
                    $this->updatedata('finance_data_log',['date' => $apiStartDate ], ['user_id' => $user_profile_id ] );
                    updatedata('finance_data_api',['save_data' => 'y' ], ['id' => $apiTableId ] );
                }
            }


            if ($res['payload']['AdjustmentEventList'] && !empty($res['payload']['AdjustmentEventList'])) {
                $fael_bulk_query_data = array();
                foreach ($res['payload']['AdjustmentEventList'] as $adjustmentEventListValue) {
                    $fael_perunitamount              = (isset($adjustmentEventListValue['perunitamount']) && ""!=trim($adjustmentEventListValue['perunitamount'])) ? $this->db->escape($adjustmentEventListValue['perunitamount']) : $this->db->escape('0.00');
                    $fael_totalamount                = (isset($adjustmentEventListValue['totalamount']) && ""!=trim($adjustmentEventListValue['totalamount'])) ? $this->db->escape($adjustmentEventListValue['totalamount']) : $this->db->escape('0.00');
                    $fael_quantity                   = (isset($adjustmentEventListValue['quantity']) && ""!=trim($adjustmentEventListValue['quantity'])) ? $this->db->escape($adjustmentEventListValue['quantity']) : $this->db->escape('');
                    $fael_sellersku                  = (isset($adjustmentEventListValue['sellersku']) && ""!=trim($adjustmentEventListValue['sellersku'])) ? $this->db->escape($adjustmentEventListValue['sellersku']) : $this->db->escape('');
                    $fael_productdescription         = (isset($adjustmentEventListValue['productdescription']) && ""!=trim($adjustmentEventListValue['productdescription'])) ? $this->db->escape($adjustmentEventListValue['productdescription']) : $this->db->escape('');
                    $fael_adjustmentamount           = (isset($adjustmentEventListValue['adjustmentamount']) && ""!=trim($adjustmentEventListValue['adjustmentamount'])) ? $this->db->escape($adjustmentEventListValue['adjustmentamount']) : $this->db->escape('0.00');
                    $fael_adjustmenttype             = (isset($adjustmentEventListValue['adjustmenttype']) && ""!=trim($adjustmentEventListValue['adjustmenttype'])) ? $this->db->escape($adjustmentEventListValue['adjustmenttype']) : $this->db->escape('');
                    $fael_posteddate                 = (isset($adjustmentEventListValue['posteddate']) && ""!=trim($adjustmentEventListValue['posteddate'])) ? $this->db->escape($adjustmentEventListValue['posteddate']) : $this->db->escape('');
                    $fael_added_by                   = $this->db->escape($user_profile_id);
                    $fael_fin_country                = $this->db->escape($country_code);
                    $fael_currency                   = (isset($adjustmentEventListValue['currency']) && ""!=trim($adjustmentEventListValue['currency'])) ? $this->db->escape($adjustmentEventListValue['currency']) : $this->db->escape('');
                    $fael_dev_date                   = (isset($adjustmentEventListValue['dev_date']) && ""!=trim($adjustmentEventListValue['dev_date'])) ? $this->db->escape($adjustmentEventListValue['dev_date']) : $this->db->escape('');
                    $fael_createDate                 = $this->db->escape(date('Y-m-d H:i:s'));
                    $fael_updateDate                 = $this->db->escape(date('Y-m-d H:i:s'));
                    $fael_finance_order_data_summary = $this->db->escape('n');
                    $fael_bulk_query_data[] = "({$fael_perunitamount},{$fael_totalamount},{$fael_quantity},{$fael_sellersku},{$fael_productdescription},{$fael_adjustmentamount},{$fael_adjustmenttype},{$fael_posteddate},{$fael_added_by},{$fael_fin_country},{$fael_currency},{$fael_dev_date},{$fael_createDate},{$fael_updateDate},{$fael_finance_order_data_summary})";
                }

                if (!empty($fael_bulk_query_data)) {
                    $fael_bulk_query_data_implode = implode(',',$fael_bulk_query_data);
                    $fael_sql_query = "INSERT INTO `finance_adjustment_event_list` (`perunitamount`, `totalamount`, `quantity`, `sellersku`, `productdescription`, `adjustmentamount`, `adjustmenttype`, `posteddate`, `added_by`, `fin_country`, `currency`, `dev_date`, `createDate`, `updateDate`, `finance_order_data_summary`)
                                       VALUES
                                       $fael_bulk_query_data_implode
                                       ON DUPLICATE KEY
                                       UPDATE
                                       perunitamount=VALUES(perunitamount), totalamount=VALUES(totalamount), quantity=VALUES(quantity), sellersku=VALUES(sellersku), productdescription=VALUES(productdescription), adjustmentamount=VALUES(adjustmentamount), adjustmenttype=VALUES(adjustmenttype), posteddate=VALUES(posteddate), added_by=VALUES(added_by), fin_country=VALUES(fin_country), currency=VALUES(currency), dev_date=VALUES(dev_date), updateDate=VALUES(updateDate), finance_order_data_summary=VALUES(finance_order_data_summary)";
                    $check_fael_sql_query = $this->db->query($fael_sql_query);
                    if (!$check_fael_sql_query) {
                        $saveErrorData = array();
                        $saveErrorData['error'] = $this->db->error();
                        $saveErrorData['error_dates'] = $apiGetDateToData;
                        $mwsNewDataLogEmpty = array();
                        $mwsNewDataLogEmpty['table_name'] = "Error Found finance_adjustment_event_list Query";
                        $mwsNewDataLogEmpty['user_id']    = $user_profile_id;
                        $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                        $mwsNewDataLogEmpty['api_date']   = $apiLastDate;
                        $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                    }
                }
            }

            // die('AdjustmentEventList');

            if ($res['payload']['RefundEventList'] && !empty($res['payload']['RefundEventList'])) {
                $frel_bulk_query_data = array();
                foreach ($res['payload']['RefundEventList'] as $refundEventListValue) {
                    $frel_amazonorderid                      = (isset($refundEventListValue['amazonorderid']) && ""!=trim($refundEventListValue['amazonorderid'])) ? $this->db->escape($refundEventListValue['amazonorderid']) : $this->db->escape('');
                    $frel_posteddate                         = (isset($refundEventListValue['posteddate']) && ""!=trim($refundEventListValue['posteddate'])) ? $this->db->escape($refundEventListValue['posteddate']) : $this->db->escape('');
                    $frel_marketplacename                    = (isset($refundEventListValue['marketplacename']) && ""!=trim($refundEventListValue['marketplacename'])) ? $this->db->escape($refundEventListValue['marketplacename']) : $this->db->escape('');
                    $frel_sellerorderid                      = (isset($refundEventListValue['sellerorderid']) && ""!=trim($refundEventListValue['sellerorderid'])) ? $this->db->escape($refundEventListValue['sellerorderid']) : $this->db->escape('');
                    $frel_orderadjustmentitemid              = (isset($refundEventListValue['orderadjustmentitemid']) && ""!=trim($refundEventListValue['orderadjustmentitemid'])) ? $this->db->escape($refundEventListValue['orderadjustmentitemid']) : $this->db->escape('');
                    $frel_quantityshipped                    = (isset($refundEventListValue['quantityshipped']) && ""!=trim($refundEventListValue['quantityshipped'])) ? $this->db->escape($refundEventListValue['quantityshipped']) : $this->db->escape('');
                    $frel_sellersku                          = (isset($refundEventListValue['sellersku']) && ""!=trim($refundEventListValue['sellersku'])) ? $this->db->escape($refundEventListValue['sellersku']) : $this->db->escape('');
                    $frel_commission                         = (isset($refundEventListValue['commission']) && ""!=trim($refundEventListValue['commission'])) ? $this->db->escape($refundEventListValue['commission']) : $this->db->escape('0.00');
                    $frel_refundcommission                   = (isset($refundEventListValue['refundcommission']) && ""!=trim($refundEventListValue['refundcommission'])) ? $this->db->escape($refundEventListValue['refundcommission']) : $this->db->escape('0.00');
                    $frel_tax                                = (isset($refundEventListValue['tax']) && ""!=trim($refundEventListValue['tax'])) ? $this->db->escape($refundEventListValue['tax']) : $this->db->escape('0.00');
                    $frel_marketplacefacilitatortaxprincipal = (isset($refundEventListValue['marketplacefacilitatortaxprincipal']) && ""!=trim($refundEventListValue['marketplacefacilitatortaxprincipal'])) ? $this->db->escape($refundEventListValue['marketplacefacilitatortaxprincipal']) : $this->db->escape('0.00');
                    $frel_marketplacefacilitatortaxshipping  = (isset($refundEventListValue['marketplacefacilitatortaxshipping']) && ""!=trim($refundEventListValue['marketplacefacilitatortaxshipping'])) ? $this->db->escape($refundEventListValue['marketplacefacilitatortaxshipping']) : $this->db->escape('0.00');
                    $frel_principal                          = (isset($refundEventListValue['principal']) && ""!=trim($refundEventListValue['principal'])) ? $this->db->escape($refundEventListValue['principal']) : $this->db->escape('0.00');
                    $frel_shippingtax                        = (isset($refundEventListValue['shippingtax']) && ""!=trim($refundEventListValue['shippingtax'])) ? $this->db->escape($refundEventListValue['shippingtax']) : $this->db->escape('0.00');
                    $frel_shippingcharge                     = (isset($refundEventListValue['shippingcharge']) && ""!=trim($refundEventListValue['shippingcharge'])) ? $this->db->escape($refundEventListValue['shippingcharge']) : $this->db->escape('0.00');
                    $frel_promotionmetadatadefinitionvalue   = (isset($refundEventListValue['promotionmetadatadefinitionvalue']) && ""!=trim($refundEventListValue['promotionmetadatadefinitionvalue'])) ? $this->db->escape($refundEventListValue['promotionmetadatadefinitionvalue']) : $this->db->escape('0.00');
                    $frel_promotionid                        = (isset($refundEventListValue['promotionid']) && ""!=trim($refundEventListValue['promotionid'])) ? $this->db->escape($refundEventListValue['promotionid']) : $this->db->escape('');
                    $frel_currency                           = (isset($refundEventListValue['currency']) && ""!=trim($refundEventListValue['currency'])) ? $this->db->escape($refundEventListValue['currency']) : $this->db->escape('');
                    $frel_added_by                           = $this->db->escape($user_profile_id);
                    $frel_fin_country                        = $this->db->escape($country_code);
                    $frel_dev_date                           = (isset($refundEventListValue['dev_date']) && ""!=trim($refundEventListValue['dev_date'])) ? $this->db->escape($refundEventListValue['dev_date']) : $this->db->escape('');
                    $frel_createDate                         = $this->db->escape(date('Y-m-d H:i:s'));
                    $frel_updateDate                         = $this->db->escape(date('Y-m-d H:i:s'));
                    $frel_finance_order_data_summary         = $this->db->escape('n');
                    $frel_bulk_query_data[] = "({$frel_amazonorderid},{$frel_posteddate},{$frel_marketplacename},{$frel_sellerorderid},{$frel_orderadjustmentitemid},{$frel_quantityshipped},{$frel_sellersku},{$frel_commission},{$frel_refundcommission},{$frel_tax},{$frel_marketplacefacilitatortaxprincipal},{$frel_marketplacefacilitatortaxshipping},{$frel_principal},{$frel_shippingtax},{$frel_shippingcharge},{$frel_promotionmetadatadefinitionvalue},{$frel_promotionid},{$frel_currency},{$frel_added_by},{$frel_fin_country},{$frel_dev_date},{$frel_createDate},{$frel_updateDate},{$frel_finance_order_data_summary})";
                }
                if (!empty($frel_bulk_query_data)) {
                    $frel_bulk_query_data_implode = implode(',',$frel_bulk_query_data);
                    $frel_sql_query = "INSERT INTO `finance_refund_event_list` (`amazonorderid`, `posteddate`, `marketplacename`, `sellerorderid`, `orderadjustmentitemid`, `quantityshipped`, `sellersku`, `commission`, `refundcommission`, `tax`, `marketplacefacilitatortaxprincipal`, `marketplacefacilitatortaxshipping`, `principal`, `shippingtax`, `shippingcharge`, `promotionmetadatadefinitionvalue`, `promotionid`, `currency`, `added_by`, `fin_country`, `dev_date`, `createDate`, `updateDate`, `finance_order_data_summary`)
                                       VALUES
                                       $frel_bulk_query_data_implode
                                       ON DUPLICATE KEY
                                       UPDATE
                                       amazonorderid=VALUES(amazonorderid), posteddate=VALUES(posteddate), marketplacename=VALUES(marketplacename), sellerorderid=VALUES(sellerorderid), orderadjustmentitemid=VALUES(orderadjustmentitemid), quantityshipped=VALUES(quantityshipped), sellersku=VALUES(sellersku), commission=VALUES(commission), refundcommission=VALUES(refundcommission), tax=VALUES(tax), marketplacefacilitatortaxprincipal=VALUES(marketplacefacilitatortaxprincipal), marketplacefacilitatortaxshipping=VALUES(marketplacefacilitatortaxshipping), principal=VALUES(principal), shippingtax=VALUES(shippingtax), shippingcharge=VALUES(shippingcharge), promotionmetadatadefinitionvalue=VALUES(promotionmetadatadefinitionvalue), promotionid=VALUES(promotionid), currency=VALUES(currency), added_by=VALUES(added_by), fin_country=VALUES(fin_country), dev_date=VALUES(dev_date), updateDate=VALUES(updateDate), finance_order_data_summary=VALUES(finance_order_data_summary)";
                    $check_frel_sql_query = $this->db->query($frel_sql_query);
                    if (!$check_frel_sql_query) {
                        $saveErrorData = array();
                        $saveErrorData['error'] = $this->db->error();
                        $saveErrorData['error_dates'] = $apiGetDateToData;
                        $mwsNewDataLogEmpty = array();
                        $mwsNewDataLogEmpty['table_name'] = "Error Found finance_refund_event_list Query";
                        $mwsNewDataLogEmpty['user_id']    = $user_profile_id;
                        $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                        $mwsNewDataLogEmpty['api_date']   = $apiLastDate;
                        $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                    }
                }
            }

            // die("RefundEventList");

            if ($res['payload']['ServiceFeeEventList'] && !empty($res['payload']['ServiceFeeEventList'])) {
                $fsfel_bulk_query_data = array();
                foreach ($res['payload']['ServiceFeeEventList'] as $serviceFeeEventListValue) {
                    $fsfel_fee_amount  = (""!=trim($serviceFeeEventListValue['fee_amount'])) ? $this->db->escape($serviceFeeEventListValue['fee_amount']) : $this->db->escape('');
                    $fsfel_fee_type    = (""!=trim($serviceFeeEventListValue['fee_type'])) ? $this->db->escape($serviceFeeEventListValue['fee_type']) : $this->db->escape('');
                    $fsfel_currency    = (""!=trim($serviceFeeEventListValue['currency'])) ? $this->db->escape($serviceFeeEventListValue['currency']) : $this->db->escape('');
                 	$fsfel_added_by    = $this->db->escape($user_profile_id);
                    $fsfel_fin_country = $this->db->escape($country_code);
                    $fsfel_ref_date    = $this->db->escape($apiStartDate);
                    $fsfel_createDate  = $this->db->escape(date('Y-m-d H:i:s'));
                    $fsfel_updateDate  = $this->db->escape(date('Y-m-d H:i:s'));
                    $fsfel_finance_order_data_summary = $this->db->escape('n');
                    $fsfel_bulk_query_data[] = "({$fsfel_fee_amount},{$fsfel_fee_type},{$fsfel_currency},{$fsfel_added_by},{$fsfel_fin_country},{$fsfel_ref_date},{$fsfel_createDate},{$fsfel_updateDate},{$fsfel_finance_order_data_summary})";
                }
                if (!empty($fsfel_bulk_query_data)) {
                    $fsfel_bulk_query_data_implode = implode(',',$fsfel_bulk_query_data);
                    $fsfel_sql_query = "INSERT INTO `finance_service_fee_event_list` (`fee_amount`, `fee_type`, `currency`, `added_by`, `fin_country`, `ref_date`, `createDate`, `updateDate`, `finance_order_data_summary`)
                                        VALUES
                                        $fsfel_bulk_query_data_implode
                                        ON DUPLICATE KEY
                                        UPDATE
                                        fee_amount=VALUES(fee_amount), fee_type=VALUES(fee_type), currency=VALUES(currency), added_by=VALUES(added_by), fin_country=VALUES(fin_country), ref_date=VALUES(ref_date), updateDate=VALUES(updateDate), finance_order_data_summary=VALUES(finance_order_data_summary)";
                    $check_fsfel_sql_query = $this->db->query($fsfel_sql_query);
                    if (!$check_fsfel_sql_query) {
                        $saveErrorData = array();
                        $saveErrorData['error'] = $this->db->error();
                        $saveErrorData['error_dates'] = $apiGetDateToData;
                        $mwsNewDataLogEmpty = array();
                        $mwsNewDataLogEmpty['table_name'] = "Error Found finance_service_fee_event_list Query";
                        $mwsNewDataLogEmpty['user_id']    = $user_profile_id;
                        $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                        $mwsNewDataLogEmpty['api_date']   = $apiLastDate;
                        $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                    }
                }
            }

            // die("ServiceFeeEventList");

            $checkGetErrorLog = $res['payload']['mwsNewDataLog'];
            unset($res['payload']['mwsNewDataLog']);
            unset($res['payload']['ServiceFeeEventList']);
            unset($res['payload']['RefundEventList']);
            unset($res['payload']['AdjustmentEventList']);

            if (isset($res['payload'][0]) && !empty($res['payload'][0])) {
                $finance_order_item_fee_list_data_array           = array();
                $finance_order_item_charge_list_data_array        = array();
                $finance_order_item_promotion_list_data_array     = array();
                $finance_order_item_tax_withheld_list_data_array  = array();
                $finance_order_item_data_array                    = array();
                $finance_order_shipment_fee_list_array            = array();
                $delete_amazon_order_id = array();
                $delete_dev_date        = array();
                $finance_order_shipment_fee_list_delete_amazon_order_id = array();
                $finance_order_shipment_fee_list_delete_dev_date        = array();
                $bulk_query_data = array();
                foreach ($res['payload'] as $key => $value) {
                    if ( isset($value['amazon_order_id']) && trim($value['amazon_order_id']) !='' ) {
                        $amazon_order_id = $value['amazon_order_id'];
                        $delete_amazon_order_id[] = $amazon_order_id;
                        $delete_dev_date[]        = $value['dev_date'];
                        $finance_order_shipment_fee_list_delete_amazon_order_id[] = $amazon_order_id;
                        $finance_order_shipment_fee_list_delete_dev_date[]        = $value['dev_date'];
                        if (isset($value['shipmentItemList']) && !empty($value['shipmentItemList'])) {
                            foreach ($value['shipmentItemList'] as $shipmentItemList) {
                                if (isset($shipmentItemList['ItemFeeList']) && !empty($shipmentItemList['ItemFeeList'])) {
                                    $finance_order_item_fee_list_data_array = array_merge($finance_order_item_fee_list_data_array,$shipmentItemList['ItemFeeList']) ;
                                }
                                if (isset($shipmentItemList['ItemChargeList']) && !empty($shipmentItemList['ItemChargeList'])) {
                                    $finance_order_item_charge_list_data_array = array_merge($finance_order_item_charge_list_data_array, $shipmentItemList['ItemChargeList']);
                                }
                                if (isset($shipmentItemList['PromotionList']) && !empty($shipmentItemList['PromotionList'])) {
                                    $finance_order_item_promotion_list_data_array = array_merge($finance_order_item_promotion_list_data_array, $shipmentItemList['PromotionList']);
                                }
                                if (isset($shipmentItemList['ItemTaxWithheldList']) && !empty($shipmentItemList['ItemTaxWithheldList'])) {
                                    $finance_order_item_tax_withheld_list_data_array = array_merge($finance_order_item_tax_withheld_list_data_array,$shipmentItemList['ItemTaxWithheldList']);
                                }
                                unset($shipmentItemList['ItemFeeList']);
                                unset($shipmentItemList['ItemChargeList']);
                                unset($shipmentItemList['PromotionList']);
                                unset($shipmentItemList['ItemTaxWithheldList']);
                                $shipmentItemList['fin_country'] = $country_code;
                                $shipmentItemList['added_by']    = $user_profile_id;
                                $finance_order_item_data_array[] = $shipmentItemList;
                                if (!empty($finance_order_item_data_array) && count($finance_order_item_data_array)>=15) {
                                    if (!empty($delete_amazon_order_id) && !empty($delete_dev_date)) {
                                        $delete_tables_names = array(
                                            'finance_order_item_fee_list_data',
                                            'finance_order_item_charge_list_data',
                                            'finance_order_item_promotion_list_data',
                                            'finance_order_item_tax_withheld_list_data',
                                            'finance_order_item_data'
                                        );
                                        $this->db->where_in('amazon_order_id', $delete_amazon_order_id);
                                        $this->db->where_in('dev_date', $delete_dev_date);
                                        $this->db->delete($delete_tables_names);
                                    }

                                    if (!empty($finance_order_item_fee_list_data_array)) {
                                        $this->db->insert_batch('finance_order_item_fee_list_data', $finance_order_item_fee_list_data_array);
                                    }
                                    if (!empty($finance_order_item_charge_list_data_array)) {
                                        $this->db->insert_batch('finance_order_item_charge_list_data', $finance_order_item_charge_list_data_array);
                                    }
                                    if (!empty($finance_order_item_promotion_list_data_array)) {
                                        $this->db->insert_batch('finance_order_item_promotion_list_data', $finance_order_item_promotion_list_data_array);
                                    }
                                    if (!empty($finance_order_item_tax_withheld_list_data_array)) {
                                        $this->db->insert_batch('finance_order_item_tax_withheld_list_data', $finance_order_item_tax_withheld_list_data_array);
                                    }
                                    if (!empty($finance_order_item_data_array)) {
                                        $this->db->insert_batch('finance_order_item_data',$finance_order_item_data_array);
                                    }
                                    $delete_amazon_order_id = array();
                                    $delete_dev_date        = array();
                                    $finance_order_item_fee_list_data_array           = array();
                                    $finance_order_item_charge_list_data_array        = array();
                                    $finance_order_item_promotion_list_data_array     = array();
                                    $finance_order_item_tax_withheld_list_data_array  = array();
                                    $finance_order_item_data_array                    = array();
                                }
                            }
                        }

                        if (isset($value['ShipmentFeeList']) && !empty($value['ShipmentFeeList'])) {
                            $finance_order_shipment_fee_list_array = array_merge($finance_order_shipment_fee_list_array, $value['ShipmentFeeList']);
                        }
                        unset($value['ShipmentFeeList']);
                        unset($value['shipmentItemList']);
                        $fod_amazon_order_id = (""!=trim($value['amazon_order_id'])) ? $this->db->escape($value['amazon_order_id']) : $this->db->escape('');
                        $fod_posted_date     = (""!=trim($value['posted_date'])) ? $this->db->escape($value['posted_date']) : $this->db->escape('');
                        $fod_posted_date_gmt = (""!=trim($value['posted_date_gmt'])) ? $this->db->escape($value['posted_date_gmt']) : $this->db->escape('');
                        $fod_posted_date_pst = (""!=trim($value['posted_date_pst'])) ? $this->db->escape($value['posted_date_pst']) : $this->db->escape('');
                        $fod_market_place    = (""!=trim($value['market_place'])) ? $this->db->escape($value['market_place']) : $this->db->escape('');
                        $fod_seller_order_id = (""!=trim($value['seller_order_id'])) ? $this->db->escape($value['seller_order_id']) : $this->db->escape('');
                        $fod_dev_ref         = (""!=trim($value['dev_ref'])) ? $this->db->escape($value['dev_ref']) : $this->db->escape('');
                        $fod_dev_date        = (""!=trim($value['dev_date'])) ? $this->db->escape($value['dev_date']) : $this->db->escape('');
                        $fod_fin_country     = $this->db->escape($country_code);
                        $fod_added_by        = $this->db->escape($user_profile_id);

                        $bulk_query_data[] = "({$fod_amazon_order_id},{$fod_market_place},{$fod_seller_order_id},{$fod_posted_date},{$fod_posted_date_gmt},{$fod_posted_date_pst},{$fod_added_by},{$fod_fin_country},{$fod_dev_ref},{$fod_dev_date},'n')";

                    } else {
                        $this->updatedata('finance_data_log',['date' => $apiLastDate ], ['user_id' => $user_profile_id ] );
                        updatedata('finance_data_api',['save_data' => 'y' ], ['id' => $apiTableId ] );
                    }
                }

                if (!empty($finance_order_shipment_fee_list_array)) {
                    if (!empty($finance_order_shipment_fee_list_delete_amazon_order_id) && !empty($finance_order_shipment_fee_list_delete_dev_date)) {
                        $delete_tables_names = array(
                            'finance_order_shipment_fee_list'
                        );
                        $this->db->where_in('amazon_order_id', $finance_order_shipment_fee_list_delete_amazon_order_id);
                        $this->db->where_in('dev_date', $finance_order_shipment_fee_list_delete_dev_date);
                        $this->db->delete($delete_tables_names);
                    }
                    $this->db->insert_batch('finance_order_shipment_fee_list', $finance_order_shipment_fee_list_array);
                }

                if (!empty($finance_order_item_data_array)) {
                    if (!empty($delete_amazon_order_id) && !empty($delete_dev_date)) {
                        $delete_tables_names = array(
                                                        'finance_order_item_fee_list_data',
                                                        'finance_order_item_charge_list_data',
                                                        'finance_order_item_promotion_list_data',
                                                        'finance_order_item_tax_withheld_list_data',
                                                        'finance_order_item_data'
                                                    );
                        $this->db->where_in('amazon_order_id', $delete_amazon_order_id);
                        $this->db->where_in('dev_date', $delete_dev_date);
                        $this->db->delete($delete_tables_names);
                    }
                    if (!empty($finance_order_item_fee_list_data_array)) {
                        $this->db->insert_batch('finance_order_item_fee_list_data', $finance_order_item_fee_list_data_array);
                    }
                    if (!empty($finance_order_item_charge_list_data_array)) {
                        $this->db->insert_batch('finance_order_item_charge_list_data', $finance_order_item_charge_list_data_array);
                    }
                    if (!empty($finance_order_item_promotion_list_data_array)) {
                        $this->db->insert_batch('finance_order_item_promotion_list_data', $finance_order_item_promotion_list_data_array);
                    }
                    if (!empty($finance_order_item_tax_withheld_list_data_array)) {
                        $this->db->insert_batch('finance_order_item_tax_withheld_list_data', $finance_order_item_tax_withheld_list_data_array);
                    }
                    if (!empty($finance_order_item_data_array)) {
                        $this->db->insert_batch('finance_order_item_data',$finance_order_item_data_array);
                    }
                    $delete_amazon_order_id = array();
                    $delete_dev_date        = array();
                    $finance_order_item_fee_list_data_array           = array();
                    $finance_order_item_charge_list_data_array        = array();
                    $finance_order_item_promotion_list_data_array     = array();
                    $finance_order_item_tax_withheld_list_data_array  = array();
                    $finance_order_item_data_array                    = array();
                }

                if (!empty($bulk_query_data)) {
                    $bulk_query_data_implode = implode(',',$bulk_query_data);
                    $fod_sql_query = "INSERT INTO `finance_order_data` (`amazon_order_id`, `market_place`, `seller_order_id`, `posted_date`, `posted_date_gmt`, `posted_date_pst`, `added_by`, `fin_country`, `dev_ref`, `dev_date`, `finance_order_data_summary`)
                                     VALUES
                                     $bulk_query_data_implode
                                     ON DUPLICATE KEY
                                     UPDATE
                                     amazon_order_id=VALUES(amazon_order_id), market_place=VALUES(market_place), seller_order_id=VALUES(seller_order_id), posted_date=VALUES(posted_date), posted_date_gmt=VALUES(posted_date_gmt), posted_date_pst=VALUES(posted_date_pst), added_by=VALUES(added_by), fin_country=VALUES(fin_country), dev_ref=VALUES(dev_ref), dev_date=VALUES(dev_date), finance_order_data_summary=VALUES(finance_order_data_summary)";

                    $check_fod_sql_query = $this->db->query($fod_sql_query);
                    if (!$check_fod_sql_query) {
                        $saveErrorData = array();
                        $saveErrorData['error'] = $this->db->error();
                        $saveErrorData['error_dates'] = $apiGetDateToData;
                        $mwsNewDataLogEmpty = array();
                        $mwsNewDataLogEmpty['table_name'] = "Error Found finance_order_data Query";
                        $mwsNewDataLogEmpty['user_id']    = $user_profile_id;
                        $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                        $mwsNewDataLogEmpty['api_date']   = $apiLastDate;
                        $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                    }
                }

                // die("Finances Data");

            } else {
                if (isset($res['payload']) && empty($res['payload'][0])) {
                    if (empty($checkGetErrorLog)) {
                        $mwsNewDataLogEmpty = array();
                        $mwsNewDataLogEmpty['table_name'] = "No Data Found finance_order_data";
                        $mwsNewDataLogEmpty['user_id']    = $user_profile_id;
                        $mwsNewDataLogEmpty['data']       = $apiGetDateToData;
                        $mwsNewDataLogEmpty['api_date']   = $apiLastDate;
                        $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                        $this->updatedata('finance_data_log',['date' => $apiLastDate ], ['user_id' => $user_profile_id ] );
                        updatedata('finance_data_api',['save_data' => 'y' ], ['id' => $apiTableId ] );
                    }
                }
            }
        }
    }


    public function product_match_sb_bk($user_id='')
    {
        // die("hello dsfsdfds dsfds");
        $users=$this->product_api->get_seller_for_process();
        // echo "<pre>";
        // print_r($users);
        // die();
        if(count($users) > 0)
        {
            foreach($users as $usr)
            {
                $this->product_api->set_credentials($usr);
                $prod_list=$this->product_api->get_product_to_match($usr['profile_id'],$usr['country_code']);
                $res=$this->product_api->fetch_product_details($usr['profile_id']);
                // echo "<pre>"; print_r($res);
                // die;
                $this->saveFinanceData($res, $usr);
                $responseArray = array();
                $responseArray['api'] = "Finances / ListFinancialEvent";
                $responseArray['status'] = "Successfully Fetched";
                // echo "<prE>"; print_r($responseArray); die();
            }
        }
        $this->rerun_finace_empty();
        $this->finance_order_data_summary();
        send_error_mail();
    }

    public function saveFinanceData_sb_bk($res,$usr)
    {
        // echo "<pre>"; print_r($res);
        // die();
        if (isset($res['startDate'])) {
            $this->startDate = $res['startDate'];
        }
        if (!empty($res['payload']))
        {
            $date_now = date('Y-m-d');
            $insert   = [];
            if ($res['payload']['mwsNewDataLog'] && !empty($res['payload']['mwsNewDataLog'])) {
                foreach ($res['payload']['mwsNewDataLog'] as $mwsNewDataLog) {
                    $this->insertdata('mws_new_data_log',$mwsNewDataLog);
                }
            }
            if ($res['payload']['AdjustmentEventList'] && !empty($res['payload']['AdjustmentEventList'])) {
                foreach ($res['payload']['AdjustmentEventList'] as $adjustmentEventListValue) {
                    $adjustmentEventListValue['fin_country'] = $usr['country_code'];
                    $adjustmentEventListValue['added_by']    = $usr['profile_id'];
                    $checkExitsAdjustmentEventArray = array(
                                                                'totalamount' => $adjustmentEventListValue['totalamount'],
                                                                'adjustmenttype' => $adjustmentEventListValue['adjustmenttype'],
                                                                'sellersku' => $adjustmentEventListValue['sellersku'],
                                                                'dev_date'  => $adjustmentEventListValue['dev_date'],
                                                                'added_by'  => $usr['profile_id']
                                                            );
                    $checkExits = $this->checkExits('finance_adjustment_event_list', $checkExitsAdjustmentEventArray);
                    if ($checkExits > 0 ) {
                        $this->updatedata('finance_adjustment_event_list', $adjustmentEventListValue, $checkExitsAdjustmentEventArray);
                    } else {
                        $adjustmentEventListValue['createDate']  = date('Y-m-d H:i:s');
                        $this->insertdata('finance_adjustment_event_list',$adjustmentEventListValue);
                    }
                }
            }
            if ($res['payload']['RefundEventList'] && !empty($res['payload']['RefundEventList'])) {
                foreach ($res['payload']['RefundEventList'] as $refundEventListValue) {
                    $refundEventListValue['fin_country'] = $usr['country_code'];
                    $refundEventListValue['added_by']    = $usr['profile_id'];
                    $checkExitsRefundEventArray = array(
                                                            'orderadjustmentitemid' => $refundEventListValue['orderadjustmentitemid'],
                                                            'sellersku'             => $refundEventListValue['sellersku'],
                                                            'added_by'              => $usr['profile_id'],
                                                            'amazonorderid'         => $refundEventListValue['amazonorderid'],
                                                            'dev_date'              => $refundEventListValue['dev_date']
                                                        );
                    $checkExits = $this->checkExits('finance_refund_event_list', $checkExitsRefundEventArray);
                    if ($checkExits > 0 ) {
                        $this->updatedata('finance_refund_event_list', $refundEventListValue, $checkExitsRefundEventArray);
                    } else {
                        $refundEventListValue['createDate']  = date('Y-m-d H:i:s');
                        $this->insertdata('finance_refund_event_list',$refundEventListValue);
                    }
                }
            }
            if ($res['payload']['ServiceFeeEventList'] && !empty($res['payload']['ServiceFeeEventList'])) {
                foreach ($res['payload']['ServiceFeeEventList'] as $serviceFeeEventListValue) {
                    $serviceFeeEventListValue['fin_country'] = $usr['country_code'];
                    $serviceFeeEventListValue['added_by']    = $usr['profile_id'];
                    $checkExitServiceFeeEventArray = array(
                                                                'fee_amount' => $serviceFeeEventListValue['fee_amount'],
                                                                'fee_type'   => $serviceFeeEventListValue['fee_type'],
                                                                'added_by'   => $usr['profile_id']
                                                            );
                    $checkExits = $this->checkExits('finance_service_fee_event_list', $checkExitServiceFeeEventArray);
                    if ($checkExits > 0 ) {
                        $this->updatedata('finance_service_fee_event_list', $serviceFeeEventListValue, $checkExitServiceFeeEventArray);
                    } else {
                        $serviceFeeEventListValue['createDate']  = date('Y-m-d H:i:s');
                        $this->insertdata('finance_service_fee_event_list',$serviceFeeEventListValue);
                    }
                }
            }
            $checkGetErrorLog = $res['payload']['mwsNewDataLog'];
            unset($res['payload']['mwsNewDataLog']);
            unset($res['payload']['ServiceFeeEventList']);
            unset($res['payload']['RefundEventList']);
            unset($res['payload']['AdjustmentEventList']);
            $apiGetDateToData = $this->startDate." to ".$res['createDate'];
            if (isset($res['payload'][0]) && !empty($res['payload'][0])) {
                foreach ($res['payload'] as $key => $value) {
                    // if ( (isset($value['amazon_order_id']) && trim($value['amazon_order_id']) !='' ) && (isset($value['seller_order_id']) && trim($value['seller_order_id']) !='' ) ) {
                    if ( isset($value['amazon_order_id']) && trim($value['amazon_order_id']) !='' ) {
                        $amazon_order_id = $value['amazon_order_id'];
                        $this->db->delete('finance_order_item_fee_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_charge_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_promotion_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_tax_withheld_list_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_shipment_fee_list', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        $this->db->delete('finance_order_item_data', array('amazon_order_id' => $amazon_order_id, 'dev_date' => $value['dev_date']));
                        if (isset($value['shipmentItemList']) && !empty($value['shipmentItemList'])) {
                            foreach ($value['shipmentItemList'] as $shipmentItemList) {
                                if (isset($shipmentItemList['ItemFeeList']) && !empty($shipmentItemList['ItemFeeList'])) {
                                    $this->db->insert_batch('finance_order_item_fee_list_data', $shipmentItemList['ItemFeeList']);
                                }
                                if (isset($shipmentItemList['ItemChargeList']) && !empty($shipmentItemList['ItemChargeList'])) {
                                    $this->db->insert_batch('finance_order_item_charge_list_data', $shipmentItemList['ItemChargeList']);
                                }
                                if (isset($shipmentItemList['PromotionList']) && !empty($shipmentItemList['PromotionList'])) {
                                    $this->db->insert_batch('finance_order_item_promotion_list_data', $shipmentItemList['PromotionList']);
                                }
                                if (isset($shipmentItemList['ItemTaxWithheldList']) && !empty($shipmentItemList['ItemTaxWithheldList'])) {
                                    $this->db->insert_batch('finance_order_item_tax_withheld_list_data', $shipmentItemList['ItemTaxWithheldList']);
                                }
                                unset($shipmentItemList['ItemFeeList']);
                                unset($shipmentItemList['ItemChargeList']);
                                unset($shipmentItemList['PromotionList']);
                                unset($shipmentItemList['ItemTaxWithheldList']);
                                $shipmentItemList['fin_country'] = $usr['country_code'];
                                $shipmentItemList['added_by']    = $usr['profile_id'];
                                $this->insertdata('finance_order_item_data',$shipmentItemList);
                            }
                        }

                        if (isset($value['ShipmentFeeList']) && !empty($value['ShipmentFeeList'])) {
                            $this->db->insert_batch('finance_order_shipment_fee_list', $value['ShipmentFeeList']);
                        }
                        unset($value['ShipmentFeeList']);
                        unset($value['shipmentItemList']);
                        // sleep(2);
                        $value['fin_country'] = $usr['country_code'];
                        $value['added_by']    = $usr['profile_id'];

                        // $value['extra']    = $usr['profile_id'];

                        $checkExits = $this->checkExits('finance_order_data',array('amazon_order_id' => $value['amazon_order_id'], 'dev_date' => $value['dev_date']));
                        if ($checkExits > 0 ) {
                            $value['finance_order_data_summary'] = 'n';
                            $checkUpDateFinData = $this->updatedata('finance_order_data', $value, array('amazon_order_id' => $value['amazon_order_id'], 'dev_date' => $value['dev_date']));
                            if ($checkUpDateFinData) {
                                $this->updatedata('rep_orders_data_order_date_list',['fee_flag' => 1 ], ['order_id' => $value['amazon_order_id'] ] );
                                $this->updatedata('finance_data_log',['date' => $res['createDate'] ], ['user_id' => $usr['profile_id'] ] );
                            } else {
                                $saveErrorData = array();
                                $saveErrorData['error'] = $this->db->error();
                                $saveErrorData['error_dates'] = $apiGetDateToData;
                                $mwsNewDataLogEmpty = array();
                                $mwsNewDataLogEmpty['table_name'] = "Error Found finance_order_data update Query";
                                $mwsNewDataLogEmpty['user_id']    = $usr['profile_id'];
                                $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                                $mwsNewDataLogEmpty['api_date']   = $res['createDate'];
                                $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                                // break;
                            }
                        } else {
                            $checkInsertFinData = $this->insertdata('finance_order_data',$value);
                            if ($checkInsertFinData) {
                                $this->updatedata('rep_orders_data_order_date_list',['fee_flag' => 1 ], ['order_id' => $value['amazon_order_id'] ] );
                                $this->updatedata('finance_data_log',['date' => $res['createDate'] ], ['user_id' => $usr['profile_id'] ] );
                            } else {
                                $saveErrorData = array();
                                $saveErrorData['error'] = $this->db->error();
                                $saveErrorData['error_dates'] = $apiGetDateToData;
                                $mwsNewDataLogEmpty = array();
                                $mwsNewDataLogEmpty['table_name'] = "Error Found finance_order_data insert Query";
                                $mwsNewDataLogEmpty['user_id']    = $usr['profile_id'];
                                $mwsNewDataLogEmpty['data']       = json_encode($saveErrorData);
                                $mwsNewDataLogEmpty['api_date']   = $res['createDate'];
                                $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                                // break;
                            }
                            // $insert[] = $value;
                        }
                    } else {
                        /*if (strtotime($res['createDate']) < strtotime($date_now) ) {
                            $createSubmitDate = date('Y-m-d', strtotime('+1 day', strtotime($res['createDate'])));
                            $this->updatedata('finance_data_log',['date' => $createSubmitDate ], ['user_id' => $usr['profile_id'] ] );
                        }*/
                        $this->updatedata('finance_data_log',['date' => $res['createDate'] ], ['user_id' => $usr['profile_id'] ] );
                    }
                }
            } else {
                if (isset($res['payload']) && empty($res['payload'][0])) {
                    if (empty($checkGetErrorLog)) {
                        $mwsNewDataLogEmpty = array();
                        $mwsNewDataLogEmpty['table_name'] = "No Data Found finance_order_data";
                        $mwsNewDataLogEmpty['user_id']    = $usr['profile_id'];
                        $mwsNewDataLogEmpty['data']       = $apiGetDateToData;
                        $mwsNewDataLogEmpty['api_date']   = $res['createDate'];
                        $this->insertdata('mws_new_data_log',$mwsNewDataLogEmpty);
                        $this->updatedata('finance_data_log',['date' => $res['createDate'] ], ['user_id' => $usr['profile_id'] ] );
                    }
                }
            }
            if (isset($res['tokens'])) {
                $token = $res['tokens'];
                $number = $res['numberCheck']+1;
                // if ($number < 2) {
                    $ressss =$this->product_api->fetch_product_details($usr['profile_id'],null,null,null, $token,$number);
                    $this->saveFinanceData($ressss,$usr);
                // }
                // die("hello dsfsdfds dsfds");
            }
        }
    }

    public function rerun_finace_empty()
    {
        $sql="UPDATE rep_orders_data_order_date_list SET fee_flag='0' where prod_id in(SELECT fin_id from finance_data where sku='')";
        //print_r($sql);
        $this->db->query($sql);
    }

    public function finance_order_data_summary()
    {
        // Code Start For add finance order data to summary table
        $get_finance_order_data = $this->product_api->get_finance_order_data();
        // echo "<pre>";
        // print_r($get_finance_order_data);
        // die();
        if (!empty($get_finance_order_data)) {
            $fods_insert_batch = array();
            $fods_update_batch = array();
            $fod_change_status_insert = array();
            $fod_change_status_update = array();
            foreach ($get_finance_order_data as $finance_order_data) {
                $financeCurrency = "";
                $finance_order_data_summary_array = array();
                $finance_order_data_summary_array["posted_date"]     = $finance_order_data["posted_date"];
                $finance_order_data_summary_array["Date_in_GMT"]     = $finance_order_data["posted_date_gmt"];
                $finance_order_data_summary_array["Date_in_PST"]     = $finance_order_data["posted_date_pst"];
                $finance_order_data_summary_array["amazon_order_id"] = $finance_order_data["amazon_order_id"];
                $finance_order_data_summary_array["added_by"] = $finance_order_data["added_by"];
                $finance_order_data_summary_array["dev_date"] = $finance_order_data["dev_date"];
                $finance_order_data_summary_array["marketplace"] = $finance_order_data["market_place"];
                $finance_order_data_summary_array["type"]        = 'order';
                $get_finance_order_item_data = $this->product_api->get_finance_order_item_data($finance_order_data);
                if (!empty($get_finance_order_item_data)) {
                    $quantity            = 0;
                    $sales_tax_collected = 0;
                    $product_sales       = 0;
                    $shipping_credits    = 0;
                    $gift_wrap_credits   = 0;
                    $fba_fees            = 0;
                    $selling_fees        = 0;
                    $other_transaction_fees    = 0;
                    $promotional_rebates_total = 0;
                    $total_charge_amount = 0;
                    foreach ($get_finance_order_item_data as $get_finance_order_item) {
                        $finance_order_data_summary_array["seller_sku"] = $get_finance_order_item["seller_sku"];
                        $quantity += $get_finance_order_item["quantity_shipped"];
                        $get_finance_order_item_charge_list_data = $this->product_api->get_finance_order_item_charge_list_data($get_finance_order_item);
                        if (!empty($get_finance_order_item_charge_list_data)) {
                            foreach ($get_finance_order_item_charge_list_data as $get_finance_order_item_charge_list) {
                                $financeCurrency = $get_finance_order_item_charge_list["currency_code"];
                                if ($get_finance_order_item_charge_list['charge_type'] == "Principal") {
                                    $product_sales += $get_finance_order_item_charge_list["charge_amount"];
                                }
                                if (strpos($get_finance_order_item_charge_list['charge_type'], "Tax") !== false ) {
                                    $sales_tax_collected += $get_finance_order_item_charge_list["charge_amount"];
                                }
                                if ($get_finance_order_item_charge_list['charge_type'] == "ShippingCharge") {
                                    $shipping_credits += $get_finance_order_item_charge_list["charge_amount"];
                                }
                                if ($get_finance_order_item_charge_list['charge_type'] == "GiftWrap") {
                                    $gift_wrap_credits += $get_finance_order_item_charge_list["charge_amount"];
                                }
                            }
                        }
                        $get_finance_order_item_fee_list_data = $this->product_api->get_finance_order_item_fee_list_data($get_finance_order_item);
                        if (!empty($get_finance_order_item_fee_list_data)) {
                            foreach ($get_finance_order_item_fee_list_data as $get_finance_order_item_fee_list) {
                                $financeCurrency = $get_finance_order_item_fee_list["currency_code"];
                                if ($get_finance_order_item_fee_list["fee_type"]=="Commission") {
                                    $selling_fees += $get_finance_order_item_fee_list["fee_amount"];
                                }
                                if (strpos($get_finance_order_item_fee_list["fee_type"], 'FBA') !== false || $get_finance_order_item_fee_list["fee_type"]=="GiftwrapChargeback" || $get_finance_order_item_fee_list["fee_type"]=="ShippingChargeback" ) {
                                    $fba_fees += $get_finance_order_item_fee_list["fee_amount"];
                                }
                                if ($get_finance_order_item_fee_list["fee_type"]=="SalesTaxCollectionFee") {
                                    $other_transaction_fees += $get_finance_order_item_fee_list["fee_amount"];
                                }
                            }
                        }
                        $get_finance_order_item_promotion_list_data = $this->product_api->get_finance_order_item_promotion_list_data($get_finance_order_item);
                        if (!empty($get_finance_order_item_promotion_list_data)) {
                            foreach ($get_finance_order_item_promotion_list_data as $get_finance_order_item_promotion_list) {
                                $financeCurrency = $get_finance_order_item_promotion_list["currency_code"];
                                if ($get_finance_order_item_promotion_list['promotion_type']=="PromotionMetaDataDefinitionValue") {
                                    $promotional_rebates_total += $get_finance_order_item_promotion_list["promotion_amount"];
                                }
                            }
                        }
                        $get_finance_order_item_tax_withheld_list_data = $this->product_api->get_finance_order_item_tax_withheld_list_data($get_finance_order_item);
                        if (!empty($get_finance_order_item_tax_withheld_list_data)) {
                            foreach ($get_finance_order_item_tax_withheld_list_data as $get_finance_order_item_tax_withheld_list) {
                                $financeCurrency = $get_finance_order_item_tax_withheld_list['currency_code'];
                                $total_charge_amount += $get_finance_order_item_tax_withheld_list['charge_amount'];
                            }
                        }
                    }
                    $finance_order_data_summary_array["quantity"] = $quantity;
                    $finance_order_data_summary_array["sales_tax_collected"] = $sales_tax_collected;
                    $finance_order_data_summary_array["product_sales"] = $product_sales;
                    $finance_order_data_summary_array["shipping_credits"] = $shipping_credits;
                    $finance_order_data_summary_array["gift_wrap_credits"] = $gift_wrap_credits;
                    $finance_order_data_summary_array["fba_fees"] = $fba_fees;
                    $finance_order_data_summary_array["selling_fees"] = $selling_fees;
                    $finance_order_data_summary_array["other_transaction_fees"] = $other_transaction_fees;
                    $finance_order_data_summary_array["promotional_rebates"] = $promotional_rebates_total;
                    $finance_order_data_summary_array["marketplace_facilitator_tax"] = $total_charge_amount;
                    $finance_order_data_summary_array["currency"] = $financeCurrency;
                    $get_finance_order_data_summary = $this->product_api->get_finance_order_data_summary($finance_order_data);
                    if (!empty($get_finance_order_data_summary)) {
                        $finance_order_data_summary_array['f_oid'] = $get_finance_order_data_summary->f_oid;
                        $fods_update_batch[]                       = $finance_order_data_summary_array;
                        $fod_change_status_update[]                = array('id' => $finance_order_data["id"], 'finance_order_data_summary' => 'y');
                    } else {
                        $fods_insert_batch[]        = $finance_order_data_summary_array;
                        $fod_change_status_insert[] = array('id' => $finance_order_data["id"], 'finance_order_data_summary' => 'y');
                    }
                }
            }

            if (!empty($fods_insert_batch)) {
                $check_fods_insert_batch = $this->db->insert_batch('finance_order_data_summary', $fods_insert_batch);
                if ($check_fods_insert_batch>=0) {
                    $this->db->update_batch('finance_order_data', $fod_change_status_insert, 'id');
                }
            }
            if (!empty($fods_update_batch)) {
                $check_fods_update_batch = $this->db->update_batch('finance_order_data_summary', $fods_update_batch, 'f_oid');
                if ($check_fods_update_batch>=0) {
                    $this->db->update_batch('finance_order_data', $fod_change_status_update, 'id');
                }
            }
        }
        // die("finance_order_data_summary");
        // Code End For add finance order data to summary table

        $changeStatus = array();
        $changeStatus['finance_order_data_summary'] = 'y';

        // Code Start To adjustment to summary table
        $get_finance_adjustment_event_list = $this->product_api->get_finance_adjustment_event_list();
        // echo "<pre>";
        // print_r($get_finance_adjustment_event_list);
        // die();
        if (!empty($get_finance_adjustment_event_list)) {
            $fae_insert_batch = array();
            $fae_update_batch = array();
            $fae_change_status_insert = array();
            $fae_change_status_update = array();
            foreach ($get_finance_adjustment_event_list as $get_finance_adjustment_event) {
                $add_finance_adjustment_event_list = array();
                $add_finance_adjustment_event_list['posted_date']   = $get_finance_adjustment_event['posteddate'];
                $add_finance_adjustment_event_list['seller_sku']    = $get_finance_adjustment_event['sellersku'];
                $add_finance_adjustment_event_list['quantity']      = $get_finance_adjustment_event['quantity'];
                $add_finance_adjustment_event_list['other_transaction_fees'] = $get_finance_adjustment_event['totalamount'];
                $add_finance_adjustment_event_list['added_by']      = $get_finance_adjustment_event['added_by'];
                $add_finance_adjustment_event_list['type']          = "adjustment";
                $add_finance_adjustment_event_list['adjustment_id'] = $get_finance_adjustment_event['id'];
                $add_finance_adjustment_event_list['currency']      = $get_finance_adjustment_event['currency'];
                $add_finance_adjustment_event_list['dev_date']      = $get_finance_adjustment_event['dev_date'];
                $checkExitsAdjustmentEventArrayForSummary = array(
                                                                    'other_transaction_fees' => $get_finance_adjustment_event['totalamount'],
                                                                    'seller_sku' => $get_finance_adjustment_event['sellersku'],
                                                                    'added_by'   => $get_finance_adjustment_event['added_by'],
                                                                    'type'       => "adjustment",
                                                                    'dev_date'   => $get_finance_adjustment_event['dev_date'],
                                                                );
                $checkExitAdjustmentEvent = checkExitData('finance_order_data_summary', $checkExitsAdjustmentEventArrayForSummary);
                if (!empty($checkExitAdjustmentEvent)) {
                    $checkExitAdjustmentEvent   = current($checkExitAdjustmentEvent);
                    $add_finance_adjustment_event_list['f_oid'] = $checkExitAdjustmentEvent['f_oid'];
                    $fae_update_batch[]         = $add_finance_adjustment_event_list;
                    $fae_change_status_update[] = array('id' => $get_finance_adjustment_event["id"], 'finance_order_data_summary' => 'y', 'finance_order_data_summary_id' => $checkExitAdjustmentEvent['f_oid']);
                } else {
                    $fae_insert_batch[]         = $add_finance_adjustment_event_list;
                    $fae_change_status_insert[] = array('id' => $get_finance_adjustment_event["id"], 'finance_order_data_summary' => 'y');
                }
            }

            if (!empty($fae_insert_batch)) {
                $get_first_increment_id = get_last_increment_id('finance_order_data_summary');
                $check_fae_insert_batch = $this->db->insert_batch('finance_order_data_summary', $fae_insert_batch);
                $get_last_increment_id  = get_last_increment_id('finance_order_data_summary');
                if ($check_fae_insert_batch>=0) {
                    $faeIn = 0;
                    for ($faeI=$get_first_increment_id; $faeI < $get_last_increment_id ; $faeI++) {
                        $fae_change_status_insert[$faeIn]['finance_order_data_summary_id'] = $faeI;
                        $faeIn++;
                    }
                    $this->db->update_batch('finance_adjustment_event_list', $fae_change_status_insert, 'id');
                }
            }

            if (!empty($fae_update_batch)) {
                $check_fae_update_batch = $this->db->update_batch('finance_order_data_summary', $fae_update_batch, 'f_oid');
                if ($check_fae_update_batch>=0) {
                    $this->db->update_batch('finance_adjustment_event_list', $fae_change_status_update, 'id');
                }
            }
        }
        // die('get_finance_adjustment_event_list');
        // Code End To adjustment to summary table

        // Code Start To refund to summary table
        $get_finance_refund_event_list = $this->product_api->get_finance_refund_event_list();
        // echo "<pre>";
        // print_r($get_finance_refund_event_list);
        // die();
        if (!empty($get_finance_refund_event_list)) {
            $frel_insert_batch = array();
            $frel_update_batch = array();
            $frel_change_status_insert = array();
            $frel_change_status_update = array();
            foreach ($get_finance_refund_event_list as $get_finance_refund_event) {
                $add_finance_refund_event_list = array();
                $add_finance_refund_event_list['posted_date'] = $get_finance_refund_event['posteddate'];
                $add_finance_refund_event_list['amazon_order_id'] = $get_finance_refund_event['amazonorderid'];
                $add_finance_refund_event_list['seller_sku']    = $get_finance_refund_event['sellersku'];
                $add_finance_refund_event_list['quantity']      = $get_finance_refund_event['quantityshipped'];
                $add_finance_refund_event_list['marketplace']   = $get_finance_refund_event['marketplacename'];
                $add_finance_refund_event_list['product_sales'] = $get_finance_refund_event['principal'];
                $add_finance_refund_event_list['shipping_credits']    = $get_finance_refund_event['shippingcharge'];
                $promotionmetadatadefinitionvalue_explode = explode(',',$get_finance_refund_event['promotionmetadatadefinitionvalue']);
                $promotionmetadatadefinitionvalue_sum     = array_sum($promotionmetadatadefinitionvalue_explode);
                $add_finance_refund_event_list['promotional_rebates'] = $promotionmetadatadefinitionvalue_sum;
                $add_finance_refund_event_list['sales_tax_collected'] = $get_finance_refund_event['tax'];
                $marketplacefacilitatortaxprincipal = $get_finance_refund_event['marketplacefacilitatortaxprincipal'];
                if (!is_numeric(trim($get_finance_refund_event['marketplacefacilitatortaxprincipal']))) {
                    $marketplacefacilitatortaxprincipal = 0;
                }
                $marketplacefacilitatortaxshipping = $get_finance_refund_event['marketplacefacilitatortaxshipping'];
                if (!is_numeric(trim($get_finance_refund_event['marketplacefacilitatortaxshipping']))) {
                    $marketplacefacilitatortaxshipping = 0;
                }
                $add_finance_refund_event_list['marketplace_facilitator_tax'] = ($marketplacefacilitatortaxprincipal+$marketplacefacilitatortaxshipping);
                $add_finance_refund_event_list['selling_fees'] = $get_finance_refund_event['commission'];
                $add_finance_refund_event_list['other_transaction_fees'] = $get_finance_refund_event['refundcommission'];
                $add_finance_refund_event_list['added_by']  = $get_finance_refund_event['added_by'];
                $add_finance_refund_event_list['type']      = "refund";
                $add_finance_refund_event_list['dev_date']  = $get_finance_refund_event['dev_date'];
                $add_finance_refund_event_list['currency']  = $get_finance_refund_event['currency'];
                $add_finance_refund_event_list['refund_id'] = $get_finance_refund_event['id'];
                $checkExitsRefundEventArrayForSummary = array(
                                                                    'amazon_order_id' => $get_finance_refund_event['amazonorderid'],
                                                                    'seller_sku' => $get_finance_refund_event['sellersku'],
                                                                    'added_by'   => $get_finance_refund_event['added_by'],
                                                                    'type'       => "refund",
                                                                    'dev_date'   => $get_finance_refund_event['dev_date'],
                                                                );
                $checkExitRefundEvent = checkExitData('finance_order_data_summary', $checkExitsRefundEventArrayForSummary);
                if (!empty($checkExitRefundEvent)) {
                    $checkExitRefundEvent = current($checkExitRefundEvent);
                    $add_finance_refund_event_list['f_oid'] = $checkExitRefundEvent['f_oid'];
                    $frel_update_batch[]         = $add_finance_refund_event_list;
                    $frel_change_status_update[] = array('id' => $get_finance_refund_event["id"], 'finance_order_data_summary' => 'y', 'finance_order_data_summary_id' => $checkExitRefundEvent['f_oid']);
                } else {
                    $frel_insert_batch[]         = $add_finance_refund_event_list;
                    $frel_change_status_insert[] = array('id' => $get_finance_refund_event["id"], 'finance_order_data_summary' => 'y');
                }
            }

            if (!empty($frel_insert_batch)) {
                $get_first_increment_id  = get_last_increment_id('finance_order_data_summary');
                $check_frel_insert_batch = $this->db->insert_batch('finance_order_data_summary', $frel_insert_batch);
                $get_last_increment_id   = get_last_increment_id('finance_order_data_summary');
                if ($check_frel_insert_batch>=0) {
                    $frelIn = 0;
                    for ($frelI=$get_first_increment_id; $frelI < $get_last_increment_id ; $frelI++) {
                        $frel_change_status_insert[$frelIn]['finance_order_data_summary_id'] = $frelI;
                        $frelIn++;
                    }
                    $this->db->update_batch('finance_refund_event_list', $frel_change_status_insert, 'id');
                }
            }

            if (!empty($frel_update_batch)) {
                $check_frel_update_batch = $this->db->update_batch('finance_order_data_summary', $frel_update_batch, 'f_oid');
                if ($check_frel_update_batch>=0) {
                    $this->db->update_batch('finance_refund_event_list', $frel_change_status_update, 'id');
                }
            }
        }
        // die("finance_refund_event_list");
        // Code End To refund to summary table

        // Code Start To service to summary table
        $get_finance_service_fee_event_list = $this->product_api->get_finance_service_fee_event_list();
        // echo "<pre>";
        // print_r($get_finance_service_fee_event_list);
        // die();
        if (!empty($get_finance_service_fee_event_list)) {
            $fsfel_insert_batch = array();
            $fsfel_update_batch = array();
            $fsfel_change_status_insert = array();
            $fsfel_change_status_update = array();
            foreach ($get_finance_service_fee_event_list as $get_finance_service_fee_event) {
                $add_finance_service_fee_event = array();
                $add_finance_service_fee_event['other_transaction_fees'] = $get_finance_service_fee_event['fee_amount'];
                $add_finance_service_fee_event['added_by'] = $get_finance_service_fee_event['added_by'];
                $add_finance_service_fee_event['type']     = "service-fee";
                $add_finance_service_fee_event['service_fee_id'] = $get_finance_service_fee_event['id'];
                $add_finance_service_fee_event['currency']    = $get_finance_service_fee_event['currency'];
                $add_finance_service_fee_event['fee_type']    = $get_finance_service_fee_event['fee_type'];
                $add_finance_service_fee_event['posted_date'] = $get_finance_service_fee_event['ref_date'];
                $checkExitServiceFeeEventArrayForSummary = array(
                                                                    'other_transaction_fees' => $get_finance_service_fee_event['fee_amount'],
                                                                    'added_by'   => $get_finance_service_fee_event['added_by'],
                                                                    'type'       => "service-fee",
                                                                    'fee_type'   => $get_finance_service_fee_event['fee_type']
                                                                );
                $checkExitServiceFeeEvent = checkExitData('finance_order_data_summary', $checkExitServiceFeeEventArrayForSummary);
                if (!empty($checkExitServiceFeeEvent)) {
                    $checkExitServiceFeeEvent = current($checkExitServiceFeeEvent);
                    $add_finance_service_fee_event['f_oid'] = $checkExitServiceFeeEvent['f_oid'];
                    $fsfel_update_batch[]         = $add_finance_service_fee_event;
                    $fsfel_change_status_update[] = array('id' => $get_finance_service_fee_event["id"], 'finance_order_data_summary' => 'y', 'finance_order_data_summary_id' => $checkExitServiceFeeEvent['f_oid']);
                } else {
                    $fsfel_insert_batch[]         = $add_finance_service_fee_event;
                    $fsfel_change_status_insert[] = array('id' => $get_finance_service_fee_event["id"], 'finance_order_data_summary' => 'y');
                }
            }

            if (!empty($fsfel_insert_batch)) {
                $get_first_increment_id   = get_last_increment_id('finance_order_data_summary');
                $check_fsfel_insert_batch = $this->db->insert_batch('finance_order_data_summary', $fsfel_insert_batch);
                $get_last_increment_id    = get_last_increment_id('finance_order_data_summary');
                if ($check_fsfel_insert_batch>=0) {
                    $fsfelIn = 0;
                    for ($fsfelI=$get_first_increment_id; $fsfelI < $get_last_increment_id ; $fsfelI++) {
                        $fsfel_change_status_insert[$fsfelIn]['finance_order_data_summary_id'] = $fsfelI;
                        $fsfelIn++;
                    }
                    $this->db->update_batch('finance_service_fee_event_list', $fsfel_change_status_insert, 'id');
                }
            }

            if (!empty($fsfel_update_batch)) {
                $check_fsfel_update_batch = $this->db->update_batch('finance_order_data_summary', $fsfel_update_batch, 'f_oid');
                if ($check_fsfel_update_batch>=0) {
                    $this->db->update_batch('finance_service_fee_event_list', $fsfel_change_status_update, 'id');
                }
            }
        }
        // Code End To service to summary table
    }

    public function finance_data_api_delete_data()
    {
        $matchDeleteArray = array('save_data' => 'y');
        deletedata('finance_data_api', $matchDeleteArray);
    }

    /*public function send_error_mail()
    {
        $this->db->select('*');
        $this->db->from('mws_new_data_log');
        $this->db->where('sent_mail','n');
        $query = $this->db->get();
        $get_all_error = $query->result_array();
        if (!empty($get_all_error)) {
            $error_view_array = array();
            $error_msg_array = array();
            $error_status_array = array();
            foreach ($get_all_error as $get_error) {
                $error_msg_array[$get_error['user_id']][] = $get_error['data'];
                $error_status_array[] = $get_error['id'];
            }
            $error_msg_html = "";
            foreach ($error_msg_array as $error_key => $error_msgs) {
                $error_msg_html .= "User ID => ".$error_key."<br><br>";
                foreach ($error_msgs as $error_msg) {
                    $error_msg_html .= "Error Msg => ".$error_msg."<br><br>";
                }
                $error_msg_html .= "<br><br><br>";
            }
            $error_view_array['error_msg_html'] = $error_msg_html;
            $emailData =  array();
            $emailData['to'] = "support@magicdatamachine.com";
            $emailData['subject'] = "DataHub Error";
            $emailData['message'] = $this->load->view('email/email_template',$error_view_array,true);
            $checkEmail = sendEmails($emailData);
            if ($checkEmail) {
                $changeStatus = array();
                $changeStatus['sent_mail'] = "y";
                $this->db->where_in('id', $error_status_array);
                $this->db->update('mws_new_data_log',$changeStatus);
            }
        }
    }*/
}
