<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Report_file_process extends CI_Model
{
    public function  __construct()
    {
        parent::__construct();
    }

    public function update_report_feed_log($user_id,$req_id)
    {
        $this->db->query("UPDATE report_feed SET is_processed=1 WHERE req_id=".$req_id);
    }

    public function process_afn_inventory_data($user_id,$report_file,$country,$request_type)
    {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while (!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if($i>=1 && !empty($buffer[0]) )
                {
                    $sku= isset($buffer[0])?$this->db->escape($buffer[0]):'';
                    $asin= isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $itm_qty=isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    echo $sku."\t".$asin."\t".$itm_qty."\t".$buffer[4]."\n";
                    $bulk_data[]="(".$sku.",".$asin.",".$itm_qty.",".$user_id.",'".$country."','FBA')";
                }

                if(isset($bulk_data) && count($bulk_data)>=500)
                {
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT INTO `customer_product` (prod_sku,prod_asin,itm_qty,added_by,prod_country,fc_code)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    prod_sku=VALUES(prod_sku),prod_asin=VALUES(prod_asin),itm_qty=VALUES(itm_qty),prod_country=VALUES(prod_country),fc_code=VALUES(fc_code);";
                    $this->db->query($qi);
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {
                $quer=implode(',',$bulk_data);
                $qi="INSERT INTO `customer_product` (prod_sku,prod_asin,itm_qty,added_by,prod_country,fc_code)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                prod_sku=VALUES(prod_sku),prod_asin=VALUES(prod_asin),itm_qty=VALUES(itm_qty),prod_country=VALUES(prod_country),fc_code=VALUES(fc_code);";
                $this->db->query($qi);
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
    }

    public function process_inventory_data($user_id,$report_file,$country,$request_type)
    {
        // echo "<pre>";
        $responseData = array();
        $responseData['response'] = 1;
        $responseData['msg'] = "";
        $responseData['table_name'] = "active_inventory_data";
        try {
            $dataBaseColumnName = array(
                                            'item-name' => 'item_name',
                                            'item-description' => 'item_description',
                                            'listing-id' => 'listing_id',
                                            'seller-sku' => 'seller_sku',
                                            'price' => 'price',
                                            'quantity' => 'quantity',
                                            'open-date' => 'open_date',
                                            'image-url' => 'image_url',
                                            'item-is-marketplace' => 'item_is_marketplace',
                                            'product-id-type' => 'product_id_type',
                                            'zshop-shipping-fee' => 'zshop_shipping_fee',
                                            'item-note' => 'item_note',
                                            'item-condition' => 'item_condition',
                                            'zshop-category1' => 'zshop_category1',
                                            'zshop-browse-path' => 'zshop_browse_path',
                                            'zshop-storefront-feature' => 'zshop_storefront_feature',
                                            'asin1' => 'asin1',
                                            'asin2' => 'asin2',
                                            'asin3' => 'asin3',
                                            'will-ship-internationally' => 'will_ship_internationally',
                                            'expedited-shipping' => 'expedited_shipping',
                                            'zshop-boldface' => 'zshop_boldface',
                                            'product-id' => 'product_id',
                                            'bid-for-featured-placement' => 'bid_for_featured_placement',
                                            'add-delete' => 'add_delete',
                                            'pending-quantity' => 'pending_quantity',
                                            'fulfillment-channel' => 'fulfillment_channel',
                                            'business-price' => 'business_price',
                                            'quantity-price-type' => 'quantity_price_type',
                                            'quantity-lower-bound-1' => 'quantity_lower_bound_1',
                                            'quantity-price-1' => 'quantity_price_1',
                                            'quantity-lower-bound-2' => 'quantity_lower_bound_2',
                                            'quantity-price-2' => 'quantity_price_2',
                                            'quantity-lower-bound-3' => 'quantity_lower_bound_3',
                                            'quantity-price-3' => 'quantity_price_3',
                                            'quantity-lower-bound-4' => 'quantity_lower_bound_4',
                                            'quantity-price-4' => 'quantity_price_4',
                                            'quantity-lower-bound-5' => 'quantity_lower_bound_5',
                                            'quantity-price-5' => 'quantity_price_5',
                                            'merchant-shipping-group' => 'merchant_shipping_group'
                                        );


            $fp=fopen($report_file,'r');
            if ($fp)
            {
                $i=0;
                $csvColumnNames = array();
                $activeInventoryBulkQueryData = array();
                while (!feof($fp))
                {
                    $buffer = fgetcsv($fp,0,"\t");
                    // print_r($buffer);
                    if ($i===0) {
                        $csvColumnNames = $buffer;
                    }

                    if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0]))) {
                        if (strpos($buffer[0],"ErrorResponse")) {
                            $responseData['response'] = 2;
                            $getFileErrorXml     = simplexml_load_file($report_file);
                            $getFileErrorEncode  = $getFileErrorXml;
                            $responseData['msg'] = $getFileErrorEncode;
                            $responseData['fileName'] = $report_file;
                            return $responseData;
                            break;
                        }
                    }

                    if($i>=1 && !empty($buffer[3]))
                    {
                        $insertData = array();
                        foreach ($csvColumnNames as $key => $csvColumnName) {
                            if (in_array($csvColumnName, $csvColumnNames) && array_key_exists($csvColumnName,$dataBaseColumnName)) {
                                $getMatchKey = array_search($csvColumnName, $csvColumnNames);
                                if (isset($dataBaseColumnName[$csvColumnName])) {
                                    $insertData[$dataBaseColumnName[$csvColumnName]] = $buffer[$getMatchKey];
                                }
                            }
                        }

                        foreach ($dataBaseColumnName as $checkColumnName) {
                            if (!array_key_exists($checkColumnName,$insertData)) {
                                $insertData[$checkColumnName] = "";
                            }
                        }

                        if (isset($insertData['open_date'])) {
                            $dateExplode = explode(' ', $insertData['open_date']);
                            if (isset($dateExplode[0]) && isset($dateExplode[1])) {
                                $replaceTextDate = str_replace('/','-',$dateExplode[0]);
                                $setDateFoemat = $replaceTextDate." ".$dateExplode[1];
                                $insertData['open_date'] =  date('Y-m-d H:i:s', strtotime($setDateFoemat));
                            }
                        }

                        // $insertData['added_by'] = $user_id;
                        // $insertData['country']  = $country;
                        // print_r($insertData);
                        // die();

                        $activeInventoryData_item_name                 = (isset($insertData["item_name"]) && "" != trim($insertData["item_name"])) ? $this->db->escape($insertData["item_name"]) : $this->db->escape('');
                        $activeInventoryData_item_description          = (isset($insertData["item_description"]) && "" != trim($insertData["item_description"])) ? $this->db->escape($insertData["item_description"]) : $this->db->escape('');
                        $activeInventoryData_listing_id                = (isset($insertData["listing_id"]) && "" != trim($insertData["listing_id"])) ? $this->db->escape($insertData["listing_id"]) : $this->db->escape('');
                        $activeInventoryData_seller_sku                = (isset($insertData["seller_sku"]) && "" != trim($insertData["seller_sku"])) ? $this->db->escape($insertData["seller_sku"]) : $this->db->escape('');
                        $activeInventoryData_price                     = (isset($insertData["price"]) && "" != trim($insertData["price"])) ? $this->db->escape($insertData["price"]) : $this->db->escape('');
                        $activeInventoryData_quantity                  = (isset($insertData["quantity"]) && "" != trim($insertData["quantity"])) ? $this->db->escape($insertData["quantity"]) : $this->db->escape('');
                        $activeInventoryData_open_date                 = (isset($insertData["open_date"]) && "" != trim($insertData["open_date"])) ? $this->db->escape($insertData["open_date"]) : $this->db->escape('');
                        $activeInventoryData_image_url                 = (isset($insertData["image_url"]) && "" != trim($insertData["image_url"])) ? $this->db->escape($insertData["image_url"]) : $this->db->escape('');
                        $activeInventoryData_item_is_marketplace       = (isset($insertData["item_is_marketplace"]) && "" != trim($insertData["item_is_marketplace"])) ? $this->db->escape($insertData["item_is_marketplace"]) : $this->db->escape('');
                        $activeInventoryData_product_id_type           = (isset($insertData["product_id_type"]) && "" != trim($insertData["product_id_type"])) ? $this->db->escape($insertData["product_id_type"]) : $this->db->escape('');
                        $activeInventoryData_zshop_shipping_fee        = (isset($insertData["zshop_shipping_fee"]) && "" != trim($insertData["zshop_shipping_fee"])) ? $this->db->escape($insertData["zshop_shipping_fee"]) : $this->db->escape('');
                        $activeInventoryData_item_note                 = (isset($insertData["item_note"]) && "" != trim($insertData["item_note"])) ? $this->db->escape($insertData["item_note"]) : $this->db->escape('');
                        $activeInventoryData_item_condition            = (isset($insertData["item_condition"]) && "" != trim($insertData["item_condition"])) ? $this->db->escape($insertData["item_condition"]) : $this->db->escape('');
                        $activeInventoryData_zshop_category1           = (isset($insertData["zshop_category1"]) && "" != trim($insertData["zshop_category1"])) ? $this->db->escape($insertData["zshop_category1"]) : $this->db->escape('');
                        $activeInventoryData_zshop_browse_path         = (isset($insertData["zshop_browse_path"]) && "" != trim($insertData["zshop_browse_path"])) ? $this->db->escape($insertData["zshop_browse_path"]) : $this->db->escape('');
                        $activeInventoryData_zshop_storefront_feature  = (isset($insertData["zshop_storefront_feature"]) && "" != trim($insertData["zshop_storefront_feature"])) ? $this->db->escape($insertData["zshop_storefront_feature"]) : $this->db->escape('');
                        $activeInventoryData_asin1                     = (isset($insertData["asin1"]) && "" != trim($insertData["asin1"])) ? $this->db->escape($insertData["asin1"]) : $this->db->escape('');
                        $activeInventoryData_asin2                     = (isset($insertData["asin2"]) && "" != trim($insertData["asin2"])) ? $this->db->escape($insertData["asin2"]) : $this->db->escape('');
                        $activeInventoryData_asin3                     = (isset($insertData["asin3"]) && "" != trim($insertData["asin3"])) ? $this->db->escape($insertData["asin3"]) : $this->db->escape('');
                        $activeInventoryData_will_ship_internationally = (isset($insertData["will_ship_internationally"]) && "" != trim($insertData["will_ship_internationally"])) ? $this->db->escape($insertData["will_ship_internationally"]) : $this->db->escape('');
                        $activeInventoryData_expedited_shipping        = (isset($insertData["expedited_shipping"]) && "" != trim($insertData["expedited_shipping"])) ? $this->db->escape($insertData["expedited_shipping"]) : $this->db->escape('');
                        $activeInventoryData_zshop_boldface            = (isset($insertData["zshop_boldface"]) && "" != trim($insertData["zshop_boldface"])) ? $this->db->escape($insertData["zshop_boldface"]) : $this->db->escape('');
                        $activeInventoryData_product_id                = (isset($insertData["product_id"]) && "" != trim($insertData["product_id"])) ? $this->db->escape($insertData["product_id"]) : $this->db->escape('');
                        $activeInventoryData_bid_for_featured_placement = (isset($insertData["bid_for_featured_placement"]) && "" != trim($insertData["bid_for_featured_placement"])) ? $this->db->escape($insertData["bid_for_featured_placement"]) : $this->db->escape('');
                        $activeInventoryData_add_delete                 = (isset($insertData["add_delete"]) && "" != trim($insertData["add_delete"])) ? $this->db->escape($insertData["add_delete"]) : $this->db->escape('');
                        $activeInventoryData_pending_quantity           = (isset($insertData["pending_quantity"]) && "" != trim($insertData["pending_quantity"])) ? $this->db->escape($insertData["pending_quantity"]) : $this->db->escape('');
                        $activeInventoryData_fulfillment_channel        = (isset($insertData["fulfillment_channel"]) && "" != trim($insertData["fulfillment_channel"])) ? $this->db->escape($insertData["fulfillment_channel"]) : $this->db->escape('');
                        $activeInventoryData_business_price             = (isset($insertData["business_price"]) && "" != trim($insertData["business_price"])) ? $this->db->escape($insertData["business_price"]) : $this->db->escape('');
                        $activeInventoryData_quantity_price_type        = (isset($insertData["quantity_price_type"]) && "" != trim($insertData["quantity_price_type"])) ? $this->db->escape($insertData["quantity_price_type"]) : $this->db->escape('');
                        $activeInventoryData_quantity_lower_bound_1     = (isset($insertData["quantity_lower_bound_1"]) && "" != trim($insertData["quantity_lower_bound_1"])) ? $this->db->escape($insertData["quantity_lower_bound_1"]) : $this->db->escape('');
                        $activeInventoryData_quantity_price_1           = (isset($insertData["quantity_price_1"]) && "" != trim($insertData["quantity_price_1"])) ? $this->db->escape($insertData["quantity_price_1"]) : $this->db->escape('');
                        $activeInventoryData_quantity_lower_bound_2     = (isset($insertData["quantity_lower_bound_2"]) && "" != trim($insertData["quantity_lower_bound_2"])) ? $this->db->escape($insertData["quantity_lower_bound_2"]) : $this->db->escape('');
                        $activeInventoryData_quantity_price_2           = (isset($insertData["quantity_price_2"]) && "" != trim($insertData["quantity_price_2"])) ? $this->db->escape($insertData["quantity_price_2"]) : $this->db->escape('');
                        $activeInventoryData_quantity_lower_bound_3     = (isset($insertData["quantity_lower_bound_3"]) && "" != trim($insertData["quantity_lower_bound_3"])) ? $this->db->escape($insertData["quantity_lower_bound_3"]) : $this->db->escape('');
                        $activeInventoryData_quantity_price_3           = (isset($insertData["quantity_price_3"]) && "" != trim($insertData["quantity_price_3"])) ? $this->db->escape($insertData["quantity_price_3"]) : $this->db->escape('');
                        $activeInventoryData_quantity_lower_bound_4     = (isset($insertData["quantity_lower_bound_4"]) && "" != trim($insertData["quantity_lower_bound_4"])) ? $this->db->escape($insertData["quantity_lower_bound_4"]) : $this->db->escape('');
                        $activeInventoryData_quantity_price_4           = (isset($insertData["quantity_price_4"]) && "" != trim($insertData["quantity_price_4"])) ? $this->db->escape($insertData["quantity_price_4"]) : $this->db->escape('');
                        $activeInventoryData_quantity_lower_bound_5     = (isset($insertData["quantity_lower_bound_5"]) && "" != trim($insertData["quantity_lower_bound_5"])) ? $this->db->escape($insertData["quantity_lower_bound_5"]) : $this->db->escape('');
                        $activeInventoryData_quantity_price_5           = (isset($insertData["quantity_price_5"]) && "" != trim($insertData["quantity_price_5"])) ? $this->db->escape($insertData["quantity_price_5"]) : $this->db->escape('');
                        $activeInventoryData_merchant_shipping_group    = (isset($insertData["merchant_shipping_group"]) && "" != trim($insertData["merchant_shipping_group"])) ? $this->db->escape($insertData["merchant_shipping_group"]) : $this->db->escape('');
                        $activeInventoryData_country                    = $this->db->escape($country);
                        $activeInventoryData_added_by                   = $this->db->escape($user_id);

                        $activeInventoryBulkQueryData[] = "({$activeInventoryData_item_name},{$activeInventoryData_item_description},{$activeInventoryData_listing_id},{$activeInventoryData_seller_sku},{$activeInventoryData_price},{$activeInventoryData_quantity},{$activeInventoryData_open_date},{$activeInventoryData_image_url},{$activeInventoryData_item_is_marketplace},{$activeInventoryData_product_id_type},{$activeInventoryData_zshop_shipping_fee},{$activeInventoryData_item_note},{$activeInventoryData_item_condition},{$activeInventoryData_zshop_category1},{$activeInventoryData_zshop_browse_path},{$activeInventoryData_zshop_storefront_feature},{$activeInventoryData_asin1},{$activeInventoryData_asin2},{$activeInventoryData_asin3},{$activeInventoryData_will_ship_internationally},{$activeInventoryData_expedited_shipping},{$activeInventoryData_zshop_boldface},{$activeInventoryData_product_id},{$activeInventoryData_bid_for_featured_placement},{$activeInventoryData_add_delete},{$activeInventoryData_pending_quantity},{$activeInventoryData_fulfillment_channel},{$activeInventoryData_business_price},{$activeInventoryData_quantity_price_type},{$activeInventoryData_quantity_lower_bound_1},{$activeInventoryData_quantity_price_1},{$activeInventoryData_quantity_lower_bound_2},{$activeInventoryData_quantity_price_2},{$activeInventoryData_quantity_lower_bound_3},{$activeInventoryData_quantity_price_3},{$activeInventoryData_quantity_lower_bound_4},{$activeInventoryData_quantity_price_4},{$activeInventoryData_quantity_lower_bound_5},{$activeInventoryData_quantity_price_5},{$activeInventoryData_merchant_shipping_group},{$activeInventoryData_country},{$activeInventoryData_added_by})";

                        if (!empty($activeInventoryBulkQueryData) && count($activeInventoryBulkQueryData) > 200) {
                            $activeInventoryBulkQueryData_implode = implode(',',$activeInventoryBulkQueryData);
                            $activeInventoryBulkQueryData_sql_query = "INSERT INTO `active_inventory_data` (`item_name`, `item_description`, `listing_id`, `seller_sku`, `price`, `quantity`, `open_date`, `image_url`, `item_is_marketplace`, `product_id_type`, `zshop_shipping_fee`, `item_note`, `item_condition`, `zshop_category1`, `zshop_browse_path`, `zshop_storefront_feature`, `asin1`, `asin2`, `asin3`, `will_ship_internationally`, `expedited_shipping`, `zshop_boldface`, `product_id`, `bid_for_featured_placement`, `add_delete`, `pending_quantity`, `fulfillment_channel`, `business_price`, `quantity_price_type`, `quantity_lower_bound_1`, `quantity_price_1`, `quantity_lower_bound_2`, `quantity_price_2`, `quantity_lower_bound_3`, `quantity_price_3`, `quantity_lower_bound_4`, `quantity_price_4`, `quantity_lower_bound_5`, `quantity_price_5`, `merchant_shipping_group`, `country`, `added_by`)
                                                                        VALUES
                                                                        $activeInventoryBulkQueryData_implode
                                                                        ON DUPLICATE KEY
                                                                        UPDATE
                                                                        item_name=VALUES(item_name), item_description=VALUES(item_description), listing_id=VALUES(listing_id), seller_sku=VALUES(seller_sku), price=VALUES(price), quantity=VALUES(quantity), open_date=VALUES(open_date), image_url=VALUES(image_url), item_is_marketplace=VALUES(item_is_marketplace), product_id_type=VALUES(product_id_type), zshop_shipping_fee=VALUES(zshop_shipping_fee), item_note=VALUES(item_note), item_condition=VALUES(item_condition), zshop_category1=VALUES(zshop_category1), zshop_browse_path=VALUES(zshop_browse_path), zshop_storefront_feature=VALUES(zshop_storefront_feature), asin1=VALUES(asin1), asin2=VALUES(asin2), asin3=VALUES(asin3), will_ship_internationally=VALUES(will_ship_internationally), expedited_shipping=VALUES(expedited_shipping), zshop_boldface=VALUES(zshop_boldface), product_id=VALUES(product_id), bid_for_featured_placement=VALUES(bid_for_featured_placement), add_delete=VALUES(add_delete), pending_quantity=VALUES(pending_quantity), fulfillment_channel=VALUES(fulfillment_channel), business_price=VALUES(business_price), quantity_price_type=VALUES(quantity_price_type), quantity_lower_bound_1=VALUES(quantity_lower_bound_1), quantity_price_1=VALUES(quantity_price_1), quantity_lower_bound_2=VALUES(quantity_lower_bound_2), quantity_price_2=VALUES(quantity_price_2), quantity_lower_bound_3=VALUES(quantity_lower_bound_3), quantity_price_3=VALUES(quantity_price_3), quantity_lower_bound_4=VALUES(quantity_lower_bound_4), quantity_price_4=VALUES(quantity_price_4), quantity_lower_bound_5=VALUES(quantity_lower_bound_5), quantity_price_5=VALUES(quantity_price_5), merchant_shipping_group=VALUES(merchant_shipping_group), country=VALUES(country), added_by=VALUES(added_by)";

                            $check_activeInventoryBulkQueryData_sql_query = $this->db->query($activeInventoryBulkQueryData_sql_query);
                            if (!$check_activeInventoryBulkQueryData_sql_query) {
                                $getError = $this->db->error();
                                $responseData['response'] = 2;
                                $responseData['msg']      = $getError;
                                $responseData['fileName'] = $report_file;
                                return $responseData;
                                break;
                            }
                            $activeInventoryBulkQueryData = array();
                        }
                    }
                    $i++;
                }//while ends here
                fclose($fp);

                if (!empty($activeInventoryBulkQueryData) && count($activeInventoryBulkQueryData) > 0) {
                    $activeInventoryBulkQueryData_implode = implode(',',$activeInventoryBulkQueryData);
                    $activeInventoryBulkQueryData_sql_query = "INSERT INTO `active_inventory_data` (`item_name`, `item_description`, `listing_id`, `seller_sku`, `price`, `quantity`, `open_date`, `image_url`, `item_is_marketplace`, `product_id_type`, `zshop_shipping_fee`, `item_note`, `item_condition`, `zshop_category1`, `zshop_browse_path`, `zshop_storefront_feature`, `asin1`, `asin2`, `asin3`, `will_ship_internationally`, `expedited_shipping`, `zshop_boldface`, `product_id`, `bid_for_featured_placement`, `add_delete`, `pending_quantity`, `fulfillment_channel`, `business_price`, `quantity_price_type`, `quantity_lower_bound_1`, `quantity_price_1`, `quantity_lower_bound_2`, `quantity_price_2`, `quantity_lower_bound_3`, `quantity_price_3`, `quantity_lower_bound_4`, `quantity_price_4`, `quantity_lower_bound_5`, `quantity_price_5`, `merchant_shipping_group`, `country`, `added_by`)
                                                                VALUES
                                                                $activeInventoryBulkQueryData_implode
                                                                ON DUPLICATE KEY
                                                                UPDATE
                                                                item_name=VALUES(item_name), item_description=VALUES(item_description), listing_id=VALUES(listing_id), seller_sku=VALUES(seller_sku), price=VALUES(price), quantity=VALUES(quantity), open_date=VALUES(open_date), image_url=VALUES(image_url), item_is_marketplace=VALUES(item_is_marketplace), product_id_type=VALUES(product_id_type), zshop_shipping_fee=VALUES(zshop_shipping_fee), item_note=VALUES(item_note), item_condition=VALUES(item_condition), zshop_category1=VALUES(zshop_category1), zshop_browse_path=VALUES(zshop_browse_path), zshop_storefront_feature=VALUES(zshop_storefront_feature), asin1=VALUES(asin1), asin2=VALUES(asin2), asin3=VALUES(asin3), will_ship_internationally=VALUES(will_ship_internationally), expedited_shipping=VALUES(expedited_shipping), zshop_boldface=VALUES(zshop_boldface), product_id=VALUES(product_id), bid_for_featured_placement=VALUES(bid_for_featured_placement), add_delete=VALUES(add_delete), pending_quantity=VALUES(pending_quantity), fulfillment_channel=VALUES(fulfillment_channel), business_price=VALUES(business_price), quantity_price_type=VALUES(quantity_price_type), quantity_lower_bound_1=VALUES(quantity_lower_bound_1), quantity_price_1=VALUES(quantity_price_1), quantity_lower_bound_2=VALUES(quantity_lower_bound_2), quantity_price_2=VALUES(quantity_price_2), quantity_lower_bound_3=VALUES(quantity_lower_bound_3), quantity_price_3=VALUES(quantity_price_3), quantity_lower_bound_4=VALUES(quantity_lower_bound_4), quantity_price_4=VALUES(quantity_price_4), quantity_lower_bound_5=VALUES(quantity_lower_bound_5), quantity_price_5=VALUES(quantity_price_5), merchant_shipping_group=VALUES(merchant_shipping_group), country=VALUES(country), added_by=VALUES(added_by)";

                    $check_activeInventoryBulkQueryData_sql_query = $this->db->query($activeInventoryBulkQueryData_sql_query);
                    if (!$check_activeInventoryBulkQueryData_sql_query) {
                        $getError = $this->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $getError;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                    }
                }
                // print_r($activeInventoryBulkQueryData);
            }
            return $responseData;
        } catch(Exception $e) {
            $responseData['response'] = 2;
            $responseData['msg']      = $e->getMessage();
            $responseData['fileName'] = $report_file;
            return $responseData;
        }
    }


public function process_inactive_inventory_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "inactive_inventory_data";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while (!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                }
                if($i>=1 && !empty($buffer[3]) && $country!='FR')
                {
                    $item_name=isset($buffer[0])?$this->db->escape($buffer[0]):'';
                    $item_description=isset($buffer[1])?$this->db->escape($buffer[1]):'';
                    $listing_id=isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $seller_sku=isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $price=isset($buffer[4])?$this->db->escape($buffer[4]):'';
                    $quantity=isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $open_date=isset($buffer[6])?$this->db->escape($buffer[6]):'';
                    $image_url=isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $item_is_marketplace=isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $product_id_type=isset($buffer[9])?$this->db->escape($buffer[9]):'';
                    $zshop_shipping_fee=isset($buffer[10])?$this->db->escape($buffer[10]):'';
                    $item_note=isset($buffer[11])?$this->db->escape($buffer[11]):'';
                    $item_condition=isset($buffer[12])?$this->db->escape($buffer[12]):'';
                    $zshop_category1=isset($buffer[13])?$this->db->escape($buffer[13]):'';
                    $zshop_browse_path=isset($buffer[14])?$this->db->escape($buffer[14]):'';
                    $zshop_storefront_feature=isset($buffer[15])?$this->db->escape($buffer[15]):'';
                    $asin1=isset($buffer[16])?$this->db->escape($buffer[16]):'';
                    $asin2=isset($buffer[17])?$this->db->escape($buffer[17]):'';
                    $asin3=isset($buffer[18])?$this->db->escape($buffer[18]):'';
                    $will_ship_internationally=isset($buffer[19])?$this->db->escape($buffer[19]):'';
                    $expedited_shipping=isset($buffer[20])?$this->db->escape($buffer[20]):'';
                    $zshop_boldface=isset($buffer[21])?$this->db->escape($buffer[21]):'';
                    $product_id=isset($buffer[22])?$this->db->escape($buffer[22]):'';
                    $bid_for_featured_placement=isset($buffer[23])?$this->db->escape($buffer[23]):'';
                    $add_delete=isset($buffer[24])?$this->db->escape($buffer[24]):'';
                    $pending_quantity=isset($buffer[25])?$this->db->escape($buffer[25]):'';
                    $fulfillment_channel=isset($buffer[26])?$this->db->escape($buffer[26]):'';
                    $merchant_shipping_group=isset($buffer[27])?$this->db->escape($buffer[27]):'';

                    $bulk_data[]="(".$item_name.",".$item_description.",".$listing_id.",".$seller_sku.",".$price.",".$quantity.",".$open_date.",".$image_url.",".$item_is_marketplace.",".$product_id_type.",".$zshop_shipping_fee.",".$item_note.",".$item_condition.",".$zshop_category1.",".$zshop_browse_path.",".$zshop_storefront_feature.",".$asin1.",".$asin2.",".$asin3.",".$will_ship_internationally.",".$expedited_shipping.",".$zshop_boldface.",".$product_id.",".$bid_for_featured_placement.",".$add_delete.",".$pending_quantity.",".$fulfillment_channel.",".$merchant_shipping_group.",'".$country."','".$user_id."')";
                }
                elseif($i>=1 && !empty($buffer[3]) && $country='FR')
                {
                    $item_name=isset($buffer[0])?$this->db->escape($buffer[0]):'';
                    $listing_id=isset($buffer[1])?$this->db->escape($buffer[1]):'';
                    $seller_sku=isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $price=isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $quantity=isset($buffer[4])?$this->db->escape($buffer[4]):'';
                    $open_date=isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $product_id_type=isset($buffer[6])?$this->db->escape($buffer[6]):'';
                    $item_note=isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $item_condition=isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $will_ship_internationally=isset($buffer[9])?$this->db->escape($buffer[9]):'';
                    $expedited_shipping=isset($buffer[10])?$this->db->escape($buffer[10]):'';
                    $product_id=isset($buffer[11])?$this->db->escape($buffer[11]):'';
                    $pending_quantity=isset($buffer[12])?$this->db->escape($buffer[12]):'';
                    $fulfillment_channel=isset($buffer[13])?$this->db->escape($buffer[13]):'';
                    $merchant_shipping_group=isset($buffer[14])?$this->db->escape($buffer[14]):'';
                    $item_description="''";
                    $image_url="''";
                    $item_is_marketplace="''";
                    $zshop_shipping_fee="''";
                    $zshop_category1="''";
                    $zshop_browse_path="''";
                    $zshop_storefront_feature="''";
                    $asin1="''";
                    $asin2="''";
                    $asin3="''";
                    $zshop_boldface="''";
                    $bid_for_featured_placement="''";
                    $add_delete="''";
                    $bulk_data[]="(".$item_name.",".$item_description.",".$listing_id.",".$seller_sku.",".$price.",".$quantity.",".$open_date.",".$image_url.",".$item_is_marketplace.",".$product_id_type.",".$zshop_shipping_fee.",".$item_note.",".$item_condition.",".$zshop_category1.",".$zshop_browse_path.",".$zshop_storefront_feature.",".$asin1.",".$asin2.",".$asin3.",".$will_ship_internationally.",".$expedited_shipping.",".$zshop_boldface.",".$product_id.",".$bid_for_featured_placement.",".$add_delete.",".$pending_quantity.",".$fulfillment_channel.",".$merchant_shipping_group.",'".$country."','".$user_id."')";
                }
    		   //print_r($bulk_data);
                if(isset($bulk_data) && count($bulk_data)>=500)
                {
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT INTO `inactive_inventory_data` (item_name,item_description,listing_id,seller_sku,price,quantity,open_date,image_url,item_is_marketplace,product_id_type,zshop_shipping_fee,item_note,item_condition,zshop_category1,zshop_browse_path,zshop_storefront_feature,asin1,asin2,asin3,will_ship_internationally,expedited_shipping,zshop_boldface,product_id,bid_for_featured_placement,add_delete,pending_quantity,fulfillment_channel,merchant_shipping_group,country,added_by)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    item_name=VALUES(item_name),item_description=VALUES(item_description),listing_id=VALUES(listing_id),seller_sku=VALUES(seller_sku),price=VALUES(price),quantity=VALUES(quantity),open_date=VALUES(open_date),image_url=VALUES(image_url),item_is_marketplace=VALUES(item_is_marketplace),product_id_type=VALUES(product_id_type),zshop_shipping_fee=VALUES(zshop_shipping_fee),item_note=VALUES(item_note),item_condition=VALUES(item_condition),zshop_category1=VALUES(zshop_category1),zshop_browse_path=VALUES(zshop_browse_path),zshop_storefront_feature=VALUES(zshop_storefront_feature),asin1=VALUES(asin1),asin2=VALUES(asin2),asin3=VALUES(asin3),will_ship_internationally=VALUES(will_ship_internationally),expedited_shipping=VALUES(expedited_shipping),zshop_boldface=VALUES(zshop_boldface),product_id=VALUES(product_id),bid_for_featured_placement=VALUES(bid_for_featured_placement),add_delete=VALUES(add_delete),pending_quantity=VALUES(pending_quantity),fulfillment_channel=VALUES(fulfillment_channel),merchant_shipping_group=VALUES(merchant_shipping_group),country=VALUES(country),added_by=VALUES(added_by);";

                    $checkAddUpdateData = $this->db->query($qi);
                    if (!$checkAddUpdateData) {
                        $error = get_instance()->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $error;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }//while ends here
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {
                $quer=implode(',',$bulk_data);
                $qi="INSERT INTO `inactive_inventory_data` (item_name,item_description,listing_id,seller_sku,price,quantity,open_date,image_url,item_is_marketplace,product_id_type,zshop_shipping_fee,item_note,item_condition,zshop_category1,zshop_browse_path,zshop_storefront_feature,asin1,asin2,asin3,will_ship_internationally,expedited_shipping,zshop_boldface,product_id,bid_for_featured_placement,add_delete,pending_quantity,fulfillment_channel,merchant_shipping_group,country,added_by)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                item_name=VALUES(item_name),item_description=VALUES(item_description),listing_id=VALUES(listing_id),seller_sku=VALUES(seller_sku),price=VALUES(price),quantity=VALUES(quantity),open_date=VALUES(open_date),image_url=VALUES(image_url),item_is_marketplace=VALUES(item_is_marketplace),product_id_type=VALUES(product_id_type),zshop_shipping_fee=VALUES(zshop_shipping_fee),item_note=VALUES(item_note),item_condition=VALUES(item_condition),zshop_category1=VALUES(zshop_category1),zshop_browse_path=VALUES(zshop_browse_path),zshop_storefront_feature=VALUES(zshop_storefront_feature),asin1=VALUES(asin1),asin2=VALUES(asin2),asin3=VALUES(asin3),will_ship_internationally=VALUES(will_ship_internationally),expedited_shipping=VALUES(expedited_shipping),zshop_boldface=VALUES(zshop_boldface),product_id=VALUES(product_id),bid_for_featured_placement=VALUES(bid_for_featured_placement),add_delete=VALUES(add_delete),pending_quantity=VALUES(pending_quantity),fulfillment_channel=VALUES(fulfillment_channel),merchant_shipping_group=VALUES(merchant_shipping_group),country=VALUES(country),added_by=VALUES(added_by);";
                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}

public function process_order_update_data($user_id,$report_file,$country,$request_type)
{
    // echo "<pre>";
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_orders_update_list";
    try {
        $dataBaseColumnName = array(
                                        'amazon-order-id' => 'order_id',
                                        'merchant-order-id' => 'mer_order_id',
                                        'purchase-date' => 'po_date',
                                        'last-updated-date' => 'last_update_date',
                                        'order-status' => 'ord_status',
                                        'fulfillment-channel' => 'fulfillment',
                                        'sales-channel' => 'sales_channel',
                                        'ship-service-level' => 'ship_service',
                                        'product-name' => 'title',
                                        'sku' => 'ord_sku',
                                        'asin' => 'asin',
                                        'item-status' => 'itm_status',
                                        'quantity' => 'qty',
                                        'currency' => 'currency',
                                        'item-price' => 'itm_price',
                                        'item-tax' => 'itm_tax',
                                        'shipping-price' => 'ship_price',
                                        'shipping-tax' => 'ship_tax',
                                        'gift-wrap-price' => 'gift_price',
                                        'gift-wrap-tax' => 'gift_tax',
                                        'item-promotion-discount' => 'itm_promo_discount',
                                        'ship-promotion-discount' => 'itm_ship_discount',
                                        'ship-city' => 'ship_city',
                                        'ship-state' => 'ship_state',
                                        'ship-postal-code' => 'ship_post',
                                        'ship-country' => 'ship_country',
                                        'promotion-ids' => 'promo_id'
                                    );
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            $csvColumnNames = array();
            $repOrdersUpdateListBulkQueryData = array();
            while(!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if ($i===0) {
                    $csvColumnNames = $buffer;
                }

                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $insertData = array();
                    foreach ($csvColumnNames as $key => $csvColumnName) {
                        if (in_array($csvColumnName, $csvColumnNames) && array_key_exists($csvColumnName,$dataBaseColumnName)) {
                            $getMatchKey = array_search($csvColumnName, $csvColumnNames);
                            if (isset($dataBaseColumnName[$csvColumnName])) {
                                $insertData[$dataBaseColumnName[$csvColumnName]] = $buffer[$getMatchKey];
                            }
                        }
                    }
                    if (in_array('purchase-date', $csvColumnNames)) {
                        $getMatchDateKey = array_search('purchase-date', $csvColumnNames);
                        $insertData['dev_purchase_date'] = $buffer[$getMatchDateKey];
                    }

                    if (isset($insertData['po_date'])) {
                        $insertData['po_date'] = getTimeZoneDateTime($insertData['po_date'], $insertData['sales_channel']);
                    }

                    if (isset($insertData['last_update_date'])) {
                        $insertData['last_update_date'] = getTimeZoneDateTime($insertData['last_update_date'], $insertData['sales_channel']);
                    }

                    // $insertData['user_id'] = $user_id;

                    $repOrdersUpdateList_order_id           = (isset($insertData["order_id"]) && "" != trim($insertData["order_id"])) ? $this->db->escape($insertData["order_id"]) : $this->db->escape('');
                    $repOrdersUpdateList_mer_order_id       = (isset($insertData["mer_order_id"]) && "" != trim($insertData["mer_order_id"])) ? $this->db->escape($insertData["mer_order_id"]) : $this->db->escape('');
                    $repOrdersUpdateList_po_date            = (isset($insertData["po_date"]) && "" != trim($insertData["po_date"])) ? $this->db->escape($insertData["po_date"]) : $this->db->escape('');
                    $repOrdersUpdateList_last_update_date   = (isset($insertData["last_update_date"]) && "" != trim($insertData["last_update_date"])) ? $this->db->escape($insertData["last_update_date"]) : $this->db->escape('');
                    $repOrdersUpdateList_ord_status         = (isset($insertData["ord_status"]) && "" != trim($insertData["ord_status"])) ? $this->db->escape($insertData["ord_status"]) : $this->db->escape('');
                    $repOrdersUpdateList_fulfillment        = (isset($insertData["fulfillment"]) && "" != trim($insertData["fulfillment"])) ? $this->db->escape($insertData["fulfillment"]) : $this->db->escape('');
                    $repOrdersUpdateList_sales_channel      = (isset($insertData["sales_channel"]) && "" != trim($insertData["sales_channel"])) ? $this->db->escape($insertData["sales_channel"]) : $this->db->escape('');
                    $repOrdersUpdateList_itm_status         = (isset($insertData["itm_status"]) && "" != trim($insertData["itm_status"])) ? $this->db->escape($insertData["itm_status"]) : $this->db->escape('');
                    $repOrdersUpdateList_ord_sku            = (isset($insertData["ord_sku"]) && "" != trim($insertData["ord_sku"])) ? $this->db->escape($insertData["ord_sku"]) : $this->db->escape('');
                    $repOrdersUpdateList_asin               = (isset($insertData["asin"]) && "" != trim($insertData["asin"])) ? $this->db->escape($insertData["asin"]) : $this->db->escape('');
                    $repOrdersUpdateList_title              = (isset($insertData["title"]) && "" != trim($insertData["title"])) ? $this->db->escape($insertData["title"]) : $this->db->escape('');
                    $repOrdersUpdateList_ship_service       = (isset($insertData["ship_service"]) && "" != trim($insertData["ship_service"])) ? $this->db->escape($insertData["ship_service"]) : $this->db->escape('');
                    $repOrdersUpdateList_qty                = (isset($insertData["qty"]) && "" != trim($insertData["qty"])) ? $this->db->escape($insertData["qty"]) : $this->db->escape('');
                    $repOrdersUpdateList_currency           = (isset($insertData["currency"]) && "" != trim($insertData["currency"])) ? $this->db->escape($insertData["currency"]) : $this->db->escape('');
                    $repOrdersUpdateList_itm_price          = (isset($insertData["itm_price"]) && "" != trim($insertData["itm_price"])) ? $this->db->escape($insertData["itm_price"]) : $this->db->escape('0.00');
                    $repOrdersUpdateList_itm_tax            = (isset($insertData["itm_tax"]) && "" != trim($insertData["itm_tax"])) ? $this->db->escape($insertData["itm_tax"]) : $this->db->escape('0.00');
                    $repOrdersUpdateList_ship_price         = (isset($insertData["ship_price"]) && "" != trim($insertData["ship_price"])) ? $this->db->escape($insertData["ship_price"]) : $this->db->escape('0.00');
                    $repOrdersUpdateList_ship_tax           = (isset($insertData["ship_tax"]) && "" != trim($insertData["ship_tax"])) ? $this->db->escape($insertData["ship_tax"]) : $this->db->escape('0.00');
                    $repOrdersUpdateList_gift_price         = (isset($insertData["gift_price"]) && "" != trim($insertData["gift_price"])) ? $this->db->escape($insertData["gift_price"]) : $this->db->escape('0.00');
                    $repOrdersUpdateList_gift_tax           = (isset($insertData["gift_tax"]) && "" != trim($insertData["gift_tax"])) ? $this->db->escape($insertData["gift_tax"]) : $this->db->escape('0.00');
                    $repOrdersUpdateList_itm_promo_discount = (isset($insertData["itm_promo_discount"]) && "" != trim($insertData["itm_promo_discount"])) ? $this->db->escape($insertData["itm_promo_discount"]) : $this->db->escape('0.00');
                    $repOrdersUpdateList_itm_ship_discount  = (isset($insertData["itm_ship_discount"]) && "" != trim($insertData["itm_ship_discount"])) ? $this->db->escape($insertData["itm_ship_discount"]) : $this->db->escape('0.00');
                    $repOrdersUpdateList_ship_city          = (isset($insertData["ship_city"]) && "" != trim($insertData["ship_city"])) ? $this->db->escape($insertData["ship_city"]) : $this->db->escape('');
                    $repOrdersUpdateList_ship_state         = (isset($insertData["ship_state"]) && "" != trim($insertData["ship_state"])) ? $this->db->escape($insertData["ship_state"]) : $this->db->escape('');
                    $repOrdersUpdateList_ship_post          = (isset($insertData["ship_post"]) && "" != trim($insertData["ship_post"])) ? $this->db->escape($insertData["ship_post"]) : $this->db->escape('');
                    $repOrdersUpdateList_ship_country       = (isset($insertData["ship_country"]) && "" != trim($insertData["ship_country"])) ? $this->db->escape($insertData["ship_country"]) : $this->db->escape('');
                    $repOrdersUpdateList_promo_id           = (isset($insertData["promo_id"]) && "" != trim($insertData["promo_id"])) ? $this->db->escape($insertData["promo_id"]) : $this->db->escape('');
                    $repOrdersUpdateList_user_id            = $this->db->escape($user_id);
                    $repOrdersUpdateList_dev_purchase_date  = (isset($insertData["dev_purchase_date"]) && "" != trim($insertData["dev_purchase_date"])) ? $this->db->escape($insertData["dev_purchase_date"]) : $this->db->escape('');

                    $repOrdersUpdateListBulkQueryData[] = "({$repOrdersUpdateList_order_id},{$repOrdersUpdateList_mer_order_id},{$repOrdersUpdateList_po_date},{$repOrdersUpdateList_last_update_date},{$repOrdersUpdateList_ord_status},{$repOrdersUpdateList_fulfillment},{$repOrdersUpdateList_sales_channel},{$repOrdersUpdateList_itm_status},{$repOrdersUpdateList_ord_sku},{$repOrdersUpdateList_asin},{$repOrdersUpdateList_title},{$repOrdersUpdateList_ship_service},{$repOrdersUpdateList_qty},{$repOrdersUpdateList_currency}, {$repOrdersUpdateList_itm_price},{$repOrdersUpdateList_itm_tax},{$repOrdersUpdateList_ship_price},{$repOrdersUpdateList_ship_tax},{$repOrdersUpdateList_gift_price},{$repOrdersUpdateList_gift_tax},{$repOrdersUpdateList_itm_promo_discount},{$repOrdersUpdateList_itm_ship_discount},{$repOrdersUpdateList_ship_city},{$repOrdersUpdateList_ship_state},{$repOrdersUpdateList_ship_post},{$repOrdersUpdateList_ship_country},{$repOrdersUpdateList_promo_id},{$repOrdersUpdateList_user_id},{$repOrdersUpdateList_dev_purchase_date})";

                    if (!empty($repOrdersUpdateListBulkQueryData) && count($repOrdersUpdateListBulkQueryData) > 200) {
                        $repOrdersUpdateListBulkQueryData_implode   = implode(',',$repOrdersUpdateListBulkQueryData);
                        $repOrdersUpdateListBulkQueryData_sql_query = "INSERT INTO `rep_orders_update_list` (`order_id`, `mer_order_id`, `po_date`, `last_update_date`, `ord_status`, `fulfillment`, `sales_channel`, `itm_status`, `ord_sku`, `asin`, `title`, `ship_service`, `qty`, `currency`, `itm_price`, `itm_tax`, `ship_price`, `ship_tax`, `gift_price`, `gift_tax`, `itm_promo_discount`, `itm_ship_discount`, `ship_city`, `ship_state`, `ship_post`, `ship_country`, `promo_id`, `user_id`, `dev_purchase_date`)
                                                                      VALUES
                                                                      $repOrdersUpdateListBulkQueryData_implode
                                                                      ON DUPLICATE KEY
                                                                      UPDATE
                                                                      order_id=VALUES(order_id), mer_order_id=VALUES(mer_order_id), po_date=VALUES(po_date), last_update_date=VALUES(last_update_date), ord_status=VALUES(ord_status), fulfillment=VALUES(fulfillment), sales_channel=VALUES(sales_channel), itm_status=VALUES(itm_status), ord_sku=VALUES(ord_sku), asin=VALUES(asin), title=VALUES(title), ship_service=VALUES(ship_service), qty=VALUES(qty), currency=VALUES(currency), itm_price=VALUES(itm_price), itm_tax=VALUES(itm_tax), ship_price=VALUES(ship_price), ship_tax=VALUES(ship_tax), gift_price=VALUES(gift_price), gift_tax=VALUES(gift_tax), itm_promo_discount=VALUES(itm_promo_discount), itm_ship_discount=VALUES(itm_ship_discount), ship_city=VALUES(ship_city), ship_state=VALUES(ship_state), ship_post=VALUES(ship_post), ship_country=VALUES(ship_country), promo_id=VALUES(promo_id), user_id=VALUES(user_id), dev_purchase_date=VALUES(dev_purchase_date)";

                        $check_repOrdersUpdateListBulkQueryData_sql_query = $this->db->query($repOrdersUpdateListBulkQueryData_sql_query);
                        if (!$check_repOrdersUpdateListBulkQueryData_sql_query) {
                            $getError = $this->db->error();
                            $responseData['response'] = 2;
                            $responseData['msg']      = $getError;
                            $responseData['fileName'] = $report_file;
                            return $responseData;
                            break;
                        }
                        $repOrdersUpdateListBulkQueryData = array();
                    }
                    // print_r($insertData);
                    // die();
                }
                $i++;
            }
            fclose($fp);

            if (!empty($repOrdersUpdateListBulkQueryData) && count($repOrdersUpdateListBulkQueryData) > 0) {
                $repOrdersUpdateListBulkQueryData_implode   = implode(',',$repOrdersUpdateListBulkQueryData);
                $repOrdersUpdateListBulkQueryData_sql_query = "INSERT INTO `rep_orders_update_list` (`order_id`, `mer_order_id`, `po_date`, `last_update_date`, `ord_status`, `fulfillment`, `sales_channel`, `itm_status`, `ord_sku`, `asin`, `title`, `ship_service`, `qty`, `currency`, `itm_price`, `itm_tax`, `ship_price`, `ship_tax`, `gift_price`, `gift_tax`, `itm_promo_discount`, `itm_ship_discount`, `ship_city`, `ship_state`, `ship_post`, `ship_country`, `promo_id`, `user_id`, `dev_purchase_date`)
                                                              VALUES
                                                              $repOrdersUpdateListBulkQueryData_implode
                                                              ON DUPLICATE KEY
                                                              UPDATE
                                                              order_id=VALUES(order_id), mer_order_id=VALUES(mer_order_id), po_date=VALUES(po_date), last_update_date=VALUES(last_update_date), ord_status=VALUES(ord_status), fulfillment=VALUES(fulfillment), sales_channel=VALUES(sales_channel), itm_status=VALUES(itm_status), ord_sku=VALUES(ord_sku), asin=VALUES(asin), title=VALUES(title), ship_service=VALUES(ship_service), qty=VALUES(qty), currency=VALUES(currency), itm_price=VALUES(itm_price), itm_tax=VALUES(itm_tax), ship_price=VALUES(ship_price), ship_tax=VALUES(ship_tax), gift_price=VALUES(gift_price), gift_tax=VALUES(gift_tax), itm_promo_discount=VALUES(itm_promo_discount), itm_ship_discount=VALUES(itm_ship_discount), ship_city=VALUES(ship_city), ship_state=VALUES(ship_state), ship_post=VALUES(ship_post), ship_country=VALUES(ship_country), promo_id=VALUES(promo_id), user_id=VALUES(user_id), dev_purchase_date=VALUES(dev_purchase_date)";

                $check_repOrdersUpdateListBulkQueryData_sql_query = $this->db->query($repOrdersUpdateListBulkQueryData_sql_query);
                if (!$check_repOrdersUpdateListBulkQueryData_sql_query) {
                    $getError = $this->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $getError;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
            }
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}
public function process_vat_tax_data($user_id,$report_file,$country,$request_type)
{
    $fp=fopen($report_file,'r');
    if ($fp)
    {

       $i=0;
       while (!feof($fp))
       {
        $buffer = fgetcsv($fp);
			//print_r($buffer);

        if($i>=1 && !empty($buffer[4]))
        {
         $market_id= isset($buffer[0])?$this->db->escape($buffer[0]):'';
			   //print_r($market_id);
         $merchant_id= isset($buffer[1])?$this->db->escape($buffer[1]):'';
         $order_date=isset($buffer[2])?$this->db->escape($buffer[2]):'';
         $or_date = substr($order_date, 1, 12);
         $ord_date=date("Y-m-d", strtotime($or_date));
               //print_r($ord_date);
               //die();
         $trans_type=isset($buffer[3])?$this->db->escape($buffer[3]):'';
         $order_id=isset($buffer[4])?$this->db->escape($buffer[4]):'';
               // $open_date=$this->db->escape(date('Y-m-d H:i:s',strtotime($open_date)));
         $shipping_date=isset($buffer[5])?$this->db->escape($buffer[5]):'';
         $sh_date = substr($shipping_date, 1, 12);
         $ship_date=date("Y-m-d", strtotime($sh_date));
         $ship_id=isset($buffer[6])?$this->db->escape($buffer[6]):'';
         $trans_id=isset($buffer[7])?$this->db->escape($buffer[7]):'';
         $asin=isset($buffer[8])?$this->db->escape($buffer[8]):'';
         $sku=isset($buffer[9])?$this->db->escape($buffer[9]):'';
         $qty=isset($buffer[10])?$this->db->escape($buffer[10]):'';
         $tax_calcul_date=isset($buffer[11])?$this->db->escape($buffer[11]):'';
         $tx_date = substr($tax_calcul_date, 1, 12);
         $tax_cal_date=date("Y-m-d", strtotime($tx_date));
         $tax_rate=isset($buffer[12])?$this->db->escape($buffer[12]):'';
         $tax_code=isset($buffer[13])?$this->db->escape($buffer[13]):'';
         $currency=isset($buffer[14])?$this->db->escape($buffer[14]):'';
         $tax_type=isset($buffer[15])?$this->db->escape($buffer[15]):'';
         $tax_cal_reason=isset($buffer[16])?$this->db->escape($buffer[16]):'';
         $tax_addr_role=isset($buffer[17])?$this->db->escape($buffer[17]):'';
         $jurisdiction_level=isset($buffer[18])?$this->db->escape($buffer[18]):'';
         $jurisdiction_name=isset($buffer[19])?$this->db->escape($buffer[19]):'';
         $our_price_tax_inclusive=isset($buffer[20])?$this->db->escape($buffer[20]):'';
         $our_price_tax_amount=isset($buffer[21])?$this->db->escape($buffer[21]):'';
         $our_price_tax_exclusive=isset($buffer[22])?$this->db->escape($buffer[22]):'';
         $our_promo_amount_inclusive=isset($buffer[23])?$this->db->escape($buffer[23]):'';
         $our_tax_promo_amount=isset($buffer[24])?$this->db->escape($buffer[24]):'';
         $our_promo_amount_exclusive=isset($buffer[25])?$this->db->escape($buffer[25]):'';
         $ship_tax_inclusive=isset($buffer[26])?$this->db->escape($buffer[26]):'';
         $ship_tax=isset($buffer[27])?$this->db->escape($buffer[27]):'';
         $ship_tax_exclusive=isset($buffer[28])?$this->db->escape($buffer[28]):'';
         $ship_tax_promo_inclusive=isset($buffer[29])?$this->db->escape($buffer[29]):'';
         $ship_tax_promo=isset($buffer[30])?$this->db->escape($buffer[30]):'';
         $ship_tax_promo_exclusive=isset($buffer[31])?$this->db->escape($buffer[31]):'';
         $gift_tax_inclusive=isset($buffer[32])?$this->db->escape($buffer[32]):'';
         $gift_tax=isset($buffer[33])?$this->db->escape($buffer[33]):'';
         $gift_tax_exclusive=isset($buffer[34])?$this->db->escape($buffer[34]):'';
         $gift_tax_promo_inclusive=isset($buffer[35])?$this->db->escape($buffer[35]):'';
         $gift_tax_promo=isset($buffer[36])?$this->db->escape($buffer[36]):'';
         $gift_tax_promo_exclusive=isset($buffer[37])?$this->db->escape($buffer[37]):'';
         $sell_tax_reg=isset($buffer[38])?$this->db->escape($buffer[38]):'';
         $sell_tax_reg_jud=isset($buffer[39])?$this->db->escape($buffer[39]):'';
         $buy_tax_reg=isset($buffer[40])?$this->db->escape($buffer[40]):'';
         $buy_tax_reg_jud=isset($buffer[41])?$this->db->escape($buffer[41]):'';
         $buy_tax_reg_type=isset($buffer[42])?$this->db->escape($buffer[42]):'';
         $inv_curr_code=isset($buffer[43])?$this->db->escape($buffer[43]):'';
         $inv_ex_rate=isset($buffer[44])?$this->db->escape($buffer[44]):'';
         $inv_ex_date=isset($buffer[45])?$this->db->escape($buffer[45]):'';
         $con_tax_amt=isset($buffer[46])?$this->db->escape($buffer[46]):'';
         $vat_inv_no=isset($buffer[47])?$this->db->escape($buffer[47]):'';
         $inv_url=isset($buffer[48])?$this->db->escape($buffer[48]):'';
         $exp_out_eu=isset($buffer[49])?$this->db->escape($buffer[49]):'';
         $ship_from_city=isset($buffer[50])?$this->db->escape($buffer[50]):'';
         $ship_from_state=isset($buffer[51])?$this->db->escape($buffer[51]):'';
         $ship_from_country=isset($buffer[52])?$this->db->escape($buffer[52]):'';
         $ship_from_postal=isset($buffer[53])?$this->db->escape($buffer[53]):'';
         $ship_from_tax_loca=isset($buffer[54])?$this->db->escape($buffer[54]):'';
         $ship_to_city=isset($buffer[55])?$this->db->escape($buffer[55]):'';
         $ship_to_state=isset($buffer[56])?$this->db->escape($buffer[56]):'';
         $ship_to_country=isset($buffer[57])?$this->db->escape($buffer[57]):'';
         $ship_to_postal=isset($buffer[58])?$this->db->escape($buffer[58]):'';
         $ship_to_tax_loca=isset($buffer[59])?$this->db->escape($buffer[59]):'';


         $bulk_data[]="(".$market_id.",".$merchant_id.",'".$ord_date."',".$trans_type.",".$order_id.",'".$ship_date."',".$ship_id.",".$trans_id.",".$asin.",".$sku.",".$qty.",'".$tax_cal_date."',".$tax_rate.",".$tax_code.",".$currency.",".$tax_type.",".$tax_cal_reason.",".$tax_addr_role.",".$jurisdiction_level.",".$jurisdiction_name.",".$our_price_tax_inclusive.",".$our_price_tax_amount.",".$our_price_tax_exclusive.",".$our_promo_amount_inclusive.",".$our_tax_promo_amount.",".$our_promo_amount_exclusive.",".$ship_tax_inclusive.",".$ship_tax.",".$ship_tax_exclusive.",".$ship_tax_promo_inclusive.",".$ship_tax_promo.",".$ship_tax_promo_exclusive.",".$gift_tax_inclusive.",".$gift_tax.",".$gift_tax_exclusive.",".$gift_tax_promo_inclusive.",".$gift_tax_promo.",".$gift_tax_promo_exclusive.",".$sell_tax_reg.",".$sell_tax_reg_jud.",".$buy_tax_reg.",".$buy_tax_reg_jud.",".$buy_tax_reg_type.",".$inv_curr_code.",".$inv_ex_rate.",".$inv_ex_date.",".$con_tax_amt.",".$vat_inv_no.",".$inv_url.",".$exp_out_eu.",".$ship_from_city.",".$ship_from_state.",".$ship_from_country.",".$ship_from_postal.",".$ship_from_tax_loca.",".$ship_to_city.",".$ship_to_state.",".$ship_to_country.",".$ship_to_postal.",".$ship_to_tax_loca.",".$user_id.")";

     }

     if(isset($bulk_data) && count($bulk_data)>=500)
     {
      $quer=implode(',',$bulk_data);
      $qi="INSERT INTO `rep_sc_vat_tax` (market_id,merchant_id,ord_date,trans_type,order_id,ship_date,ship_id,trans_id,asin,sku,qty,tax_cal_date,tax_rate,tax_code,currency,tax_type,tax_cal_rsn_code,tax_addr_role,juri_level,juri_country,our_price_tax_inclusive,our_price_tax,our_price_tax_exclusive,our_promo_amount_inclusive,our_promo_amount,our_promo_amount_exclusive,ship_tax_inclusive,ship_tax,ship_tax_exclusive,ship_tax_promo_inclusive,ship_tax_promo,ship_tax_promo_exclusive,gift_tax_inclusive,gift_tax,gift_tax_exclusive,gift_tax_promo_inclusive,gift_tax_promo,gift_tax_promo_exclusive,sell_tax_reg,sell_tax_reg_jud,buy_tax_reg,buy_tax_reg_jud,buy_tax_reg_type,inv_curr_code,inv_ex_rate,inv_ex_date,con_tax_amt,vat_inv_no,inv_url,exp_out_eu,ship_from_city,ship_from_state,ship_from_country,ship_from_postal,ship_from_tax_loca,ship_to_city,ship_to_state,ship_to_country,ship_to_postal,ship_to_tax_loca,user_id)VALUES
      $quer
      ON DUPLICATE KEY
      UPDATE
      market_id=VALUES(market_id),merchant_id=VALUES(merchant_id),ord_date=VALUES(ord_date),trans_type=VALUES(trans_type),order_id=VALUES(order_id),ship_date=VALUES(ship_date),ship_id=VALUES(ship_id),trans_id=VALUES(trans_id),asin=VALUES(asin),sku=VALUES(sku),qty=VALUES(qty),tax_cal_date=VALUES(tax_cal_date),tax_rate=VALUES(tax_rate),
      tax_code=VALUES(tax_code),currency=VALUES(currency),tax_type=VALUES(tax_type),tax_cal_rsn_code=VALUES(tax_cal_rsn_code),tax_addr_role=VALUES(tax_addr_role),juri_level=VALUES(juri_level),juri_country=VALUES(juri_country),our_price_tax_inclusive=VALUES(our_price_tax_inclusive),our_price_tax=VALUES(our_price_tax),our_price_tax_exclusive=VALUES(our_price_tax_exclusive),our_promo_amount_inclusive=VALUES(our_promo_amount_inclusive),
      our_promo_amount=VALUES(our_promo_amount),our_promo_amount_exclusive=VALUES(our_promo_amount_exclusive),ship_tax_inclusive=VALUES(ship_tax_inclusive),ship_tax=VALUES(ship_tax),ship_tax_exclusive=VALUES(ship_tax_exclusive),ship_tax_promo_inclusive=VALUES(ship_tax_promo_inclusive), ship_tax_promo=VALUES(ship_tax_promo),ship_tax_promo_exclusive=VALUES(ship_tax_promo_exclusive),gift_tax_inclusive=VALUES(gift_tax_inclusive),
      gift_tax=VALUES(gift_tax),gift_tax_exclusive=VALUES(gift_tax_exclusive),gift_tax_promo_inclusive=VALUES(gift_tax_promo_inclusive),gift_tax_promo=VALUES(gift_tax_promo),gift_tax_promo_exclusive=VALUES(gift_tax_promo_exclusive),sell_tax_reg=VALUES(sell_tax_reg),sell_tax_reg_jud=VALUES(sell_tax_reg_jud),buy_tax_reg=VALUES(buy_tax_reg),buy_tax_reg_jud=VALUES(buy_tax_reg_jud),buy_tax_reg_type=VALUES(buy_tax_reg_type),inv_curr_code=VALUES(inv_curr_code),inv_ex_rate=VALUES(inv_ex_rate),
      inv_ex_date=VALUES(inv_ex_date),con_tax_amt=VALUES(con_tax_amt),vat_inv_no=VALUES(vat_inv_no),inv_url=VALUES(inv_url),exp_out_eu=VALUES(exp_out_eu),ship_from_city=VALUES(ship_from_city),ship_from_state=VALUES(ship_from_state),ship_from_country=VALUES(ship_from_country),ship_from_postal=VALUES(ship_from_postal),ship_from_tax_loca=VALUES(ship_from_tax_loca),ship_to_city=VALUES(ship_to_city),ship_to_state=VALUES(ship_to_state),ship_to_country=VALUES(ship_to_country),ship_to_postal=VALUES(ship_to_postal),ship_to_tax_loca=VALUES(ship_to_tax_loca),user_id=VALUES(user_id);";
      $this->db->query($qi);
			 // print_r($qi);
      unset($bulk_data);
      unset($quer);
  }
  $i++;
    }//while ends here
    if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
    {
      $quer=implode(',',$bulk_data);
      $qi="INSERT INTO `rep_sc_vat_tax` (market_id,merchant_id,ord_date,trans_type,order_id,ship_date,ship_id,trans_id,asin,sku,qty,tax_cal_date,tax_rate,tax_code,currency,tax_type,tax_cal_rsn_code,tax_addr_role,juri_level,juri_country,our_price_tax_inclusive,our_price_tax,our_price_tax_exclusive,our_promo_amount_inclusive,our_promo_amount,our_promo_amount_exclusive,ship_tax_inclusive,ship_tax,ship_tax_exclusive,ship_tax_promo_inclusive,ship_tax_promo,ship_tax_promo_exclusive,gift_tax_inclusive,gift_tax,gift_tax_exclusive,gift_tax_promo_inclusive,gift_tax_promo,gift_tax_promo_exclusive,sell_tax_reg,sell_tax_reg_jud,buy_tax_reg,buy_tax_reg_jud,buy_tax_reg_type,inv_curr_code,inv_ex_rate,inv_ex_date,con_tax_amt,vat_inv_no,inv_url,exp_out_eu,ship_from_city,ship_from_state,ship_from_country,ship_from_postal,ship_from_tax_loca,ship_to_city,ship_to_state,ship_to_country,ship_to_postal,ship_to_tax_loca,user_id)VALUES
      $quer
      ON DUPLICATE KEY
      UPDATE
      market_id=VALUES(market_id),merchant_id=VALUES(merchant_id),ord_date=VALUES(ord_date),trans_type=VALUES(trans_type),order_id=VALUES(order_id),ship_date=VALUES(ship_date),ship_id=VALUES(ship_id),trans_id=VALUES(trans_id),asin=VALUES(asin),sku=VALUES(sku),qty=VALUES(qty),tax_cal_date=VALUES(tax_cal_date),tax_rate=VALUES(tax_rate),
      tax_code=VALUES(tax_code),currency=VALUES(currency),tax_type=VALUES(tax_type),tax_cal_rsn_code=VALUES(tax_cal_rsn_code),tax_addr_role=VALUES(tax_addr_role),juri_level=VALUES(juri_level),juri_country=VALUES(juri_country),our_price_tax_inclusive=VALUES(our_price_tax_inclusive),our_price_tax=VALUES(our_price_tax),our_price_tax_exclusive=VALUES(our_price_tax_exclusive),our_promo_amount_inclusive=VALUES(our_promo_amount_inclusive),
      our_promo_amount=VALUES(our_promo_amount),our_promo_amount_exclusive=VALUES(our_promo_amount_exclusive),ship_tax_inclusive=VALUES(ship_tax_inclusive),ship_tax=VALUES(ship_tax),ship_tax_exclusive=VALUES(ship_tax_exclusive),ship_tax_promo_inclusive=VALUES(ship_tax_promo_inclusive), ship_tax_promo=VALUES(ship_tax_promo),ship_tax_promo_exclusive=VALUES(ship_tax_promo_exclusive),gift_tax_inclusive=VALUES(gift_tax_inclusive),
      gift_tax=VALUES(gift_tax),gift_tax_exclusive=VALUES(gift_tax_exclusive),gift_tax_promo_inclusive=VALUES(gift_tax_promo_inclusive),gift_tax_promo=VALUES(gift_tax_promo),gift_tax_promo_exclusive=VALUES(gift_tax_promo_exclusive),sell_tax_reg=VALUES(sell_tax_reg),sell_tax_reg_jud=VALUES(sell_tax_reg_jud),buy_tax_reg=VALUES(buy_tax_reg),buy_tax_reg_jud=VALUES(buy_tax_reg_jud),buy_tax_reg_type=VALUES(buy_tax_reg_type),inv_curr_code=VALUES(inv_curr_code),inv_ex_rate=VALUES(inv_ex_rate),
      inv_ex_date=VALUES(inv_ex_date),con_tax_amt=VALUES(con_tax_amt),vat_inv_no=VALUES(vat_inv_no),inv_url=VALUES(inv_url),exp_out_eu=VALUES(exp_out_eu),ship_from_city=VALUES(ship_from_city),ship_from_state=VALUES(ship_from_state),ship_from_country=VALUES(ship_from_country),ship_from_postal=VALUES(ship_from_postal),ship_from_tax_loca=VALUES(ship_from_tax_loca),ship_to_city=VALUES(ship_to_city),ship_to_state=VALUES(ship_to_state),ship_to_country=VALUES(ship_to_country),ship_to_postal=VALUES(ship_to_postal),ship_to_tax_loca=VALUES(ship_to_tax_loca),user_id=VALUES(user_id);";
      $this->db->query($qi);
			 // print_r($qi);
      unset($bulk_data);
      unset($quer);
  }
  fclose($fp);

}
}

public function process_order_data_by_date($user_id,$report_file,$country,$request_type)
{
    // echo "<pre>";
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_orders_data_order_date_list";
    try {
        $dataBaseColumnName = array(
                                        'amazon-order-id' => 'order_id',
                                        'purchase-date' => 'po_date',
                                        'last-updated-date' => 'last_update_date',
                                        'order-status' => 'ord_status',
                                        'fulfillment-channel' => 'fulfillment',
                                        'sales-channel' => 'sales_channel',
                                        'ship-service-level' => 'ship_service',
                                        'product-name' => 'title',
                                        'sku' => 'ord_sku',
                                        'asin' => 'asin',
                                        'item-status' => 'itm_status',
                                        'quantity' => 'qty',
                                        'currency' => 'currency',
                                        'item-price' => 'itm_price',
                                        'item-tax' => 'itm_tax',
                                        'shipping-price' => 'ship_price',
                                        'shipping-tax' => 'ship_tax',
                                        'gift-wrap-price' => 'gift_price',
                                        'gift-wrap-tax' => 'gift_tax',
                                        'item-promotion-discount' => 'itm_promo_discount',
                                        'ship-promotion-discount' => 'itm_ship_discount',
                                        'ship-city' => 'ship_city',
                                        'ship-state' => 'ship_state',
                                        'ship-postal-code' => 'ship_post',
                                        'ship-country' => 'ship_country',
                                        'promotion-ids' => 'promo_id'
                                    );

        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            $csvColumnNames = array();
            $repOrdersDataOrderDateListBulkQueryData = array();
            while(!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if ($i===0) {
                    $csvColumnNames = $buffer;
                }

                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $insertData = array();
                    foreach ($csvColumnNames as $key => $csvColumnName) {
                        if (in_array($csvColumnName, $csvColumnNames) && array_key_exists($csvColumnName,$dataBaseColumnName)) {
                            $getMatchKey = array_search($csvColumnName, $csvColumnNames);
                            if (isset($dataBaseColumnName[$csvColumnName])) {
                                $insertData[$dataBaseColumnName[$csvColumnName]] = $buffer[$getMatchKey];
                            }
                        }
                    }
                    if (in_array('purchase-date', $csvColumnNames)) {
                        $getMatchDateKey = array_search('purchase-date', $csvColumnNames);
                        $insertData['dev_purchase_date'] = $buffer[$getMatchDateKey];
                    }

                    // print_r($insertData);
                    /* echo "<br>";
                    echo "=======================";
                    echo "<br>";*/

                    if (isset($insertData['po_date'])) {
                        $insertData['po_date'] = getTimeZoneDateTime($insertData['po_date'], $insertData['sales_channel']);
                    }

                    if (isset($insertData['last_update_date'])) {
                        $insertData['last_update_date'] = getTimeZoneDateTime($insertData['last_update_date'], $insertData['sales_channel']);
                    }

                    $insertData['user_id'] = $user_id;

                    // print_r($insertData);
                    // die;


                    $rep_order_date_list_order_id           = (isset($insertData["order_id"]) && "" != trim($insertData["order_id"])) ? $this->db->escape($insertData["order_id"]) : $this->db->escape('');
                    $rep_order_date_list_po_date            = (isset($insertData["po_date"]) && "" != trim($insertData["po_date"])) ? $this->db->escape($insertData["po_date"]) : $this->db->escape('');
                    $rep_order_date_list_last_update_date   = (isset($insertData["last_update_date"]) && "" != trim($insertData["last_update_date"])) ? $this->db->escape($insertData["last_update_date"]) : $this->db->escape('');
                    $rep_order_date_list_ord_status         = (isset($insertData["ord_status"]) && "" != trim($insertData["ord_status"])) ? $this->db->escape($insertData["ord_status"]) : $this->db->escape('');
                    $rep_order_date_list_fulfillment        = (isset($insertData["fulfillment"]) && "" != trim($insertData["fulfillment"])) ? $this->db->escape($insertData["fulfillment"]) : $this->db->escape('');
                    $rep_order_date_list_sales_channel      = (isset($insertData["sales_channel"]) && "" != trim($insertData["sales_channel"])) ? $this->db->escape($insertData["sales_channel"]) : $this->db->escape('');
                    $rep_order_date_list_itm_status         = (isset($insertData["itm_status"]) && "" != trim($insertData["itm_status"])) ? $this->db->escape($insertData["itm_status"]) : $this->db->escape('');
                    $rep_order_date_list_ord_sku            = (isset($insertData["ord_sku"]) && "" != trim($insertData["ord_sku"])) ? $this->db->escape($insertData["ord_sku"]) : $this->db->escape('');
                    $rep_order_date_list_asin               = (isset($insertData["asin"]) && "" != trim($insertData["asin"])) ? $this->db->escape($insertData["asin"]) : $this->db->escape('');
                    $rep_order_date_list_title              = (isset($insertData["title"]) && "" != trim($insertData["title"])) ? $this->db->escape($insertData["title"]) : $this->db->escape('');
                    $rep_order_date_list_ship_service       = (isset($insertData["ship_service"]) && "" != trim($insertData["ship_service"])) ? $this->db->escape($insertData["ship_service"]) : $this->db->escape('');
                    $rep_order_date_list_qty                = (isset($insertData["qty"]) && "" != trim($insertData["qty"])) ? $this->db->escape($insertData["qty"]) : $this->db->escape('');
                    $rep_order_date_list_currency           = (isset($insertData["currency"]) && "" != trim($insertData["currency"])) ? $this->db->escape($insertData["currency"]) : $this->db->escape('');
                    $rep_order_date_list_itm_price          = (isset($insertData["itm_price"]) && "" != trim($insertData["itm_price"])) ? $this->db->escape($insertData["itm_price"]) : $this->db->escape('0.00');
                    $rep_order_date_list_itm_tax            = (isset($insertData["itm_tax"]) && "" != trim($insertData["itm_tax"])) ? $this->db->escape($insertData["itm_tax"]) : $this->db->escape('0.00');
                    $rep_order_date_list_ship_price         = (isset($insertData["ship_price"]) && "" != trim($insertData["ship_price"])) ? $this->db->escape($insertData["ship_price"]) : $this->db->escape('0.00');
                    $rep_order_date_list_ship_tax           = (isset($insertData["ship_tax"]) && "" != trim($insertData["ship_tax"])) ? $this->db->escape($insertData["ship_tax"]) : $this->db->escape('0.00');
                    $rep_order_date_list_gift_price         = (isset($insertData["gift_price"]) && "" != trim($insertData["gift_price"])) ? $this->db->escape($insertData["gift_price"]) : $this->db->escape('0.00');
                    $rep_order_date_list_gift_tax           = (isset($insertData["gift_tax"]) && "" != trim($insertData["gift_tax"])) ? $this->db->escape($insertData["gift_tax"]) : $this->db->escape('0.00');
                    $rep_order_date_list_itm_promo_discount = (isset($insertData["itm_promo_discount"]) && "" != trim($insertData["itm_promo_discount"])) ? $this->db->escape($insertData["itm_promo_discount"]) : $this->db->escape('0.00');
                    $rep_order_date_list_itm_ship_discount  = (isset($insertData["itm_ship_discount"]) && "" != trim($insertData["itm_ship_discount"])) ? $this->db->escape($insertData["itm_ship_discount"]) : $this->db->escape('0.00');
                    $rep_order_date_list_ship_city          = (isset($insertData["ship_city"]) && "" != trim($insertData["ship_city"])) ? $this->db->escape($insertData["ship_city"]) : $this->db->escape('');
                    $rep_order_date_list_ship_state         = (isset($insertData["ship_state"]) && "" != trim($insertData["ship_state"])) ? $this->db->escape($insertData["ship_state"]) : $this->db->escape('');
                    $rep_order_date_list_ship_post          = (isset($insertData["ship_post"]) && "" != trim($insertData["ship_post"])) ? $this->db->escape($insertData["ship_post"]) : $this->db->escape('');
                    $rep_order_date_list_ship_country       = (isset($insertData["ship_country"]) && "" != trim($insertData["ship_country"])) ? $this->db->escape($insertData["ship_country"]) : $this->db->escape('');
                    $rep_order_date_list_promo_id           = (isset($insertData["promo_id"]) && "" != trim($insertData["promo_id"])) ? $this->db->escape($insertData["promo_id"]) : $this->db->escape('');
                    $rep_order_date_list_user_id            = (isset($insertData["user_id"]) && "" != trim($insertData["user_id"])) ? $this->db->escape($insertData["user_id"]) : $this->db->escape('');
                    $rep_order_date_list_fee_flag           = $this->db->escape('0');
                    $rep_order_date_list_dev_purchase_date  = (isset($insertData["dev_purchase_date"]) && "" != trim($insertData["dev_purchase_date"])) ? $this->db->escape($insertData["dev_purchase_date"]) : $this->db->escape('');

                    $repOrdersDataOrderDateListBulkQueryData[] = "({$rep_order_date_list_order_id},{$rep_order_date_list_po_date},{$rep_order_date_list_last_update_date},{$rep_order_date_list_ord_status},{$rep_order_date_list_fulfillment},{$rep_order_date_list_sales_channel},{$rep_order_date_list_itm_status},{$rep_order_date_list_ord_sku},{$rep_order_date_list_asin},{$rep_order_date_list_title},{$rep_order_date_list_ship_service},{$rep_order_date_list_qty},{$rep_order_date_list_currency},{$rep_order_date_list_itm_price},{$rep_order_date_list_itm_tax},{$rep_order_date_list_ship_price},{$rep_order_date_list_ship_tax},{$rep_order_date_list_gift_price},{$rep_order_date_list_gift_tax},{$rep_order_date_list_itm_promo_discount},{$rep_order_date_list_itm_ship_discount},{$rep_order_date_list_ship_city},{$rep_order_date_list_ship_state},{$rep_order_date_list_ship_post},{$rep_order_date_list_ship_country},{$rep_order_date_list_promo_id},{$rep_order_date_list_user_id},{$rep_order_date_list_fee_flag},{$rep_order_date_list_dev_purchase_date})";

                    if (!empty($repOrdersDataOrderDateListBulkQueryData) && count($repOrdersDataOrderDateListBulkQueryData) > 200) {
                        $repOrdersDataOrderDateListBulkQueryData_implode = implode(',',$repOrdersDataOrderDateListBulkQueryData);
                        $repOrdersDataOrderDateListBulkQueryData_sql_query = "INSERT INTO `rep_orders_data_order_date_list` (`order_id`, `po_date`, `last_update_date`, `ord_status`, `fulfillment`, `sales_channel`, `itm_status`, `ord_sku`, `asin`, `title`, `ship_service`, `qty`, `currency`, `itm_price`, `itm_tax`, `ship_price`, `ship_tax`, `gift_price`, `gift_tax`, `itm_promo_discount`, `itm_ship_discount`, `ship_city`, `ship_state`, `ship_post`, `ship_country`, `promo_id`, `user_id`, `fee_flag`, `dev_purchase_date`)
                                                                             VALUES
                                                                             $repOrdersDataOrderDateListBulkQueryData_implode
                                                                             ON DUPLICATE KEY
                                                                             UPDATE
                                                                             order_id=VALUES(order_id), po_date=VALUES(po_date), last_update_date=VALUES(last_update_date), ord_status=VALUES(ord_status), fulfillment=VALUES(fulfillment), sales_channel=VALUES(sales_channel), itm_status=VALUES(itm_status), ord_sku=VALUES(ord_sku), asin=VALUES(asin), title=VALUES(title), ship_service=VALUES(ship_service), qty=VALUES(qty), currency=VALUES(currency), itm_price=VALUES(itm_price), itm_tax=VALUES(itm_tax), ship_price=VALUES(ship_price), ship_tax=VALUES(ship_tax), gift_price=VALUES(gift_price), gift_tax=VALUES(gift_tax), itm_promo_discount=VALUES(itm_promo_discount), itm_ship_discount=VALUES(itm_ship_discount), ship_city=VALUES(ship_city), ship_state=VALUES(ship_state), ship_post=VALUES(ship_post), ship_country=VALUES(ship_country), promo_id=VALUES(promo_id), user_id=VALUES(user_id), fee_flag=VALUES(fee_flag), dev_purchase_date=VALUES(dev_purchase_date)";

                        $check_repOrdersDataOrderDateListBulkQueryData_sql_query = $this->db->query($repOrdersDataOrderDateListBulkQueryData_sql_query);
                        if (!$check_repOrdersDataOrderDateListBulkQueryData_sql_query) {
                            $getError = $this->db->error();
                            $responseData['response'] = 2;
                            $responseData['msg']      = $getError;
                            $responseData['fileName'] = $report_file;
                            return $responseData;
                            break;
                        }
                        $repOrdersDataOrderDateListBulkQueryData = array();
                    }
                }
                $i++;
            }
            fclose($fp);
            if (!empty($repOrdersDataOrderDateListBulkQueryData) && count($repOrdersDataOrderDateListBulkQueryData) > 0) {
                $repOrdersDataOrderDateListBulkQueryData_implode = implode(',',$repOrdersDataOrderDateListBulkQueryData);
                $repOrdersDataOrderDateListBulkQueryData_sql_query = "INSERT INTO `rep_orders_data_order_date_list` (`order_id`, `po_date`, `last_update_date`, `ord_status`, `fulfillment`, `sales_channel`, `itm_status`, `ord_sku`, `asin`, `title`, `ship_service`, `qty`, `currency`, `itm_price`, `itm_tax`, `ship_price`, `ship_tax`, `gift_price`, `gift_tax`, `itm_promo_discount`, `itm_ship_discount`, `ship_city`, `ship_state`, `ship_post`, `ship_country`, `promo_id`, `user_id`, `fee_flag`, `dev_purchase_date`)
                                                                     VALUES
                                                                     $repOrdersDataOrderDateListBulkQueryData_implode
                                                                     ON DUPLICATE KEY
                                                                     UPDATE
                                                                     order_id=VALUES(order_id), po_date=VALUES(po_date), last_update_date=VALUES(last_update_date), ord_status=VALUES(ord_status), fulfillment=VALUES(fulfillment), sales_channel=VALUES(sales_channel), itm_status=VALUES(itm_status), ord_sku=VALUES(ord_sku), asin=VALUES(asin), title=VALUES(title), ship_service=VALUES(ship_service), qty=VALUES(qty), currency=VALUES(currency), itm_price=VALUES(itm_price), itm_tax=VALUES(itm_tax), ship_price=VALUES(ship_price), ship_tax=VALUES(ship_tax), gift_price=VALUES(gift_price), gift_tax=VALUES(gift_tax), itm_promo_discount=VALUES(itm_promo_discount), itm_ship_discount=VALUES(itm_ship_discount), ship_city=VALUES(ship_city), ship_state=VALUES(ship_state), ship_post=VALUES(ship_post), ship_country=VALUES(ship_country), promo_id=VALUES(promo_id), user_id=VALUES(user_id), fee_flag=VALUES(fee_flag), dev_purchase_date=VALUES(dev_purchase_date)";

                $check_repOrdersDataOrderDateListBulkQueryData_sql_query = $this->db->query($repOrdersDataOrderDateListBulkQueryData_sql_query);
                if (!$check_repOrdersDataOrderDateListBulkQueryData_sql_query) {
                    $getError = $this->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $getError;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
            }
            // print_r($repOrdersDataOrderDateListBulkQueryData);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}

public function process_converged_order_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_converger_orders_data_list";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while(!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $pay_status=isset($buffer[0])?$this->db->escape($buffer[0]):'';
                    $amz_order_id=isset($buffer[1])?$this->db->escape($buffer[1]):'';
                    $amz_order_item_id=isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $pay_date=isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $pay_id=isset($buffer[4])?$this->db->escape($buffer[4]):'';
                    $itm_name=isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $list_id=isset($buffer[6])?$this->db->escape($buffer[6]):'';
                    $sku=isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $price=isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $ship_price=isset($buffer[9])?$this->db->escape($buffer[9]):'';
                    $qty=isset($buffer[10])?$this->db->escape($buffer[10]):'';
                    $order_total=isset($buffer[11])?$this->db->escape($buffer[11]):'';
                    $po_date=isset($buffer[12])?$this->db->escape($buffer[12]):'';
                    $buyer_email=isset($buffer[14])?$this->db->escape($buffer[14]):'';
                    $buyer_name=isset($buffer[15])?$this->db->escape($buffer[15]):'';
                    $recipient=isset($buffer[16])?$this->db->escape($buffer[16]):'';
                    $ship_addr1=isset($buffer[17])?$this->db->escape($buffer[17]):'';
                    $ship_addr2=isset($buffer[18])?$this->db->escape($buffer[18]):'';
                    $ship_city=isset($buffer[19])?$this->db->escape($buffer[19]):'';
                    $ship_state=isset($buffer[20])?$this->db->escape($buffer[20]):'';
                    $ship_zip=isset($buffer[21])?$this->db->escape($buffer[21]):'';
                    $ship_country=isset($buffer[22])?$this->db->escape($buffer[22]):'';
                    $country_code=$this->db->escape($country);
                    $bulk_data[]="(".$pay_status.",".$amz_order_id.",".$amz_order_item_id.",".$pay_date.",".$pay_id.",".$itm_name.",".$list_id.",".$sku.",".$price.",".$ship_price.",".$qty.",".$order_total.",".$po_date.",".$buyer_name.",".$buyer_email.",".$recipient.",".$ship_addr1.",".$ship_addr2.",".$ship_city.",".$ship_state.",".$ship_zip.",".$ship_country.",'".$country."',".$user_id.")";
                }

                if(isset($bulk_data) && count($bulk_data)>=500)
                {
                    // print_r($bulk_data);
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT INTO `rep_converger_orders_data_list` (pay_status,ord_id,ord_itm_id,pay_date,pay_id,itm_name,list_id,sku,price,ship_price,qty,order_total,po_date,buyer_name,buyer_email,recipient,ship_addr1,ship_addr2,ship_city,ship_state,ship_zip,ship_country,country,user_id)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    pay_status=VALUES(pay_status),ord_id=VALUES(ord_id),ord_itm_id=VALUES(ord_itm_id),pay_date=values(pay_date),pay_id=values(pay_id),itm_name=values(itm_name),list_id=values(list_id),sku=values(sku),price=values(price),ship_price=values(ship_price),qty=VALUES(qty),order_total=values(order_total),po_date=values(po_date),price=values(price),buyer_name=values(buyer_name),buyer_email=VALUES(buyer_email),recipient=values(recipient),ship_addr1=VALUES(ship_addr1),ship_addr2=values(ship_addr2),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_zip=VALUES(ship_zip),ship_country=VALUES(ship_country),country=VALUES(country),user_id=VALUES(user_id);";

                    $checkAddUpdateData = $this->db->query($qi);
                    if (!$checkAddUpdateData) {
                        $error = get_instance()->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $error;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {
                // print_r($bulk_data);
                $quer=implode(',',$bulk_data);
                $qi="INSERT INTO `rep_converger_orders_data_list` (pay_status,ord_id,ord_itm_id,pay_date,pay_id,itm_name,list_id,sku,price,ship_price,qty,order_total,po_date,buyer_name,buyer_email,recipient,ship_addr1,ship_addr2,ship_city,ship_state,ship_zip,ship_country,country,user_id)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                pay_status=VALUES(pay_status),ord_id=VALUES(ord_id),ord_itm_id=VALUES(ord_itm_id),pay_date=values(pay_date),pay_id=values(pay_id),itm_name=values(itm_name),list_id=values(list_id),sku=values(sku),price=values(price),ship_price=values(ship_price),qty=VALUES(qty),order_total=values(order_total),po_date=values(po_date),price=values(price),buyer_name=values(buyer_name),buyer_email=VALUES(buyer_email),recipient=values(recipient),ship_addr1=VALUES(ship_addr1),ship_addr2=values(ship_addr2),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_zip=VALUES(ship_zip),ship_country=VALUES(ship_country),country=VALUES(country),user_id=VALUES(user_id);";

                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}
public function process_fba_shipments_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_amz_fullfill_list";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while(!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                // print_r($buffer);
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    if(empty($buffer[1]))
                    {
                        $amz_order_id= isset($buffer[0])?$this->db->escape($buffer[0]):'';
                        $mer_order_id= isset($buffer[1])?$this->db->escape($buffer[1]):'';
                        $ship_id= isset($buffer[2])?$this->db->escape($buffer[2]):'';
                        $ship_item_id= isset($buffer[3])?$this->db->escape($buffer[3]):'';
                        $amz_order_item_id= isset($buffer[4])?$this->db->escape($buffer[4]):'';
                        $po_date=isset($buffer[6])?$this->db->escape($buffer[6]):'';
                        $pay_date= isset($buffer[7])?$this->db->escape($buffer[7]):'';
                        $ship_date= isset($buffer[8])?$this->db->escape($buffer[8]):'';
                        $report_date= isset($buffer[9])?$this->db->escape($buffer[9]):'';
                        $buyer_email=isset($buffer[10])?$this->db->escape($buffer[10]):'';
                        $buyer_name=isset($buffer[11])?$this->db->escape($buffer[11]):'';
                        $sku=isset($buffer[13])?$this->db->escape($buffer[13]):'';
                        $title=isset($buffer[14])?$this->db->escape($buffer[14]):'';
                        $qty=isset($buffer[15])?$this->db->escape($buffer[15]):'';
                        $currency=isset($buffer[16])?$this->db->escape($buffer[16]):'';
                        $itm_price=isset($buffer[17])?$this->db->escape($buffer[17]):'';
                        $itm_tax=isset($buffer[18])?$this->db->escape($buffer[18]):'';
                        $ship_price=isset($buffer[19])?$this->db->escape($buffer[19]):'';
                        $ship_tax=isset($buffer[20])?$this->db->escape($buffer[20]):'';
                        $gift_wrap_price=isset($buffer[21])?$this->db->escape($buffer[21]):'';
                        $gift_wrap_tax=isset($buffer[22])?$this->db->escape($buffer[22]):'';
                        $ship_addr1=isset($buffer[25])?$this->db->escape($buffer[25]):'';
                        $ship_addr2=isset($buffer[26])?$this->db->escape($buffer[26]):'';
                        $ship_addr3=isset($buffer[27])?$this->db->escape($buffer[27]):'';
                        $ship_city=isset($buffer[28])?$this->db->escape($buffer[28]):'';
                        $ship_state=isset($buffer[29])?$this->db->escape($buffer[29]):'';
                        $ship_zip=isset($buffer[30])?$this->db->escape($buffer[30]):'';
                        $ship_country=isset($buffer[31])?$this->db->escape($buffer[31]):'';
                        $track_no= isset($buffer[43])?$this->db->escape($buffer[43]):'';
                        $esp_deliv_date= isset($buffer[44])?$this->db->escape($buffer[44]):'';
                        $fullfill= isset($buffer[46])?$this->db->escape($buffer[46]):'';
                        $sale_channel= $buffer[47];
                        $cnt=explode('.',$sale_channel);
                        if(count($cnt) > 1)
                        {
                            $contry=$cnt[count($cnt)-1];
                            $country=strtoupper($contry);
                        }
                        $sale_channel= isset($buffer[47])?$this->db->escape($buffer[47]):'';
                        $bulk_data[]="(".$amz_order_id.",".$mer_order_id.",".$ship_id.",".$ship_item_id.",".$amz_order_item_id.",".$pay_date.",".$ship_date.",".$report_date.",".$track_no.",".$esp_deliv_date.",'".$country."',".$user_id.",'".$country."',".$po_date.",".$buyer_name.",".$buyer_email.",".$ship_addr1.",".$ship_addr2.",".$ship_addr3.",".$ship_city.",".$ship_state.",".$ship_zip.",".$ship_country.",".$sku.",".$title.",".$qty.",".$currency.",".$itm_price.",".$itm_tax.",".$ship_price.",".$ship_tax.",".$gift_wrap_price.",".$gift_wrap_tax.",".$fullfill.")";
                    }
                }

                if(isset($bulk_data) && count($bulk_data)>=500)
                {
                    // print_r($bulk_data);
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT INTO `rep_amz_fullfill_list` (order_no,mer_order_no,ship_id,ship_itm_id,order_item_no,payment_date,calc_shipdate,report_date,tracking_number,calc_deliverydate,sales_channel,customer_id,sales_country,purchase_date,buyer_name,buyer_email,shipping_addr1,shipping_addr2,shipping_addr3,shipping_city,shipping_state,shipping_zip,shipping_country,ord_sku,ord_title,ord_qty,ord_curr,ord_itm_price,ord_itm_tax,ord_ship_price,ord_ship_tax,ord_gift_price,ord_gift_tax,ord_fullfill)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    order_no=VALUES(order_no),mer_order_no=VALUES(mer_order_no),ship_id=VALUES(ship_id),ship_itm_id=VALUES(ship_itm_id),order_item_no=VALUES(order_item_no),payment_date=VALUES(payment_date),calc_shipdate=VALUES(calc_shipdate),report_date=VALUES(report_date),tracking_number=VALUES(tracking_number),calc_deliverydate=VALUES(calc_deliverydate),sales_channel=VALUES(sales_channel),customer_id=VALUES(customer_id),sales_country=VALUES(sales_country),purchase_date=VALUES(purchase_date),buyer_name=values(buyer_name),buyer_email=values(buyer_email),shipping_addr1=values(shipping_addr1),shipping_addr2=values(shipping_addr2),shipping_addr3=values(shipping_addr3),shipping_city=values(shipping_city),shipping_state=values(shipping_state),shipping_zip=values(shipping_zip),shipping_country=values(shipping_country),ord_sku=values(ord_sku),ord_title=values(ord_title),ord_qty=values(ord_qty),ord_curr=values(ord_curr),ord_curr=values(ord_curr),ord_itm_price=values(ord_itm_price),ord_itm_tax=values(ord_itm_tax),ord_ship_price=values(ord_ship_price),ord_ship_tax=values(ord_ship_tax),ord_gift_price=values(ord_gift_price),ord_gift_tax=values(ord_gift_tax),ord_fullfill=values(ord_fullfill);";
                    $checkAddUpdateData = $this->db->query($qi);
                    if (!$checkAddUpdateData) {
                        $error = get_instance()->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $error;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                    }
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {
                // print_r($bulk_data);
                $quer=implode(',',$bulk_data);
                $qi="INSERT INTO `rep_amz_fullfill_list` (order_no,mer_order_no,ship_id,ship_itm_id,order_item_no,payment_date,calc_shipdate,report_date,tracking_number,calc_deliverydate,sales_channel,customer_id,sales_country,purchase_date,buyer_name,buyer_email,shipping_addr1,shipping_addr2,shipping_addr3,shipping_city,shipping_state,shipping_zip,shipping_country,ord_sku,ord_title,ord_qty,ord_curr,ord_itm_price,ord_itm_tax,ord_ship_price,ord_ship_tax,ord_gift_price,ord_gift_tax,ord_fullfill)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                order_no=VALUES(order_no),mer_order_no=VALUES(mer_order_no),ship_id=VALUES(ship_id),ship_itm_id=VALUES(ship_itm_id),order_item_no=VALUES(order_item_no),payment_date=VALUES(payment_date),calc_shipdate=VALUES(calc_shipdate),report_date=VALUES(report_date),tracking_number=VALUES(tracking_number),calc_deliverydate=VALUES(calc_deliverydate),sales_channel=VALUES(sales_channel),customer_id=VALUES(customer_id),sales_country=VALUES(sales_country),purchase_date=VALUES(purchase_date),buyer_name=values(buyer_name),buyer_email=values(buyer_email),shipping_addr1=values(shipping_addr1),shipping_addr2=values(shipping_addr2),shipping_addr3=values(shipping_addr3),shipping_city=values(shipping_city),shipping_state=values(shipping_state),shipping_zip=values(shipping_zip),shipping_country=values(shipping_country),ord_sku=values(ord_sku),ord_title=values(ord_title),ord_qty=values(ord_qty),ord_curr=values(ord_curr),ord_curr=values(ord_curr),ord_itm_price=values(ord_itm_price),ord_itm_tax=values(ord_itm_tax),ord_ship_price=values(ord_ship_price),ord_ship_tax=values(ord_ship_tax),ord_gift_price=values(ord_gift_price),ord_gift_tax=values(ord_gift_tax),ord_fullfill=values(ord_fullfill);";
                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}
public function process_fba_fulfill_ship_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_fba_fulfill_ship_data_list";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while (!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $ship_date= isset($buffer[0])?$buffer[0]:'';
                    $sku= isset($buffer[1])?$this->db->escape($buffer[1]):'';
                    $fnsku= isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $asin= isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $fullfill_center_id= isset($buffer[4])?$this->db->escape($buffer[4]):'';
                    $qty= isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $amazon_order_id= isset($buffer[6])?$this->db->escape($buffer[6]):'';
                    $currency= isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $itm_price= isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $ship_price= isset($buffer[9])?$this->db->escape($buffer[9]):'';
                    $gift_price= isset($buffer[10])?$this->db->escape($buffer[10]):'';
                    $ship_city= isset($buffer[11])?$this->db->escape($buffer[11]):'';
                    $ship_state= isset($buffer[12])?$this->db->escape($buffer[12]):'';
                    $ship_post= isset($buffer[13])?$this->db->escape($buffer[13]):'';
                    //$country_code=$this->db->escape($country);
                    $bulk_data[]="('".$ship_date."',".$sku.",".$fnsku.",".$asin.",".$fullfill_center_id.",".$qty.",".$amazon_order_id.",".$currency.",".$itm_price.",".$ship_price.",".$gift_price.",".$ship_city.",".$ship_state.",".$ship_post.",".$user_id.")";
                }
                if(isset($bulk_data) && count($bulk_data)>=500)
                {
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT IGNORE INTO `rep_fba_fulfill_ship_data_list`(ship_date,sku,fnsku,asin,fullfill_id,qty,amz_order_id,currency,itm_price,ship_price,gift_price,ship_city,ship_state,ship_post,added_by)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    ship_date=VALUES(ship_date),sku=VALUES(sku),fnsku=VALUES(fnsku),asin=VALUES(asin),fullfill_id=VALUES(fullfill_id),qty=VALUES(qty),amz_order_id=VALUES(amz_order_id),currency=VALUES(currency),itm_price=VALUES(itm_price),ship_price=VALUES(ship_price),gift_price=VALUES(gift_price),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_post=VALUES(ship_post),ship_post=VALUES(ship_post),added_by=VALUES(added_by)";

                    $checkAddUpdateData = $this->db->query($qi);
                    if (!$checkAddUpdateData) {
                        $error = get_instance()->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $error;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {
                $quer=implode(',',$bulk_data);
                $qi="INSERT IGNORE INTO `rep_fba_fulfill_ship_data_list`(ship_date,sku,fnsku,asin,fullfill_id,qty,amz_order_id,currency,itm_price,ship_price,gift_price,ship_city,ship_state,ship_post,added_by)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                ship_date=VALUES(ship_date),sku=VALUES(sku),fnsku=VALUES(fnsku),asin=VALUES(asin),fullfill_id=VALUES(fullfill_id),qty=VALUES(qty),amz_order_id=VALUES(amz_order_id),currency=VALUES(currency),itm_price=VALUES(itm_price),ship_price=VALUES(ship_price),gift_price=VALUES(gift_price),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_post=VALUES(ship_post),ship_post=VALUES(ship_post),added_by=VALUES(added_by)";

                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}

public function process_actionable_order_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_actionable_order_data_list";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while (!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $ord_id= isset($buffer[0])?$buffer[0]:'';
                    $ord_itm_id= isset($buffer[1])?$this->db->escape($buffer[1]):'';
                    $po_date= isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $pay_date= isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $rep_date= isset($buffer[4])?$this->db->escape($buffer[4]):'';
                    $prom_date= isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $day_past= isset($buffer[6])?$this->db->escape($buffer[6]):'';
                    $buyer_email= isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $buyer_name= isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $buyer_phone= isset($buffer[9])?$this->db->escape($buffer[9]):'';
                    $sku= isset($buffer[10])?$this->db->escape($buffer[10]):'';
                    $prod_name= isset($buffer[11])?$this->db->escape($buffer[11]):'';
                    $qty_pur= isset($buffer[12])?$this->db->escape($buffer[12]):'';
                    $qty_ship= isset($buffer[13])?$this->db->escape($buffer[13]):'';
                    $qty_unship= isset($buffer[14])?$this->db->escape($buffer[14]):'';
                    $ship_service= isset($buffer[15])?$this->db->escape($buffer[15]):'';
                    $ship_addr1= isset($buffer[17])?$this->db->escape($buffer[17]):'';
                    $ship_addr2= isset($buffer[18])?$this->db->escape($buffer[18]):'';
                    $ship_addr3= isset($buffer[19])?$this->db->escape($buffer[19]):'';
                    $ship_city= isset($buffer[20])?$this->db->escape($buffer[20]):'';
                    $ship_state= isset($buffer[21])?$this->db->escape($buffer[21]):'';
                    $ship_post= isset($buffer[22])?$this->db->escape($buffer[22]):'';
                    $ship_country= isset($buffer[23])?$this->db->escape($buffer[23]):'';
                    $is_buss_order= isset($buffer[24])?$this->db->escape($buffer[24]):'';

                    //$country_code=$this->db->escape($country);
                    $bulk_data[]="('".$ord_id."',".$ord_itm_id.",".$po_date.",".$pay_date.",".$rep_date.",".$prom_date.",".$day_past.",".$buyer_email.",".$buyer_name.",".$buyer_phone.",".$sku.",".$prod_name.",".$qty_pur.",".$qty_ship.",".$qty_unship.",".$ship_service.",".$ship_addr1.",".$ship_addr2.",".$ship_addr3.",".$ship_city.",".$ship_state.",".$ship_post.",".$ship_country.",".$is_buss_order.",'".$country."',".$user_id.")";
                }

                if(isset($bulk_data) && count($bulk_data)>=500)
                {
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT IGNORE INTO `rep_actionable_order_data_list`(order_id,order_item_id,po_date,pay_date,rep_date,prom_date,day_past,buy_email,buy_name,buy_phone,sku,prod_name,qty_pur,qty_ship,qty_unship,ship_ser,ship_addr1,ship_addr2,ship_addr3,ship_city,ship_state,ship_post,ship_country,buss_order,country,usr_id)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    order_id=VALUES(order_id),order_item_id=VALUES(order_item_id),po_date=VALUES(po_date),pay_date=VALUES(pay_date),rep_date=VALUES(rep_date),prom_date=VALUES(prom_date),day_past=VALUES(day_past),buy_email=VALUES(buy_email),buy_name=VALUES(buy_name),buy_phone=VALUES(buy_phone),sku=VALUES(sku),prod_name=VALUES(prod_name),qty_pur=VALUES(qty_pur),qty_ship=VALUES(qty_ship),qty_unship=VALUES(qty_unship),ship_ser=VALUES(ship_ser),ship_addr1=VALUES(ship_addr1),ship_addr2=VALUES(ship_addr2),ship_addr3=VALUES(ship_addr3),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_post=VALUES(ship_post),ship_country=VALUES(ship_country),buss_order=VALUES(buss_order),country=VALUES(country),usr_id=VALUES(usr_id)";

                    $checkAddUpdateData = $this->db->query($qi);
                    if (!$checkAddUpdateData) {
                        $error = get_instance()->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $error;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {

                $quer=implode(',',$bulk_data);
                $qi="INSERT IGNORE INTO `rep_actionable_order_data_list`(order_id,order_item_id,po_date,pay_date,rep_date,prom_date,day_past,buy_email,buy_name,buy_phone,sku,prod_name,qty_pur,qty_ship,qty_unship,ship_ser,ship_addr1,ship_addr2,ship_addr3,ship_city,ship_state,ship_post,ship_country,buss_order,country,usr_id)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                order_id=VALUES(order_id),order_item_id=VALUES(order_item_id),po_date=VALUES(po_date),pay_date=VALUES(pay_date),rep_date=VALUES(rep_date),prom_date=VALUES(prom_date),day_past=VALUES(day_past),buy_email=VALUES(buy_email),buy_name=VALUES(buy_name),buy_phone=VALUES(buy_phone),sku=VALUES(sku),prod_name=VALUES(prod_name),qty_pur=VALUES(qty_pur),qty_ship=VALUES(qty_ship),qty_unship=VALUES(qty_unship),ship_ser=VALUES(ship_ser),ship_addr1=VALUES(ship_addr1),ship_addr2=VALUES(ship_addr2),ship_addr3=VALUES(ship_addr3),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_post=VALUES(ship_post),ship_country=VALUES(ship_country),buss_order=VALUES(buss_order),country=VALUES(country),usr_id=VALUES(usr_id)";

                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}
public function process_flat_order_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_flat_orders_list";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while (!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $ord_id= isset($buffer[0])?$this->db->escape($buffer[0]):'';
                    $ord_itm_id= isset($buffer[1])?$this->db->escape($buffer[1]):'';
                    $po_date=isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $payment_date=isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $buy_email=isset($buffer[4])?$this->db->escape($buffer[4]):'';
                    $buy_name=isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $buy_phone=isset($buffer[6])?$this->db->escape($buffer[6]):'';
                    $sku=isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $title=isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $qty=isset($buffer[9])?$this->db->escape($buffer[9]):'';
                    $currency=isset($buffer[10])?$this->db->escape($buffer[10]):'';
                    $itm_price=isset($buffer[11])?$this->db->escape($buffer[11]):'';
                    $itm_tax=isset($buffer[12])?$this->db->escape($buffer[12]):'';
                    $ship_price=isset($buffer[13])?$this->db->escape($buffer[13]):'';
                    $ship_tax=isset($buffer[14])?$this->db->escape($buffer[14]):'';
                    $ship_service=isset($buffer[15])?$this->db->escape($buffer[15]):'';
                    $recipient=isset($buffer[16])?$this->db->escape($buffer[16]):'';
                    $ship_addr1=isset($buffer[17])?$this->db->escape($buffer[17]):'';
                    $ship_addr2=isset($buffer[18])?$this->db->escape($buffer[18]):'';
                    $ship_addr3=isset($buffer[19])?$this->db->escape($buffer[19]):'';
                    $ship_city=isset($buffer[20])?$this->db->escape($buffer[20]):'';
                    $ship_state=isset($buffer[21])?$this->db->escape($buffer[21]):'';
                    $ship_post=isset($buffer[22])?$this->db->escape($buffer[22]):'';
                    $ship_country=isset($buffer[23])?$this->db->escape($buffer[23]):'';
                    $ship_phone=isset($buffer[24])?$this->db->escape($buffer[24]):'';
                    $itm_disc=isset($buffer[25])?$this->db->escape($buffer[25]):'';
                    $itm_id=isset($buffer[26])?$this->db->escape($buffer[26]):'';
                    $ship_disc=isset($buffer[27])?$this->db->escape($buffer[27]):'';
                    $ship_id=isset($buffer[28])?$this->db->escape($buffer[28]):'';
                    $del_start=isset($buffer[29])?$this->db->escape($buffer[29]):'';
                    $del_end=isset($buffer[30])?$this->db->escape($buffer[30]):'';
                    $country_code=$this->db->escape($country);

                    $bulk_data[]="(".$ord_id.",".$ord_itm_id.",".$po_date.",".$payment_date.",".$buy_email.",".$buy_name.",".$buy_phone.",".$sku.",".$title.",".$qty.",".$currency.",".$itm_price.",".$itm_tax.",".$ship_price.",".$ship_tax.",".$ship_service.",".$recipient.",".$ship_addr1.",".$ship_addr2.",".$ship_addr3.",".$ship_city.",".$ship_state.",".$ship_post.",".$ship_country.",".$ship_phone.",".$itm_disc.",".$itm_id.",".$ship_disc.",".$ship_id.",".$del_start.",".$del_end.",'".$country."',".$user_id.")";
                }

                if(isset($bulk_data) && count($bulk_data)==500)
                {
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT INTO `rep_flat_orders_list` (order_id,order_item_id,po_date,pay_date,buy_email,buy_name,buy_phone,sku,title,qty,currency,itm_price,itm_tax,ship_price,ship_tax,ship_service,recipient,ship_addr1,ship_addr2,ship_addr3,ship_city,ship_state,ship_post,ship_country,ship_phone,itm_promo_discount,itm_promo_id,ship_promo_discount,ship_promo_id,del_start_date,del_end_date,country,user_id)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    order_id=VALUES(order_id),order_item_id=VALUES(order_item_id),po_date=VALUES(po_date),pay_date=VALUES(pay_date),buy_email=VALUES(buy_email),buy_name=VALUES(buy_name),buy_phone=VALUES(buy_phone),sku=VALUES(sku),title=VALUES(title),qty=VALUES(qty),currency=VALUES(currency),itm_price=VALUES(itm_price),itm_tax=VALUES(itm_tax),ship_price=VALUES(ship_price),ship_tax=VALUES(ship_tax),ship_service=VALUES(ship_service),recipient=VALUES(recipient),ship_addr1=VALUES(ship_addr1),ship_addr2=VALUES(ship_addr2),ship_addr3=VALUES(ship_addr3),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_post=VALUES(ship_post),ship_country=VALUES(ship_country),ship_phone=VALUES(ship_phone),itm_promo_discount=VALUES(itm_promo_discount),itm_promo_id=VALUES(itm_promo_id),ship_promo_discount=VALUES(ship_promo_discount),ship_promo_id=VALUES(ship_promo_id),del_start_date=VALUES(del_start_date),del_end_date=VALUES(del_end_date),country=VALUES(country),user_id=VALUES(user_id);";

                    $checkAddUpdateData = $this->db->query($qi);
                    if (!$checkAddUpdateData) {
                        $error = get_instance()->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $error;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {
                $quer=implode(',',$bulk_data);
                $qi="INSERT INTO `rep_flat_orders_list` (order_id,order_item_id,po_date,pay_date,buy_email,buy_name,buy_phone,sku,title,qty,currency,itm_price,itm_tax,ship_price,ship_tax,ship_service,recipient,ship_addr1,ship_addr2,ship_addr3,ship_city,ship_state,ship_post,ship_country,ship_phone,itm_promo_discount,itm_promo_id,ship_promo_discount,ship_promo_id,del_start_date,del_end_date,country,user_id)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                order_id=VALUES(order_id),order_item_id=VALUES(order_item_id),po_date=VALUES(po_date),pay_date=VALUES(pay_date),buy_email=VALUES(buy_email),buy_name=VALUES(buy_name),buy_phone=VALUES(buy_phone),sku=VALUES(sku),title=VALUES(title),qty=VALUES(qty),currency=VALUES(currency),itm_price=VALUES(itm_price),itm_tax=VALUES(itm_tax),ship_price=VALUES(ship_price),ship_tax=VALUES(ship_tax),ship_service=VALUES(ship_service),recipient=VALUES(recipient),ship_addr1=VALUES(ship_addr1),ship_addr2=VALUES(ship_addr2),ship_addr3=VALUES(ship_addr3),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_post=VALUES(ship_post),ship_country=VALUES(ship_country),ship_phone=VALUES(ship_phone),itm_promo_discount=VALUES(itm_promo_discount),itm_promo_id=VALUES(itm_promo_id),ship_promo_discount=VALUES(ship_promo_discount),ship_promo_id=VALUES(ship_promo_id),del_start_date=VALUES(del_start_date),del_end_date=VALUES(del_end_date),country=VALUES(country),user_id=VALUES(user_id);";

                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}


public function process_vat_transaction_data($user_id,$report_file,$country,$request_type)
{
    $fp=fopen($report_file,'r');
    if ($fp)
    {
       $i=0;
       while (!feof($fp))
       {
        $buffer = fgetcsv($fp,0,"\t");
        if($i >= 1 && !empty($buffer[0]))
        {
          $unique_acc_identifier= isset($buffer[0])?$this->db->escape($buffer[0]):'';
          $activity_period= isset($buffer[1])?$this->db->escape($buffer[1]):'';
          $sales_channel= isset($buffer[2])?$this->db->escape($buffer[2]):'';
          $market_palce= $buffer[3];
          $cnt=explode('.',$market_palce);
          if(count($cnt) > 3)
          {
             $contry=$cnt[count($cnt)-1];
             $country=strtoupper($contry);
         }
         $market_palce= isset($buffer[3])?$this->db->escape($buffer[3]):'';
         $trans_type= isset($buffer[4])?$this->db->escape($buffer[4]):'';
         $trans_event_id= isset($buffer[5])?$this->db->escape($buffer[5]):'';
         $activity_trans_id= isset($buffer[6])?$this->db->escape($buffer[6]):'';
         $tax_cal_date_org= isset($buffer[7])?$buffer[7]:'';
         $tax_cal_date=$this->db->escape(date('Y-m-d',strtotime($tax_cal_date_org)));
         $trans_depart_date_org= isset($buffer[8])?$buffer[8]:'';
         $trans_depart_date=$this->db->escape(date('Y-m-d',strtotime($trans_depart_date_org)));
         $trans_arraival_date_org= isset($buffer[9])?$buffer[9]:'';
         $trans_arraival_date=$this->db->escape(date('Y-m-d',strtotime($trans_arraival_date_org)));
         $trans_compile_date_org= isset($buffer[10])?$buffer[10]:'';
         $trans_compile_date=$this->db->escape(date('Y-m-d',strtotime($trans_compile_date_org)));
         $seller_sku= isset($buffer[11])?$this->db->escape($buffer[11]):'';
         $prod_asin= isset($buffer[12])?$this->db->escape($buffer[12]):'';
         $desc= isset($buffer[13])?$buffer[13]:'';
         $description=str_replace("'","",$desc);
         $qty= isset($buffer[15])?$this->db->escape($buffer[15]):'';
         $itm_weight= isset($buffer[16])?$this->db->escape($buffer[16]):'';
         $total_weight_activity= isset($buffer[17])?$this->db->escape($buffer[17]):'';
         $cost_price_of_items=isset($buffer[18])?$this->db->escape($buffer[18]):'';
         $price_of_items_amt_vat_excl=isset($buffer[19])?$this->db->escape($buffer[19]):'';
         $promo_price_of_items_amt_vat_excl=isset($buffer[20])?$this->db->escape($buffer[20]):'';
         $total_price_of_items_amt_vat_excl=isset($buffer[21])?$this->db->escape($buffer[21]):'';
         $ship_charge_amt_vat_excl=isset($buffer[22])?$this->db->escape($buffer[22]):'';
         $promo_ship_charge_amt_vat_excl=isset($buffer[23])?$this->db->escape($buffer[23]):'';
         $total_ship_charge_amt_vat_excl=isset($buffer[24])?$this->db->escape($buffer[24]):'';
         $gift_wrap_amt_vat_excl=isset($buffer[25])?$this->db->escape($buffer[25]):'';
         $promo_gift_wrap_amt_vat_excl=isset($buffer[26])?$this->db->escape($buffer[26]):'';
         $total_gift_wrap_amt_vat_excl=isset($buffer[27])?$this->db->escape($buffer[27]):'';
         $total_activity_value_amt_vat_excl=isset($buffer[28])?$this->db->escape($buffer[28]):'';
         $price_of_items_vat_rate_percent=isset($buffer[29])?$this->db->escape($buffer[29]):'';
         $price_of_items_vat_amt=isset($buffer[30])?$this->db->escape($buffer[30]):'';
         $promo_price_of_items_vat_amt=isset($buffer[31])?$this->db->escape($buffer[31]):'';
         $total_price_of_items_vat_amt=isset($buffer[32])?$this->db->escape($buffer[32]):'';
         $ship_charge_vat_rate_percent=isset($buffer[33])?$this->db->escape($buffer[33]):'';
         $ship_charge_vat_amt=isset($buffer[34])?$this->db->escape($buffer[34]):'';
         $promo_ship_charge_vat_amt=isset($buffer[35])?$this->db->escape($buffer[35]):'';
         $total_ship_charge_vat_amt=isset($buffer[36])?$this->db->escape($buffer[36]):'';
         $gift_wrap_vat_rate_percent=isset($buffer[37])?$this->db->escape($buffer[37]):'';
         $gift_wrap_vat_amt=isset($buffer[38])?$this->db->escape($buffer[38]):'';
         $promo_gift_wrap_vat_amt=isset($buffer[39])?$this->db->escape($buffer[39]):'';
         $total_gift_wrap_vat_amt=isset($buffer[40])?$this->db->escape($buffer[40]):'';
         $total_activity_value_vat_amt=isset($buffer[41])?$this->db->escape($buffer[41]):'';
         $price_of_items_amt_vat_incl=isset($buffer[42])?$this->db->escape($buffer[42]):'';
         $promo_price_of_items_amt_vat_incl=isset($buffer[43])?$this->db->escape($buffer[43]):'';
         $total_price_of_items_amt_vat_incl=isset($buffer[44])?$this->db->escape($buffer[44]):'';
         $ship_charge_amt_vat_incl=isset($buffer[45])?$this->db->escape($buffer[45]):'';
         $promo_ship_charge_amt_vat_incl=isset($buffer[46])?$this->db->escape($buffer[46]):'';
         $total_ship_charge_amt_vat_incl=isset($buffer[47])?$this->db->escape($buffer[47]):'';
         $gift_wrap_amt_vat_incl=isset($buffer[48])?$this->db->escape($buffer[48]):'';
         $promo_gift_wrap_amt_vat_incl=isset($buffer[49])?$this->db->escape($buffer[49]):'';
         $total_gift_wrap_amt_vat_incl=isset($buffer[50])?$this->db->escape($buffer[50]):'';
         $total_activity_value_amt_vat_incl=isset($buffer[51])?$this->db->escape($buffer[51]):'';
         $transaction_currency_code=isset($buffer[52])?$this->db->escape($buffer[52]):'';
         $commodity_code=isset($buffer[53])?$this->db->escape($buffer[53]):'';
         $statistical_code_depart=isset($buffer[54])?$this->db->escape($buffer[54]):'';
         $statistical_code_arrival=isset($buffer[55])?$this->db->escape($buffer[55]):'';
         $commodity_code_supplementary_unit=isset($buffer[56])?$this->db->escape($buffer[56]):'';
         $item_qty_supplementary_unit=isset($buffer[57])?$this->db->escape($buffer[57]):'';
         $total_activity_supplementary_unit=isset($buffer[58])?$this->db->escape($buffer[58]):'';
         $product_tax_code=isset($buffer[59])?$this->db->escape($buffer[59]):'';
         $depature_city=isset($buffer[60])?$this->db->escape($buffer[60]):'';
         $departure_country=isset($buffer[61])?$this->db->escape($buffer[61]):'';
         $departure_post_code=isset($buffer[62])?$this->db->escape($buffer[62]):'';
         $arrival_city=isset($buffer[63])?$this->db->escape($buffer[63]):'';
         $arrival_country=isset($buffer[64])?$this->db->escape($buffer[64]):'';
         $arrival_post_code=isset($buffer[65])?$this->db->escape($buffer[65]):'';
         $sale_depart_country=isset($buffer[66])?$this->db->escape($buffer[66]):'';
         $sale_arrival_country=isset($buffer[67])?$this->db->escape($buffer[67]):'';
         $transportation_mode=isset($buffer[68])?$this->db->escape($buffer[68]):'';
         $delivery_conditions=isset($buffer[69])?$this->db->escape($buffer[69]):'';
         $seller_depart_vat_number_country=isset($buffer[70])?$this->db->escape($buffer[70]):'';
         $seller_depart_country_vat_number=isset($buffer[71])?$this->db->escape($buffer[71]):'';
         $seller_arrival_vat_number_country=isset($buffer[72])?$this->db->escape($buffer[72]):'';
         $seller_arrival_country_vat_number=isset($buffer[73])?$this->db->escape($buffer[73]):'';
         $transaction_seller_vat_number_country=isset($buffer[74])?$this->db->escape($buffer[74]):'';
         $transaction_seller_vat_number=isset($buffer[75])?$this->db->escape($buffer[75]):'';
         $buyer_vat_number_country=isset($buffer[76])?$this->db->escape($buffer[76]):'';
         $buyer_vat_number=isset($buffer[77])?$this->db->escape($buffer[77]):'';
         $vat_calculation_imputation_country=isset($buffer[78])?$this->db->escape($buffer[78]):'';
         $taxable_jurisdiction=isset($buffer[79])?$this->db->escape($buffer[79]):'';
         $taxable_jurisdiction_level=isset($buffer[80])?$this->db->escape($buffer[80]):'';
         $vat_inv_number=isset($buffer[81])?$this->db->escape($buffer[81]):'';
         $vat_inv_converted_amt=isset($buffer[82])?$this->db->escape($buffer[82]):'';
         $vat_inv_currency_code=isset($buffer[83])?$this->db->escape($buffer[83]):'';
         $vat_inv_exchange_rate=isset($buffer[84])?$this->db->escape($buffer[84]):'';
         $vat_inv_exchange_rate_date=isset($buffer[85])?$this->db->escape($buffer[85]):'';
         $export_outside_eu=isset($buffer[86])?$this->db->escape($buffer[86]):'';
         $invoice_url=isset($buffer[87])?$this->db->escape($buffer[87]):'';
         $buyer_name=isset($buffer[88])?$this->db->escape($buffer[88]):'';
         $arrival_address=isset($buffer[89])?$this->db->escape($buffer[89]):'';
          //$country_code=$this->db->escape($country);
         $bulk_data[]="(".$unique_acc_identifier.",".$activity_period.",".$sales_channel.",".$market_palce.",".$trans_type.",".$trans_event_id.",".$activity_trans_id.",".$tax_cal_date.",'".$tax_cal_date_org."',".$trans_depart_date.",".$trans_arraival_date.",".$trans_compile_date.",".$seller_sku.",".$prod_asin.",'".$description."',".$qty.",".$itm_weight.",".$total_weight_activity.",".$cost_price_of_items.",".$price_of_items_amt_vat_excl.",".$promo_price_of_items_amt_vat_excl.",".$total_price_of_items_amt_vat_excl.",".$ship_charge_amt_vat_excl.",".$promo_ship_charge_amt_vat_excl.",".$total_ship_charge_amt_vat_excl.",".$gift_wrap_amt_vat_excl.",".$promo_gift_wrap_amt_vat_excl.",".$total_gift_wrap_amt_vat_excl.",".$total_activity_value_amt_vat_excl.",".$price_of_items_vat_rate_percent.",".$price_of_items_vat_amt.",".$promo_price_of_items_vat_amt.",".$total_price_of_items_vat_amt.",".$ship_charge_vat_rate_percent.",".$ship_charge_vat_amt.",".$promo_ship_charge_vat_amt.",".$total_ship_charge_vat_amt.",".$gift_wrap_vat_rate_percent.",".$gift_wrap_vat_amt.",".$promo_gift_wrap_vat_amt.",".$total_gift_wrap_vat_amt.",".$total_activity_value_vat_amt.",".$price_of_items_amt_vat_incl.",".$promo_price_of_items_amt_vat_incl.",".$total_price_of_items_amt_vat_incl.",".$ship_charge_amt_vat_incl.",".$promo_ship_charge_amt_vat_incl.",".$total_ship_charge_amt_vat_incl.",".$gift_wrap_amt_vat_incl.",".$promo_gift_wrap_amt_vat_incl.",".$total_gift_wrap_amt_vat_incl.",".$total_activity_value_amt_vat_incl.",".$transaction_currency_code.",".$commodity_code.",".$statistical_code_depart.",".$statistical_code_arrival.",".$commodity_code_supplementary_unit.",".$item_qty_supplementary_unit.",".$total_activity_supplementary_unit.",".$product_tax_code.",".$depature_city.",".$departure_country.",".$departure_post_code.",".$arrival_city.",".$arrival_country.",".$arrival_post_code.",".$sale_depart_country.",".$sale_arrival_country.",".$transportation_mode.",".$delivery_conditions.",".$seller_depart_vat_number_country.",".$seller_depart_country_vat_number.",".$seller_arrival_vat_number_country.",".$seller_arrival_country_vat_number.",".$transaction_seller_vat_number_country.",".$transaction_seller_vat_number.",".$buyer_vat_number_country.",".$buyer_vat_number.",".$vat_calculation_imputation_country.",".$taxable_jurisdiction.",".$taxable_jurisdiction_level.",".$vat_inv_number.",".$vat_inv_converted_amt.",".$vat_inv_currency_code.",".$vat_inv_exchange_rate.",".$vat_inv_exchange_rate_date.",".$export_outside_eu.",".$invoice_url.",".$buyer_name.",".$arrival_address."
         ,'".$user_id."')";
     }
       //die();
     if(isset($bulk_data) && count($bulk_data)>=500)
     {
      $quer=implode(',',$bulk_data);
      $qi="INSERT IGNORE INTO `rep_vat_transaction_data`(unique_acc_identifier,activity_period,sales_channel,country,trans_type,trans_event_id,activity_trans_id,tax_cal_date,tax_cal_date_org,trans_depart_date,trans_arraival_date,trans_compile_date,seller_sku,prod_asin,description,qty,itm_weight,total_weight_activity,cost_price_of_items,price_of_items_amt_vat_excl,promo_price_of_items_amt_vat_excl,total_price_of_items_amt_vat_excl,ship_charge_amt_vat_excl,promo_ship_charge_amt_vat_excl,total_ship_charge_amt_vat_excl,gift_wrap_amt_vat_excl,promo_gift_wrap_amt_vat_excl,total_gift_wrap_amt_vat_excl,total_activity_value_amt_vat_excl,price_of_items_vat_rate_percent,price_of_items_vat_amt,promo_price_of_items_vat_amt,total_price_of_items_vat_amt,ship_charge_vat_rate_percent,ship_charge_vat_amt,promo_ship_charge_vat_amt,total_ship_charge_vat_amt,gift_wrap_vat_rate_percent,gift_wrap_vat_amt,promo_gift_wrap_vat_amt,total_gift_wrap_vat_amt,total_activity_value_vat_amt,price_of_items_amt_vat_incl,promo_price_of_items_amt_vat_incl,total_price_of_items_amt_vat_incl,ship_charge_amt_vat_incl,promo_ship_charge_amt_vat_incl,total_ship_charge_amt_vat_incl,gift_wrap_amt_vat_incl,promo_gift_wrap_amt_vat_incl,total_gift_wrap_amt_vat_incl,total_activity_value_amt_vat_incl,transaction_currency_code,commodity_code,statistical_code_depart,statistical_code_arrival,commodity_code_supplementary_unit,item_qty_supplementary_unit,total_activity_supplementary_unit,product_tax_code,depature_city,departure_country,departure_post_code,arrival_city,arrival_country,arrival_post_code,sale_depart_country,sale_arrival_country,transportation_mode,delivery_conditions,seller_depart_vat_number_country,seller_depart_country_vat_number,seller_arrival_vat_number_country,seller_arrival_country_vat_number,transaction_seller_vat_number_country,transaction_seller_vat_number,buyer_vat_number_country,buyer_vat_number,vat_calculation_imputation_country,taxable_jurisdiction,taxable_jurisdiction_level,vat_inv_number,vat_inv_converted_amt,vat_inv_currency_code,vat_inv_exchange_rate,vat_inv_exchange_rate_date,export_outside_eu,invoice_url,buyer_name,arrival_address,user_id)VALUES
      $quer
      ON DUPLICATE KEY
      UPDATE
      unique_acc_identifier=VALUES(unique_acc_identifier),activity_period=VALUES(activity_period),sales_channel=VALUES(sales_channel),country=VALUES(country),trans_type=VALUES(trans_type),trans_event_id=VALUES(trans_event_id),activity_trans_id=VALUES(activity_trans_id),tax_cal_date=VALUES(tax_cal_date),tax_cal_date_org=VALUES(tax_cal_date_org),trans_depart_date=VALUES(trans_depart_date),trans_arraival_date=VALUES(trans_arraival_date),trans_compile_date=VALUES(trans_compile_date),seller_sku=VALUES(seller_sku),prod_asin=VALUES(prod_asin),description=VALUES(description),qty=VALUES(qty),itm_weight=VALUES(itm_weight),total_weight_activity=VALUES(total_weight_activity),
      cost_price_of_items=VALUES(cost_price_of_items),price_of_items_amt_vat_excl=VALUES(price_of_items_amt_vat_excl),promo_price_of_items_amt_vat_excl=VALUES(promo_price_of_items_amt_vat_excl),
      total_price_of_items_amt_vat_excl=VALUES(total_price_of_items_amt_vat_excl),ship_charge_amt_vat_excl=VALUES(ship_charge_amt_vat_excl),promo_ship_charge_amt_vat_excl=VALUES(promo_ship_charge_amt_vat_excl),
      total_ship_charge_amt_vat_excl=VALUES(total_ship_charge_amt_vat_excl),gift_wrap_amt_vat_excl=VALUES(gift_wrap_amt_vat_excl),promo_gift_wrap_amt_vat_excl=VALUES(promo_gift_wrap_amt_vat_excl),
      total_gift_wrap_amt_vat_excl=VALUES(total_gift_wrap_amt_vat_excl),total_activity_value_amt_vat_excl=VALUES(total_activity_value_amt_vat_excl),
      price_of_items_vat_rate_percent=VALUES(price_of_items_vat_rate_percent),price_of_items_vat_amt=VALUES(price_of_items_vat_amt),
      promo_price_of_items_vat_amt=VALUES(promo_price_of_items_vat_amt),total_price_of_items_vat_amt=VALUES(total_price_of_items_vat_amt),
      ship_charge_vat_rate_percent=VALUES(ship_charge_vat_rate_percent),ship_charge_vat_amt=VALUES(ship_charge_vat_amt),promo_ship_charge_vat_amt=VALUES(promo_ship_charge_vat_amt),
      total_ship_charge_vat_amt=VALUES(total_ship_charge_vat_amt),gift_wrap_vat_rate_percent=VALUES(gift_wrap_vat_rate_percent),gift_wrap_vat_amt=VALUES(gift_wrap_vat_amt),
      promo_gift_wrap_vat_amt=VALUES(promo_gift_wrap_vat_amt),total_gift_wrap_vat_amt=VALUES(total_gift_wrap_vat_amt),total_activity_value_vat_amt=VALUES(total_activity_value_vat_amt),
      price_of_items_amt_vat_incl=VALUES(price_of_items_amt_vat_incl),promo_price_of_items_amt_vat_incl=VALUES(promo_price_of_items_amt_vat_incl),
      total_price_of_items_amt_vat_incl=VALUES(total_price_of_items_amt_vat_incl),ship_charge_amt_vat_incl=VALUES(ship_charge_amt_vat_incl),
      promo_ship_charge_amt_vat_incl=VALUES(promo_ship_charge_amt_vat_incl),total_ship_charge_amt_vat_incl=VALUES(total_ship_charge_amt_vat_incl),
      gift_wrap_amt_vat_incl=VALUES(gift_wrap_amt_vat_incl),promo_gift_wrap_amt_vat_incl=VALUES(promo_gift_wrap_amt_vat_incl),
      total_gift_wrap_amt_vat_incl=VALUES(total_gift_wrap_amt_vat_incl),total_activity_value_amt_vat_incl=VALUES(total_activity_value_amt_vat_incl),
      transaction_currency_code=VALUES(transaction_currency_code),commodity_code=VALUES(commodity_code),statistical_code_depart=VALUES(statistical_code_depart),
      statistical_code_arrival=VALUES(statistical_code_arrival),commodity_code_supplementary_unit=VALUES(commodity_code_supplementary_unit),item_qty_supplementary_unit=VALUES(item_qty_supplementary_unit),
      total_activity_supplementary_unit=VALUES(total_activity_supplementary_unit),product_tax_code=VALUES(product_tax_code),depature_city=VALUES(depature_city),departure_country=VALUES(departure_country),
      departure_post_code=VALUES(departure_post_code),arrival_city=VALUES(arrival_city),arrival_country=VALUES(arrival_country),arrival_post_code=VALUES(arrival_post_code),
      sale_depart_country=VALUES(sale_depart_country),sale_arrival_country=VALUES(sale_arrival_country),transportation_mode=VALUES(transportation_mode),delivery_conditions=VALUES(delivery_conditions),
      seller_depart_vat_number_country=VALUES(seller_depart_vat_number_country),seller_depart_country_vat_number=VALUES(seller_depart_country_vat_number),seller_arrival_vat_number_country=VALUES(seller_arrival_vat_number_country),
      seller_arrival_country_vat_number=VALUES(seller_arrival_country_vat_number),transaction_seller_vat_number_country=VALUES(transaction_seller_vat_number_country),transaction_seller_vat_number=VALUES(transaction_seller_vat_number),
      buyer_vat_number_country=VALUES(buyer_vat_number_country),buyer_vat_number=VALUES(buyer_vat_number),vat_calculation_imputation_country=VALUES(vat_calculation_imputation_country),taxable_jurisdiction=VALUES(taxable_jurisdiction),
      taxable_jurisdiction_level=VALUES(taxable_jurisdiction_level),vat_inv_number=VALUES(vat_inv_number),vat_inv_converted_amt=VALUES(vat_inv_converted_amt),vat_inv_currency_code=VALUES(vat_inv_currency_code),
      vat_inv_exchange_rate=VALUES(vat_inv_exchange_rate),vat_inv_exchange_rate_date=VALUES(vat_inv_exchange_rate_date),export_outside_eu=VALUES(export_outside_eu),invoice_url=VALUES(invoice_url),
      buyer_name=VALUES(buyer_name),arrival_address=VALUES(arrival_address),user_id=VALUES(user_id)";

      $this->db->query($qi);
      unset($bulk_data);
      unset($quer);
  }
  $i++;
}
if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
{

  $quer=implode(',',$bulk_data);
  $qi="INSERT IGNORE INTO `rep_vat_transaction_data`(unique_acc_identifier,activity_period,sales_channel,country,trans_type,trans_event_id,activity_trans_id,tax_cal_date,tax_cal_date_org,trans_depart_date,trans_arraival_date,trans_compile_date,seller_sku,prod_asin,description,qty,itm_weight,total_weight_activity,cost_price_of_items,price_of_items_amt_vat_excl,promo_price_of_items_amt_vat_excl,total_price_of_items_amt_vat_excl,ship_charge_amt_vat_excl,promo_ship_charge_amt_vat_excl,total_ship_charge_amt_vat_excl,gift_wrap_amt_vat_excl,promo_gift_wrap_amt_vat_excl,total_gift_wrap_amt_vat_excl,total_activity_value_amt_vat_excl,price_of_items_vat_rate_percent,price_of_items_vat_amt,promo_price_of_items_vat_amt,total_price_of_items_vat_amt,ship_charge_vat_rate_percent,ship_charge_vat_amt,promo_ship_charge_vat_amt,total_ship_charge_vat_amt,gift_wrap_vat_rate_percent,gift_wrap_vat_amt,promo_gift_wrap_vat_amt,total_gift_wrap_vat_amt,total_activity_value_vat_amt,price_of_items_amt_vat_incl,promo_price_of_items_amt_vat_incl,total_price_of_items_amt_vat_incl,ship_charge_amt_vat_incl,promo_ship_charge_amt_vat_incl,total_ship_charge_amt_vat_incl,gift_wrap_amt_vat_incl,promo_gift_wrap_amt_vat_incl,total_gift_wrap_amt_vat_incl,total_activity_value_amt_vat_incl,transaction_currency_code,commodity_code,statistical_code_depart,statistical_code_arrival,commodity_code_supplementary_unit,item_qty_supplementary_unit,total_activity_supplementary_unit,product_tax_code,depature_city,departure_country,departure_post_code,arrival_city,arrival_country,arrival_post_code,sale_depart_country,sale_arrival_country,transportation_mode,delivery_conditions,seller_depart_vat_number_country,seller_depart_country_vat_number,seller_arrival_vat_number_country,seller_arrival_country_vat_number,transaction_seller_vat_number_country,transaction_seller_vat_number,buyer_vat_number_country,buyer_vat_number,vat_calculation_imputation_country,taxable_jurisdiction,taxable_jurisdiction_level,vat_inv_number,vat_inv_converted_amt,vat_inv_currency_code,vat_inv_exchange_rate,vat_inv_exchange_rate_date,export_outside_eu,invoice_url,buyer_name,arrival_address,user_id)VALUES
  $quer
  ON DUPLICATE KEY
  UPDATE
  unique_acc_identifier=VALUES(unique_acc_identifier),activity_period=VALUES(activity_period),sales_channel=VALUES(sales_channel),country=VALUES(country),trans_type=VALUES(trans_type),trans_event_id=VALUES(trans_event_id),activity_trans_id=VALUES(activity_trans_id),tax_cal_date=VALUES(tax_cal_date),tax_cal_date_org=VALUES(tax_cal_date_org),trans_depart_date=VALUES(trans_depart_date),trans_arraival_date=VALUES(trans_arraival_date),trans_compile_date=VALUES(trans_compile_date),seller_sku=VALUES(seller_sku),prod_asin=VALUES(prod_asin),description=VALUES(description),qty=VALUES(qty),itm_weight=VALUES(itm_weight),total_weight_activity=VALUES(total_weight_activity),
  cost_price_of_items=VALUES(cost_price_of_items),price_of_items_amt_vat_excl=VALUES(price_of_items_amt_vat_excl),promo_price_of_items_amt_vat_excl=VALUES(promo_price_of_items_amt_vat_excl),
  total_price_of_items_amt_vat_excl=VALUES(total_price_of_items_amt_vat_excl),ship_charge_amt_vat_excl=VALUES(ship_charge_amt_vat_excl),promo_ship_charge_amt_vat_excl=VALUES(promo_ship_charge_amt_vat_excl),
  total_ship_charge_amt_vat_excl=VALUES(total_ship_charge_amt_vat_excl),gift_wrap_amt_vat_excl=VALUES(gift_wrap_amt_vat_excl),promo_gift_wrap_amt_vat_excl=VALUES(promo_gift_wrap_amt_vat_excl),
  total_gift_wrap_amt_vat_excl=VALUES(total_gift_wrap_amt_vat_excl),total_activity_value_amt_vat_excl=VALUES(total_activity_value_amt_vat_excl),
  price_of_items_vat_rate_percent=VALUES(price_of_items_vat_rate_percent),price_of_items_vat_amt=VALUES(price_of_items_vat_amt),
  promo_price_of_items_vat_amt=VALUES(promo_price_of_items_vat_amt),total_price_of_items_vat_amt=VALUES(total_price_of_items_vat_amt),
  ship_charge_vat_rate_percent=VALUES(ship_charge_vat_rate_percent),ship_charge_vat_amt=VALUES(ship_charge_vat_amt),promo_ship_charge_vat_amt=VALUES(promo_ship_charge_vat_amt),
  total_ship_charge_vat_amt=VALUES(total_ship_charge_vat_amt),gift_wrap_vat_rate_percent=VALUES(gift_wrap_vat_rate_percent),gift_wrap_vat_amt=VALUES(gift_wrap_vat_amt),
  promo_gift_wrap_vat_amt=VALUES(promo_gift_wrap_vat_amt),total_gift_wrap_vat_amt=VALUES(total_gift_wrap_vat_amt),total_activity_value_vat_amt=VALUES(total_activity_value_vat_amt),
  price_of_items_amt_vat_incl=VALUES(price_of_items_amt_vat_incl),promo_price_of_items_amt_vat_incl=VALUES(promo_price_of_items_amt_vat_incl),
  total_price_of_items_amt_vat_incl=VALUES(total_price_of_items_amt_vat_incl),ship_charge_amt_vat_incl=VALUES(ship_charge_amt_vat_incl),
  promo_ship_charge_amt_vat_incl=VALUES(promo_ship_charge_amt_vat_incl),total_ship_charge_amt_vat_incl=VALUES(total_ship_charge_amt_vat_incl),
  gift_wrap_amt_vat_incl=VALUES(gift_wrap_amt_vat_incl),promo_gift_wrap_amt_vat_incl=VALUES(promo_gift_wrap_amt_vat_incl),
  total_gift_wrap_amt_vat_incl=VALUES(total_gift_wrap_amt_vat_incl),total_activity_value_amt_vat_incl=VALUES(total_activity_value_amt_vat_incl),
  transaction_currency_code=VALUES(transaction_currency_code),commodity_code=VALUES(commodity_code),statistical_code_depart=VALUES(statistical_code_depart),
  statistical_code_arrival=VALUES(statistical_code_arrival),commodity_code_supplementary_unit=VALUES(commodity_code_supplementary_unit),item_qty_supplementary_unit=VALUES(item_qty_supplementary_unit),
  total_activity_supplementary_unit=VALUES(total_activity_supplementary_unit),product_tax_code=VALUES(product_tax_code),depature_city=VALUES(depature_city),departure_country=VALUES(departure_country),
  departure_post_code=VALUES(departure_post_code),arrival_city=VALUES(arrival_city),arrival_country=VALUES(arrival_country),arrival_post_code=VALUES(arrival_post_code),
  sale_depart_country=VALUES(sale_depart_country),sale_arrival_country=VALUES(sale_arrival_country),transportation_mode=VALUES(transportation_mode),delivery_conditions=VALUES(delivery_conditions),
  seller_depart_vat_number_country=VALUES(seller_depart_vat_number_country),seller_depart_country_vat_number=VALUES(seller_depart_country_vat_number),seller_arrival_vat_number_country=VALUES(seller_arrival_vat_number_country),
  seller_arrival_country_vat_number=VALUES(seller_arrival_country_vat_number),transaction_seller_vat_number_country=VALUES(transaction_seller_vat_number_country),transaction_seller_vat_number=VALUES(transaction_seller_vat_number),
  buyer_vat_number_country=VALUES(buyer_vat_number_country),buyer_vat_number=VALUES(buyer_vat_number),vat_calculation_imputation_country=VALUES(vat_calculation_imputation_country),taxable_jurisdiction=VALUES(taxable_jurisdiction),
  taxable_jurisdiction_level=VALUES(taxable_jurisdiction_level),vat_inv_number=VALUES(vat_inv_number),vat_inv_converted_amt=VALUES(vat_inv_converted_amt),vat_inv_currency_code=VALUES(vat_inv_currency_code),
  vat_inv_exchange_rate=VALUES(vat_inv_exchange_rate),vat_inv_exchange_rate_date=VALUES(vat_inv_exchange_rate_date),export_outside_eu=VALUES(export_outside_eu),invoice_url=VALUES(invoice_url),
  buyer_name=VALUES(buyer_name),arrival_address=VALUES(arrival_address),user_id=VALUES(user_id)";

  $this->db->query($qi);
  unset($bulk_data);
  unset($quer);
}
fclose($fp);
}
}


public function process_fba_monthly_inv_data($user_id,$report_file,$country,$request_type)
{
    $fp=fopen($report_file,'r');
    if ($fp)
    {
       $i=0;
       while(!feof($fp))
       {
        $buffer = fgetcsv($fp,0,"\t");
        //print_r($buffer);
        if($i>=1 && !empty($buffer[0]) )
        {

         $month= isset($buffer[0])?$this->db->escape($buffer[0]):'';
         $fnsku= isset($buffer[1])?$this->db->escape($buffer[1]):'';
         $sku= isset($buffer[2])?$this->db->escape($buffer[2]):'';
         $name= isset($buffer[3])?$this->db->escape($buffer[3]):'';
         $avg_qty= isset($buffer[4])?$this->db->escape($buffer[4]):'';
         $qty= isset($buffer[5])?$this->db->escape($buffer[5]):'';
         $fulfill_id= isset($buffer[6])?$this->db->escape($buffer[6]):'';
         $deposition= isset($buffer[7])?$this->db->escape($buffer[7]):'';
         $con= isset($buffer[8])?$this->db->escape($buffer[8]):'';


         $bulk_data[]="(".$month.",".$fnsku.",".$sku.",".$name.",".$avg_qty.",".$qty.",".$fulfill_id.",".$deposition.",".$con.",".$user_id.")";

     }

     if(isset($bulk_data) && count($bulk_data)==500)
     {
      $quer=implode(',',$bulk_data);
      $qi="INSERT INTO `rep_fba_monthly_inv_data`(prod_month,prod_fn_sku,prod_sku,prod_name,prod_avg_qty,prod_qty,prod_full_id,prod_disp,prod_country,user_id)VALUES
      $quer
      ON DUPLICATE KEY
      UPDATE
      prod_month=VALUES(prod_month),prod_fn_sku=VALUES(prod_fn_sku),prod_sku=VALUES(prod_sku),prod_name=VALUES(prod_name),prod_avg_qty=VALUES(prod_avg_qty),prod_qty=VALUES(prod_qty),prod_full_id=VALUES(prod_full_id),prod_disp=VALUES(prod_disp),prod_country=VALUES(prod_country),user_id=VALUES(user_id);";
      $this->db->query($qi);

      unset($bulk_data);
      unset($quer);
  }
  $i++;
}
if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
{
  $quer=implode(',',$bulk_data);
  $qi="INSERT INTO `rep_fba_monthly_inv_data`(prod_month,prod_fn_sku,prod_sku,prod_name,prod_avg_qty,prod_qty,prod_full_id,prod_disp,prod_country,user_id)VALUES
  $quer
  ON DUPLICATE KEY
  UPDATE
  prod_month=VALUES(prod_month),prod_fn_sku=VALUES(prod_fn_sku),prod_sku=VALUES(prod_sku),prod_name=VALUES(prod_name),prod_avg_qty=VALUES(prod_avg_qty),prod_qty=VALUES(prod_qty),prod_full_id=VALUES(prod_full_id),prod_disp=VALUES(prod_disp),prod_country=VALUES(prod_country),user_id=VALUES(user_id);";
  $this->db->query($qi);
  unset($bulk_data);
  unset($quer);
}
fclose($fp);
}
}

public function process_fba_fullfill_cus_tax_data($user_id,$report_file,$country,$request_type)
{
    $fp=fopen($report_file,'r');
    if ($fp)
    {
       $i=0;
       while(!feof($fp))
       {
        $buffer = fgetcsv($fp,0,"\t");
        //print_r($buffer);
        if($i>=1 && !empty($buffer[0]) )
        {

         $ship_date= isset($buffer[0])?$this->db->escape($buffer[0]):'';
         $shipment_date=$this->db->escape(date('Y-m-d H:i:s',strtotime($ship_date)));
         $sku= isset($buffer[1])?$this->db->escape($buffer[1]):'';
         $fnsku= isset($buffer[2])?$this->db->escape($buffer[2]):'';
         $asin= isset($buffer[3])?$this->db->escape($buffer[3]):'';
         $fullfill_id= isset($buffer[4])?$this->db->escape($buffer[4]):'';
         $qty= isset($buffer[5])?$this->db->escape($buffer[5]):'';
         $amz_order_id= isset($buffer[6])?$this->db->escape($buffer[6]):'';
         $currency= isset($buffer[7])?$this->db->escape($buffer[7]):'';
         $itm_price= isset($buffer[8])?$this->db->escape($buffer[8]):'';
         $ship_price= isset($buffer[9])?$this->db->escape($buffer[9]):'';
         $gift_wrap= isset($buffer[10])?$this->db->escape($buffer[10]):'';
         $city= isset($buffer[11])?$this->db->escape($buffer[11]):'';
         $state= isset($buffer[12])?$this->db->escape($buffer[12]):'';
         $postal= isset($buffer[13])?$this->db->escape($buffer[13]):'';


         $bulk_data[]="(".$shipment_date.",".$sku.",".$fnsku.",".$asin.",".$fullfill_id.",".$qty.",".$amz_order_id.",".$currency.",".$itm_price.",".$ship_price.",".$gift_wrap.",".$city.",".$state.",".$postal.",".$user_id.")";

     }

     if(isset($bulk_data) && count($bulk_data)==500)
     {
      $quer=implode(',',$bulk_data);
      $qi="INSERT INTO `rep_fba_customer_tax_data`(ship_date,sku,fn_sku,asin,fullfill_id,qty,amz_order_id,currency,itm_price,ship_price,gift_wrap,ship_city,ship_state,ship_postal,user_id)VALUES
      $quer
      ON DUPLICATE KEY
      UPDATE
      ship_date=VALUES(ship_date),sku=VALUES(sku),fn_sku=VALUES(fn_sku),asin=VALUES(asin),fullfill_id=VALUES(fullfill_id),qty=VALUES(qty),amz_order_id=VALUES(amz_order_id),currency=VALUES(currency),itm_price=VALUES(itm_price),ship_price=VALUES(ship_price),gift_wrap=VALUES(gift_wrap),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_postal=VALUES(ship_postal),user_id=VALUES(user_id);";
      $this->db->query($qi);

      unset($bulk_data);
      unset($quer);
  }
  $i++;
}
if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
{
  $quer=implode(',',$bulk_data);
  $qi="INSERT INTO `rep_fba_customer_tax_data`(ship_date,sku,fn_sku,asin,fullfill_id,qty,amz_order_id,currency,itm_price,ship_price,gift_wrap,ship_city,ship_state,ship_postal,user_id)VALUES
  $quer
  ON DUPLICATE KEY
  UPDATE
  ship_date=VALUES(ship_date),sku=VALUES(sku),fn_sku=VALUES(fn_sku),asin=VALUES(asin),fullfill_id=VALUES(fullfill_id),qty=VALUES(qty),amz_order_id=VALUES(amz_order_id),currency=VALUES(currency),itm_price=VALUES(itm_price),ship_price=VALUES(ship_price),gift_wrap=VALUES(gift_wrap),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_postal=VALUES(ship_postal),user_id=VALUES(user_id);";
  $this->db->query($qi);
  unset($bulk_data);
  unset($quer);
}
fclose($fp);
}
}

public function process_fba_returns_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "amz_order_return_data";
    try {
        $dataBaseColumnName = array(
                                    'return-date'           => 'return_date',
                                    'order-id'              => 'order_id',
                                    'sku'                   => 'sku',
                                    'asin'                  => 'asin',
                                    'fnsku'                 => 'fn_sku',
                                    'product-name'          => 'prod_name',
                                    'quantity'              => 'qty',
                                    'fulfillment-center-id' => 'fullfill_cent_id',
                                    'detailed-disposition'  => 'detailed_disp',
                                    'reason'                => 'reason',
                                    'status'                => 'reason',
                                    'license-plate-number'  => 'licence_plate_num',
                                    'customer-comments'     => 'cust_comments',
                                    );
        // die($report_file);
    	$fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            $csvColumnNames = array();
            $processFbaReturnsBulkQueryData = array();
            while (!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");

                if ($i===0) {
                    $csvColumnNames = $buffer;
                }

                // echo "<pre>";
                // print_r($buffer);
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }

                    $insertData = array();
                    foreach ($csvColumnNames as $key => $csvColumnName) {
                        if (in_array($csvColumnName, $csvColumnNames) && array_key_exists($csvColumnName,$dataBaseColumnName)) {
                            $getMatchKey = array_search($csvColumnName, $csvColumnNames);
                            if (isset($dataBaseColumnName[$csvColumnName])) {
                                $insertData[$dataBaseColumnName[$csvColumnName]] = $buffer[$getMatchKey];
                            }
                        }
                    }

                    // print_r($insertData);

                    if (isset($insertData['return_date'])) {
                        $insertData['return_date'] = getTimeZoneDateTime($insertData['return_date'], $insertData['return_date']);
                    }

                    // print_r($insertData);

                    $processFbaReturns_return_date      = (isset($insertData["return_date"]) && "" != trim($insertData["return_date"])) ? $this->db->escape($insertData["return_date"]) : $this->db->escape('');
                    $processFbaReturns_order_id         = (isset($insertData["order_id"]) && "" != trim($insertData["order_id"])) ? $this->db->escape($insertData["order_id"]) : $this->db->escape('');
                    $processFbaReturns_sku              = (isset($insertData["sku"]) && "" != trim($insertData["sku"])) ? $this->db->escape($insertData["sku"]) : $this->db->escape('');
                    $processFbaReturns_asin             = (isset($insertData["asin"]) && "" != trim($insertData["asin"])) ? $this->db->escape($insertData["asin"]) : $this->db->escape('');
                    $processFbaReturns_fn_sku           = (isset($insertData["fn_sku"]) && "" != trim($insertData["fn_sku"])) ? $this->db->escape($insertData["fn_sku"]) : $this->db->escape('');
                    $processFbaReturns_prod_name        = (isset($insertData["prod_name"]) && "" != trim($insertData["prod_name"])) ? $this->db->escape($insertData["prod_name"]) : $this->db->escape('');
                    $processFbaReturns_qty              = (isset($insertData["qty"]) && "" != trim($insertData["qty"])) ? $this->db->escape($insertData["qty"]) : $this->db->escape('');
                    $processFbaReturns_fullfill_cent_id = (isset($insertData["fullfill_cent_id"]) && "" != trim($insertData["fullfill_cent_id"])) ? $this->db->escape($insertData["fullfill_cent_id"]) : $this->db->escape('');
                    $processFbaReturns_detailed_disp    = (isset($insertData["detailed_disp"]) && "" != trim($insertData["detailed_disp"])) ? $this->db->escape($insertData["detailed_disp"]) : $this->db->escape('');
                    $processFbaReturns_reason           = (isset($insertData["reason"]) && "" != trim($insertData["reason"])) ? $this->db->escape($insertData["reason"]) : $this->db->escape('');
                    $processFbaReturns_status           = (isset($insertData["status"]) && "" != trim($insertData["status"])) ? $this->db->escape($insertData["status"]) : $this->db->escape('');
                    $processFbaReturns_licence_plate_num = (isset($insertData["licence_plate_num"]) && "" != trim($insertData["licence_plate_num"])) ? $this->db->escape($insertData["licence_plate_num"]) : $this->db->escape('');
                    $processFbaReturns_cust_comments    = (isset($insertData["cust_comments"]) && "" != trim($insertData["cust_comments"])) ? $this->db->escape($insertData["cust_comments"]) : $this->db->escape('');
                    $processFbaReturns_ret_for          = $this->db->escape($user_id);

                    $processFbaReturnsBulkQueryData[] = "({$processFbaReturns_return_date},{$processFbaReturns_order_id},{$processFbaReturns_sku},{$processFbaReturns_asin},{$processFbaReturns_fn_sku},{$processFbaReturns_prod_name},{$processFbaReturns_qty},{$processFbaReturns_fullfill_cent_id},{$processFbaReturns_detailed_disp},{$processFbaReturns_reason},{$processFbaReturns_status},{$processFbaReturns_licence_plate_num},{$processFbaReturns_cust_comments}, {$processFbaReturns_ret_for})";
                }

                if (!empty($processFbaReturnsBulkQueryData) && count($processFbaReturnsBulkQueryData) > 200) {
                    $processFbaReturnsBulkQueryData_implode   = implode(',',$processFbaReturnsBulkQueryData);
                    $processFbaReturnsBulkQueryData_sql_query ="INSERT INTO `amz_order_return_data` (`return_date`, `order_id`, `sku`, `asin`, `fn_sku`, `prod_name`, `qty`, `fullfill_cent_id`, `detailed_disp`, `reason`, `status`, `licence_plate_num`, `cust_comments`, `ret_for`)
                                                                VALUES
                                                                $processFbaReturnsBulkQueryData_implode
                                                                ON DUPLICATE KEY
                                                                UPDATE
                                                                return_date=VALUES(return_date), order_id=VALUES(order_id), sku=VALUES(sku), asin=VALUES(asin), fn_sku=VALUES(fn_sku), prod_name=VALUES(prod_name), qty=VALUES(qty), fullfill_cent_id=VALUES(fullfill_cent_id), detailed_disp=VALUES(detailed_disp), reason=VALUES(reason), status=VALUES(status), licence_plate_num=VALUES(licence_plate_num), cust_comments=VALUES(cust_comments), ret_for=VALUES(ret_for)";

                    $check_processFbaReturnsBulkQueryData = $this->db->query($processFbaReturnsBulkQueryData_sql_query);
                    if (!$check_processFbaReturnsBulkQueryData) {
                        $getError = $this->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $getError;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $processFbaReturnsBulkQueryData = array();
                }
                $i++;
            }
            fclose($fp);

            // print_r($processFbaReturnsBulkQueryData);

            if (!empty($processFbaReturnsBulkQueryData) && count($processFbaReturnsBulkQueryData) > 0) {
                $processFbaReturnsBulkQueryData_implode   = implode(',',$processFbaReturnsBulkQueryData);
                $processFbaReturnsBulkQueryData_sql_query ="INSERT INTO `amz_order_return_data` (`return_date`, `order_id`, `sku`, `asin`, `fn_sku`, `prod_name`, `qty`, `fullfill_cent_id`, `detailed_disp`, `reason`, `status`, `licence_plate_num`, `cust_comments`, `ret_for`)
                                                            VALUES
                                                            $processFbaReturnsBulkQueryData_implode
                                                            ON DUPLICATE KEY
                                                            UPDATE
                                                            return_date=VALUES(return_date), order_id=VALUES(order_id), sku=VALUES(sku), asin=VALUES(asin), fn_sku=VALUES(fn_sku), prod_name=VALUES(prod_name), qty=VALUES(qty), fullfill_cent_id=VALUES(fullfill_cent_id), detailed_disp=VALUES(detailed_disp), reason=VALUES(reason), status=VALUES(status), licence_plate_num=VALUES(licence_plate_num), cust_comments=VALUES(cust_comments), ret_for=VALUES(ret_for)";

                $check_processFbaReturnsBulkQueryData = $this->db->query($processFbaReturnsBulkQueryData_sql_query);
                if (!$check_processFbaReturnsBulkQueryData) {
                    $getError = $this->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $getError;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
            }
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}



public function process_restock_inv_data($user_id,$report_file,$country,$request_type)
{
    // echo "<pre>";
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_restock_inv_data";
    try {
        $dataBaseColumnName = array(
                                        'Country' => 'res_country',
                                        'Product Description' => 'res_desc',
                                        'FNSKU' => 'res_fn_sku',
                                        'SKU' => 'res_sku',
                                        'ASIN' => 'res_asin',
                                        'Condition' => 'res_cond',
                                        'Supplier' => 'res_supp',
                                        'Supplier part no.' => 'res_supp_no',
                                        'Currency Code' => 'res_curr',
                                        'Price' => 'res_price',
                                        'Sales last 30 days (sales)' => 'res_sales_30_days',
                                        'Sales last 30 days (units)' => 'res_sales_30_days_units',
                                        'Total Inventory' => 'res_total_inv',
                                        'Inbound Inventory' => 'res_inb_inv',
                                        'Available Inventory' => 'res_avb_inv',
                                        'Reserved - FC transfer' => 'res_fc_trans',
                                        'Reserved - FC processing' => 'res_fc_process',
                                        'Reserved - Customer Order' => 'res_cus_order',
                                        'Unfulfillable' => 'res_unfill',
                                        'Fulfilled by' => 'res_fulfill',
                                        'Days of Supply' => 'res_days_of_sup',
                                        'Instock Alert' => 'res_instock_alert',
                                        'Recommended Order Quantity' => 'res_recom_qty',
                                        'Recommended Order Date' => 'res_recom_order_date'
                                    );
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            $csvColumnNames = array();
            $rep_restock_inv_data_bulk_query_data = array();
            while(!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                // print_r($buffer);
                if ($i===0) {
                    $csvColumnNames = $buffer;
                    // print_r($csvColumnNames);
                }

                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $insertData = array();
                    foreach ($csvColumnNames as $key => $csvColumnName) {
                        if (in_array($csvColumnName, $csvColumnNames) && array_key_exists($csvColumnName,$dataBaseColumnName)) {
                            $getMatchKey = array_search($csvColumnName, $csvColumnNames);
                            if (isset($dataBaseColumnName[$csvColumnName])) {
                                $insertData[$dataBaseColumnName[$csvColumnName]] = $buffer[$getMatchKey];
                            }
                        }
                    }

                    if (isset($insertData['res_recom_order_date'])) {
                        $insertData['res_recom_order_date'] = date("Y-m-d", strtotime($insertData['res_recom_order_date']));
                    }

                    $insertData['res_user_id'] = $user_id;

                    $res_country     = (isset($insertData["res_country"]) && "" != trim($insertData["res_country"])) ? $this->db->escape($insertData["res_country"]) : $this->db->escape('');
                    $res_desc        = (isset($insertData["res_desc"]) && "" != trim($insertData["res_desc"])) ? $this->db->escape($insertData["res_desc"]) : $this->db->escape('');
                    $res_fn_sku      = (isset($insertData["res_fn_sku"]) && "" != trim($insertData["res_fn_sku"])) ? $this->db->escape($insertData["res_fn_sku"]) : $this->db->escape('');
                    $res_sku         = (isset($insertData["res_sku"]) && "" != trim($insertData["res_sku"])) ? $this->db->escape($insertData["res_sku"]) : $this->db->escape('');
                    $res_asin        = (isset($insertData["res_asin"]) && "" != trim($insertData["res_asin"])) ? $this->db->escape($insertData["res_asin"]) : $this->db->escape('');
                    $res_cond        = (isset($insertData["res_cond"]) && "" != trim($insertData["res_cond"])) ? $this->db->escape($insertData["res_cond"]) : $this->db->escape('');
                    $res_supp        = (isset($insertData["res_supp"]) && "" != trim($insertData["res_supp"])) ? $this->db->escape($insertData["res_supp"]) : $this->db->escape('');
                    $res_supp_no     = (isset($insertData["res_supp_no"]) && "" != trim($insertData["res_supp_no"])) ? $this->db->escape($insertData["res_supp_no"]) : $this->db->escape('');
                    $res_curr        = (isset($insertData["res_curr"]) && "" != trim($insertData["res_curr"])) ? $this->db->escape($insertData["res_curr"]) : $this->db->escape('');
                    $res_price       = (isset($insertData["res_price"]) && "" != trim($insertData["res_price"])) ? $this->db->escape($insertData["res_price"]) : $this->db->escape('');
                    $res_sales_30_days       = (isset($insertData["res_sales_30_days"]) && "" != trim($insertData["res_sales_30_days"])) ? $this->db->escape($insertData["res_sales_30_days"]) : $this->db->escape('');
                    $res_sales_30_days_units = (isset($insertData["res_sales_30_days_units"]) && "" != trim($insertData["res_sales_30_days_units"])) ? $this->db->escape($insertData["res_sales_30_days_units"]) : $this->db->escape('');
                    $res_total_inv   = (isset($insertData["res_total_inv"]) && "" != trim($insertData["res_total_inv"])) ? $this->db->escape($insertData["res_total_inv"]) : $this->db->escape('');
                    $res_inb_inv     = (isset($insertData["res_inb_inv"]) && "" != trim($insertData["res_inb_inv"])) ? $this->db->escape($insertData["res_inb_inv"]) : $this->db->escape('');
                    $res_avb_inv     = (isset($insertData["res_avb_inv"]) && "" != trim($insertData["res_avb_inv"])) ? $this->db->escape($insertData["res_avb_inv"]) : $this->db->escape('');
                    $res_fc_trans    = (isset($insertData["res_fc_trans"]) && "" != trim($insertData["res_fc_trans"])) ? $this->db->escape($insertData["res_fc_trans"]) : $this->db->escape('');
                    $res_fc_process  = (isset($insertData["res_fc_process"]) && "" != trim($insertData["res_fc_process"])) ? $this->db->escape($insertData["res_fc_process"]) : $this->db->escape('');
                    $res_cus_order   = (isset($insertData["res_cus_order"]) && "" != trim($insertData["res_cus_order"])) ? $this->db->escape($insertData["res_cus_order"]) : $this->db->escape('');
                    $res_unfill      = (isset($insertData["res_unfill"]) && "" != trim($insertData["res_unfill"])) ? $this->db->escape($insertData["res_unfill"]) : $this->db->escape('');
                    $res_fulfill     = (isset($insertData["res_fulfill"]) && "" != trim($insertData["res_fulfill"])) ? $this->db->escape($insertData["res_fulfill"]) : $this->db->escape('');
                    $res_days_of_sup = (isset($insertData["res_days_of_sup"]) && "" != trim($insertData["res_days_of_sup"])) ? $this->db->escape($insertData["res_days_of_sup"]) : $this->db->escape('');
                    $res_instock_alert = (isset($insertData["res_instock_alert"]) && "" != trim($insertData["res_instock_alert"])) ? $this->db->escape($insertData["res_instock_alert"]) : $this->db->escape('');
                    $res_recom_qty     = (isset($insertData["res_recom_qty"]) && "" != trim($insertData["res_recom_qty"])) ? $this->db->escape($insertData["res_recom_qty"]) : $this->db->escape('');
                    $res_recom_order_date = (isset($insertData["res_recom_order_date"]) && "" != trim($insertData["res_recom_order_date"])) ? $this->db->escape($insertData["res_recom_order_date"]) : $this->db->escape('');
                    $res_user_id     = (isset($insertData["res_user_id"]) && "" != trim($insertData["res_user_id"])) ? $this->db->escape($insertData["res_user_id"]) : $this->db->escape('');

                    $rep_restock_inv_data_bulk_query_data[] = "({$res_country},{$res_desc},{$res_fn_sku},{$res_sku},{$res_asin},{$res_cond},{$res_supp},{$res_supp_no},{$res_curr},{$res_price},{$res_sales_30_days},{$res_sales_30_days_units},{$res_total_inv},{$res_inb_inv},{$res_avb_inv},{$res_fc_trans},{$res_fc_process},{$res_cus_order},{$res_unfill},{$res_fulfill},{$res_days_of_sup},{$res_instock_alert},{$res_recom_qty},{$res_recom_order_date},{$res_user_id})";

                    if (!empty($rep_restock_inv_data_bulk_query_data) && count($rep_restock_inv_data_bulk_query_data) > 200) {
                        $rep_restock_inv_data_bulk_query_data_implode = implode(',',$rep_restock_inv_data_bulk_query_data);
                        $rep_restock_inv_data_sql_query = "INSERT INTO `rep_restock_inv_data` (`res_country`, `res_desc`, `res_fn_sku`, `res_sku`, `res_asin`, `res_cond`, `res_supp`, `res_supp_no`, `res_curr`, `res_price`, `res_sales_30_days`, `res_sales_30_days_units`, `res_total_inv`, `res_inb_inv`, `res_avb_inv`, `res_fc_trans`, `res_fc_process`, `res_cus_order`, `res_unfill`, `res_fulfill`, `res_days_of_sup`, `res_instock_alert`, `res_recom_qty`, `res_recom_order_date`, `res_user_id`)
                                                         VALUES
                                                         $rep_restock_inv_data_bulk_query_data_implode
                                                         ON DUPLICATE KEY
                                                         UPDATE
                                                         res_country=VALUES(res_country), res_desc=VALUES(res_desc), res_fn_sku=VALUES(res_fn_sku), res_sku=VALUES(res_sku), res_asin=VALUES(res_asin), res_cond=VALUES(res_cond), res_supp=VALUES(res_supp), res_supp_no=VALUES(res_supp_no), res_curr=VALUES(res_curr), res_price=VALUES(res_price), res_sales_30_days=VALUES(res_sales_30_days), res_sales_30_days_units=VALUES(res_sales_30_days_units), res_total_inv=VALUES(res_total_inv), res_inb_inv=VALUES(res_inb_inv), res_avb_inv=VALUES(res_avb_inv), res_fc_trans=VALUES(res_fc_trans), res_fc_process=VALUES(res_fc_process), res_cus_order=VALUES(res_cus_order), res_unfill=VALUES(res_unfill), res_fulfill=VALUES(res_fulfill), res_days_of_sup=VALUES(res_days_of_sup), res_instock_alert=VALUES(res_instock_alert), res_recom_qty=VALUES(res_recom_qty), res_recom_order_date=VALUES(res_recom_order_date), res_user_id=VALUES(res_user_id)";

                        $check_rep_restock_inv_data_sql_query = $this->db->query($rep_restock_inv_data_sql_query);
                        if (!$check_rep_restock_inv_data_sql_query) {
                            $getError = $this->db->error();
                            $responseData['response'] = 2;
                            $responseData['msg']      = $getError;
                            $responseData['fileName'] = $report_file;
                            return $responseData;
                            break;
                        }
                        $rep_restock_inv_data_bulk_query_data = array();
                    }
                }
                $i++;
            }
            fclose($fp);

            if (!empty($rep_restock_inv_data_bulk_query_data) && count($rep_restock_inv_data_bulk_query_data) > 0) {
                $rep_restock_inv_data_bulk_query_data_implode = implode(',',$rep_restock_inv_data_bulk_query_data);
                $rep_restock_inv_data_sql_query = "INSERT INTO `rep_restock_inv_data` (`res_country`, `res_desc`, `res_fn_sku`, `res_sku`, `res_asin`, `res_cond`, `res_supp`, `res_supp_no`, `res_curr`, `res_price`, `res_sales_30_days`, `res_sales_30_days_units`, `res_total_inv`, `res_inb_inv`, `res_avb_inv`, `res_fc_trans`, `res_fc_process`, `res_cus_order`, `res_unfill`, `res_fulfill`, `res_days_of_sup`, `res_instock_alert`, `res_recom_qty`, `res_recom_order_date`, `res_user_id`)
                                                 VALUES
                                                 $rep_restock_inv_data_bulk_query_data_implode
                                                 ON DUPLICATE KEY
                                                 UPDATE
                                                 res_country=VALUES(res_country), res_desc=VALUES(res_desc), res_fn_sku=VALUES(res_fn_sku), res_sku=VALUES(res_sku), res_asin=VALUES(res_asin), res_cond=VALUES(res_cond), res_supp=VALUES(res_supp), res_supp_no=VALUES(res_supp_no), res_curr=VALUES(res_curr), res_price=VALUES(res_price), res_sales_30_days=VALUES(res_sales_30_days), res_sales_30_days_units=VALUES(res_sales_30_days_units), res_total_inv=VALUES(res_total_inv), res_inb_inv=VALUES(res_inb_inv), res_avb_inv=VALUES(res_avb_inv), res_fc_trans=VALUES(res_fc_trans), res_fc_process=VALUES(res_fc_process), res_cus_order=VALUES(res_cus_order), res_unfill=VALUES(res_unfill), res_fulfill=VALUES(res_fulfill), res_days_of_sup=VALUES(res_days_of_sup), res_instock_alert=VALUES(res_instock_alert), res_recom_qty=VALUES(res_recom_qty), res_recom_order_date=VALUES(res_recom_order_date), res_user_id=VALUES(res_user_id)";

                $check_rep_restock_inv_data_sql_query = $this->db->query($rep_restock_inv_data_sql_query);
                if (!$check_rep_restock_inv_data_sql_query) {
                    $getError = $this->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $getError;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
            }

        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}


public function process_fba_inv_health_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_fba_inv_health_data";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while (!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $snap_date= isset($buffer[0])?$this->db->escape($buffer[0]):'';
                    $sku= isset($buffer[1])?$this->db->escape($buffer[1]):'';
                    $fn_sku= isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $asin= isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $prod_name= isset($buffer[4])?$this->db->escape($buffer[4]):'';
                    $prod_cond= isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $sales_rank= isset($buffer[6])?$this->db->escape($buffer[6]):'';
                    $prod_group= isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $total_qty= isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $sell_qty= isset($buffer[9])?$this->db->escape($buffer[9]):'';
                    $unsell_qty= isset($buffer[10])?$this->db->escape($buffer[10]):'';
                    $inv_age_0_to_90= isset($buffer[11])?$this->db->escape($buffer[11]):'';
                    $inv_age_91_to_180= isset($buffer[12])?$this->db->escape($buffer[12]):'';
                    $inv_age_181_to_270= isset($buffer[13])?$this->db->escape($buffer[13]):'';
                    $inv_age_271_to_365= isset($buffer[14])?$this->db->escape($buffer[14]):'';
                    $inv_age_365_plus= isset($buffer[15])?$this->db->escape($buffer[15]):'';
                    $unit_ship_24_hrs= isset($buffer[16])?$this->db->escape($buffer[16]):'';
                    $unit_ship_7_days= isset($buffer[17])?$this->db->escape($buffer[17]):'';
                    $unit_ship_30_days= isset($buffer[18])?$this->db->escape($buffer[18]):'';
                    $unit_ship_90_days= isset($buffer[19])?$this->db->escape($buffer[19]):'';
                    $unit_ship_180_days= isset($buffer[20])?$this->db->escape($buffer[20]):'';
                    $unit_ship_365_days= isset($buffer[21])?$this->db->escape($buffer[21]):'';
                    $weeks_of_cover_t7= isset($buffer[22])?$this->db->escape($buffer[22]):'';
                    $weeks_of_cover_t30= isset($buffer[23])?$this->db->escape($buffer[23]):'';
                    $weeks_of_cover_t90= isset($buffer[24])?$this->db->escape($buffer[24]):'';
                    $weeks_of_cover_t180= isset($buffer[25])?$this->db->escape($buffer[25]):'';
                    $weeks_of_cover_t365= isset($buffer[26])?$this->db->escape($buffer[26]):'';
                    $num_afn_new_sellers= isset($buffer[27])?$this->db->escape($buffer[27]):'';
                    $num_afn_user_sellers= isset($buffer[28])?$this->db->escape($buffer[28]):'';
                    $curr= isset($buffer[29])?$this->db->escape($buffer[29]):'';
                    $your_price= isset($buffer[30])?$this->db->escape($buffer[30]):'';
                    $sale_price= isset($buffer[31])?$this->db->escape($buffer[31]):'';
                    $low_afn_new_price= isset($buffer[32])?$this->db->escape($buffer[32]):'';
                    $low_afn_used_price= isset($buffer[33])?$this->db->escape($buffer[33]):'';
                    $low_mfn_new_price= isset($buffer[34])?$this->db->escape($buffer[34]):'';
                    $low_mfn_used_price= isset($buffer[35])?$this->db->escape($buffer[35]):'';
                    $qty_charged_12= isset($buffer[36])?$this->db->escape($buffer[36]):'';
                    $qty_charger_long_term= isset($buffer[37])?$this->db->escape($buffer[37]):'';
                    $qty_removal_in_progress= isset($buffer[38])?$this->db->escape($buffer[38]):'';
                    $projected_12= isset($buffer[39])?$this->db->escape($buffer[39]):'';
                    $per_unit_vol= isset($buffer[40])?$this->db->escape($buffer[40]):'';
                    $is_hazmat= isset($buffer[41])?$this->db->escape($buffer[41]):'';
                    $in_bound_qty= isset($buffer[42])?$this->db->escape($buffer[42]):'';
                    $asin_limit= isset($buffer[43])?$this->db->escape($buffer[43]):'';
                    $inbound_recomm_qty= isset($buffer[44])?$this->db->escape($buffer[44]):'';
                    $qty_charged_6= isset($buffer[45])?$this->db->escape($buffer[45]):'';
                    $projected_6= isset($buffer[46])?$this->db->escape($buffer[46]):'';

                    $bulk_data[]="(".$snap_date.",".$sku.",".$fn_sku.",".$asin.",".$prod_name.",".$prod_cond.",".$sales_rank.",".$prod_group.",".$total_qty.",".$sell_qty." ,".$unsell_qty."  ,".$inv_age_0_to_90.",".$inv_age_91_to_180.",".$inv_age_181_to_270.",".$inv_age_271_to_365.",".$inv_age_365_plus.",".$unit_ship_24_hrs.",".$unit_ship_7_days.",".$unit_ship_30_days.",".$unit_ship_90_days.",".$unit_ship_180_days.",".$unit_ship_365_days.",".$weeks_of_cover_t7.",".$weeks_of_cover_t30.",".$weeks_of_cover_t90.",".$weeks_of_cover_t180.",".$weeks_of_cover_t365.",".$num_afn_new_sellers.",".$num_afn_user_sellers.",".$curr.",".$your_price.",".$sale_price.",".$low_afn_new_price.",".$low_afn_used_price.",".$low_mfn_new_price.",".$low_mfn_used_price.",".$qty_charged_12.",".$qty_charger_long_term.",".$qty_removal_in_progress.",".$projected_12.",".$per_unit_vol.",".$is_hazmat.",".$in_bound_qty.",".$asin_limit.",".$inbound_recomm_qty.",".$qty_charged_6.",".$projected_6.",".$user_id.")";
                }

                if(isset($bulk_data) && count($bulk_data)>=500)
                {
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT  INTO `rep_fba_inv_health_data` (snap_date,sku,fn_sku,asin,prod_name,prod_cond,sales_rank,prod_group,total_qty,sell_qty,unsell_qty,inv_age_0_to_90,inv_age_91_to_180,inv_age_181_to_270,inv_age_271_to_365,inv_age_365_plus,unit_ship_24_hrs,unit_ship_7_days,unit_ship_30_days,unit_ship_90_days,unit_ship_180_days,unit_ship_365_days,weeks_of_cover_t7,weeks_of_cover_t30,weeks_of_cover_t90,weeks_of_cover_t180,weeks_of_cover_t365,num_afn_new_sellers,num_afn_user_sellers,curr,your_price,sale_price,low_afn_new_price,low_afn_used_price,low_mfn_new_price,low_mfn_used_price,qty_charged_12,qty_charger_long_term,qty_removal_in_progress,projected_12,per_unit_vol,is_hazmat,in_bound_qty,asin_limit,inbound_recomm_qty,qty_charged_6,projected_6,added_by)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    snap_date=VALUES(snap_date),sku=VALUES(sku),fn_sku=VALUES(fn_sku),asin=VALUES(asin),prod_name=VALUES(prod_name),prod_cond=VALUES(prod_cond),sales_rank=VALUES(sales_rank),prod_group=VALUES(prod_group),total_qty=VALUES(total_qty),sell_qty=VALUES(sell_qty),unsell_qty=VALUES(unsell_qty),inv_age_0_to_90=VALUES(inv_age_0_to_90),inv_age_91_to_180=VALUES(inv_age_91_to_180),inv_age_181_to_270=VALUES(inv_age_181_to_270),inv_age_271_to_365=VALUES(inv_age_271_to_365),inv_age_365_plus=VALUES(inv_age_365_plus),unit_ship_24_hrs=VALUES(unit_ship_24_hrs),unit_ship_7_days=VALUES(unit_ship_7_days),unit_ship_30_days=VALUES(unit_ship_30_days),unit_ship_90_days=VALUES(unit_ship_90_days),unit_ship_180_days=VALUES(unit_ship_180_days),unit_ship_365_days=VALUES(unit_ship_365_days),weeks_of_cover_t7=VALUES(weeks_of_cover_t7),weeks_of_cover_t30=VALUES(weeks_of_cover_t30),weeks_of_cover_t90=VALUES(weeks_of_cover_t90),weeks_of_cover_t180=VALUES(weeks_of_cover_t180),weeks_of_cover_t365=VALUES(weeks_of_cover_t365),num_afn_new_sellers=VALUES(num_afn_new_sellers),num_afn_user_sellers=VALUES(num_afn_user_sellers),curr=VALUES(curr),your_price=VALUES(your_price),sale_price=VALUES(sale_price),low_afn_new_price=VALUES(low_afn_new_price),low_afn_used_price=VALUES(low_afn_used_price),low_mfn_new_price=VALUES(low_mfn_new_price),low_mfn_used_price=VALUES(low_mfn_used_price),qty_charged_12=VALUES(qty_charged_12),qty_charger_long_term=VALUES(qty_charger_long_term),qty_removal_in_progress=VALUES(qty_removal_in_progress),projected_12=VALUES(projected_12),per_unit_vol=VALUES(per_unit_vol),is_hazmat=VALUES(is_hazmat),in_bound_qty=VALUES(in_bound_qty),asin_limit=VALUES(asin_limit),inbound_recomm_qty=VALUES(inbound_recomm_qty),qty_charged_6=VALUES(qty_charged_6),projected_6=VALUES(projected_6),added_by=VALUES(added_by)";

                    $checkAddUpdateData = $this->db->query($qi);
                    if (!$checkAddUpdateData) {
                        $error = get_instance()->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $error;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {
                $quer=implode(',',$bulk_data);
                $qi="INSERT  INTO `rep_fba_inv_health_data` (snap_date,sku,fn_sku,asin,prod_name,prod_cond,sales_rank,prod_group,total_qty,sell_qty,unsell_qty,inv_age_0_to_90,inv_age_91_to_180,inv_age_181_to_270,inv_age_271_to_365,inv_age_365_plus,unit_ship_24_hrs,unit_ship_7_days,unit_ship_30_days,unit_ship_90_days,unit_ship_180_days,unit_ship_365_days,weeks_of_cover_t7,weeks_of_cover_t30,weeks_of_cover_t90,weeks_of_cover_t180,weeks_of_cover_t365,num_afn_new_sellers,num_afn_user_sellers,curr,your_price,sale_price,low_afn_new_price,low_afn_used_price,low_mfn_new_price,low_mfn_used_price,qty_charged_12,qty_charger_long_term,qty_removal_in_progress,projected_12,per_unit_vol,is_hazmat,in_bound_qty,asin_limit,inbound_recomm_qty,qty_charged_6,projected_6,added_by)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                snap_date=VALUES(snap_date),sku=VALUES(sku),fn_sku=VALUES(fn_sku),asin=VALUES(asin),prod_name=VALUES(prod_name),prod_cond=VALUES(prod_cond),sales_rank=VALUES(sales_rank),prod_group=VALUES(prod_group),total_qty=VALUES(total_qty),sell_qty=VALUES(sell_qty),unsell_qty=VALUES(unsell_qty),inv_age_0_to_90=VALUES(inv_age_0_to_90),inv_age_91_to_180=VALUES(inv_age_91_to_180),inv_age_181_to_270=VALUES(inv_age_181_to_270),inv_age_271_to_365=VALUES(inv_age_271_to_365),inv_age_365_plus=VALUES(inv_age_365_plus),unit_ship_24_hrs=VALUES(unit_ship_24_hrs),unit_ship_7_days=VALUES(unit_ship_7_days),unit_ship_30_days=VALUES(unit_ship_30_days),unit_ship_90_days=VALUES(unit_ship_90_days),unit_ship_180_days=VALUES(unit_ship_180_days),unit_ship_365_days=VALUES(unit_ship_365_days),weeks_of_cover_t7=VALUES(weeks_of_cover_t7),weeks_of_cover_t30=VALUES(weeks_of_cover_t30),weeks_of_cover_t90=VALUES(weeks_of_cover_t90),weeks_of_cover_t180=VALUES(weeks_of_cover_t180),weeks_of_cover_t365=VALUES(weeks_of_cover_t365),num_afn_new_sellers=VALUES(num_afn_new_sellers),num_afn_user_sellers=VALUES(num_afn_user_sellers),curr=VALUES(curr),your_price=VALUES(your_price),sale_price=VALUES(sale_price),low_afn_new_price=VALUES(low_afn_new_price),low_afn_used_price=VALUES(low_afn_used_price),low_mfn_new_price=VALUES(low_mfn_new_price),low_mfn_used_price=VALUES(low_mfn_used_price),qty_charged_12=VALUES(qty_charged_12),qty_charger_long_term=VALUES(qty_charger_long_term),qty_removal_in_progress=VALUES(qty_removal_in_progress),projected_12=VALUES(projected_12),per_unit_vol=VALUES(per_unit_vol),is_hazmat=VALUES(is_hazmat),in_bound_qty=VALUES(in_bound_qty),asin_limit=VALUES(asin_limit),inbound_recomm_qty=VALUES(inbound_recomm_qty),qty_charged_6=VALUES(qty_charged_6),projected_6=VALUES(projected_6),added_by=VALUES(added_by)";

                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}


public function process_stranded_inv_ui_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "stranded_inv_ui_data";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while(!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                //print_r($buffer);
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                }
                if($i>=1 && !empty($buffer[7]) )
                {
                    $primary_action= isset($buffer[0])?$this->db->escape($buffer[0]):'';
                    $date_stran= isset($buffer[1])?$buffer[1]:'';
                    $date_stranded=date("Y-m-d", strtotime($date_stran));
                    //print_r( $date_stran);
                    //print_r($date_stranded);
                    //die();

                    $status_primary= isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $status_secondary= isset($buffer[4])?$this->db->escape($buffer[4]):'';
                    $error_msg= isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $asin= isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $sku= isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $fnsku= isset($buffer[9])?$this->db->escape($buffer[9]):'';
                    $prod_name= isset($buffer[10])?$this->db->escape($buffer[10]):'';
                    $cond= isset($buffer[11])?$this->db->escape($buffer[11]):'';
                    $fulfilled_by= isset($buffer[12])?$this->db->escape($buffer[12]):'';
                    $fulfillable_qty= isset($buffer[13])?$this->db->escape($buffer[13]):'';
                    $your_price= isset($buffer[14])?$this->db->escape($buffer[14]):'';
                    $unfulfillable_qty= isset($buffer[15])?$this->db->escape($buffer[15]):'';
                    $reserved_qty= isset($buffer[16])?$this->db->escape($buffer[16]):'';
                    $inbound_shipped_qty= isset($buffer[17])?$this->db->escape($buffer[17]):'';

                    $bulk_data[]="(".$primary_action.",'".$date_stranded."',".$status_primary.",".$status_secondary.",".$error_msg.",".$asin.",".$sku.",".$fnsku.",".$prod_name.",".$cond.",".$fulfilled_by.",".$fulfillable_qty.",".$your_price.",".$unfulfillable_qty.",".$reserved_qty.",".$inbound_shipped_qty.",".$user_id.")";
                }

                if(isset($bulk_data) && count($bulk_data)==500)
                {
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT INTO `stranded_inv_ui_data`(primary_action,date_stranded,status_primary,status_secondary,error_msg,asin,sku,fnsku,prod_name,cond,fulfilled_by,fulfillable_qty,your_price,unfulfillable_qty,reserved_qty,inbound_shipped_qty,user_id)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    primary_action=VALUES(primary_action),date_stranded=VALUES(date_stranded),status_primary=VALUES(status_primary),status_secondary=VALUES(status_secondary),error_msg=VALUES(error_msg),asin=VALUES(asin),sku=VALUES(sku),fnsku=VALUES(fnsku),prod_name=VALUES(prod_name),cond=VALUES(cond),fulfilled_by=VALUES(fulfilled_by),fulfillable_qty=VALUES(fulfillable_qty),your_price=VALUES(your_price),unfulfillable_qty=VALUES(unfulfillable_qty),reserved_qty=VALUES(reserved_qty),inbound_shipped_qty=VALUES(inbound_shipped_qty),user_id=VALUES(user_id);";
                    //print_r($qi);
                    $checkAddUpdateData = $this->db->query($qi);
                    if (!$checkAddUpdateData) {
                        $error = get_instance()->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $error;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {
                $quer=implode(',',$bulk_data);
                $qi="INSERT INTO `stranded_inv_ui_data`(primary_action,date_stranded,status_primary,status_secondary,error_msg,asin,sku,fnsku,prod_name,cond,fulfilled_by,fulfillable_qty,your_price,unfulfillable_qty,reserved_qty,inbound_shipped_qty,user_id)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                primary_action=VALUES(primary_action),date_stranded=VALUES(date_stranded),status_primary=VALUES(status_primary),status_secondary=VALUES(status_secondary),error_msg=VALUES(error_msg),asin=VALUES(asin),sku=VALUES(sku),fnsku=VALUES(fnsku),prod_name=VALUES(prod_name),cond=VALUES(cond),fulfilled_by=VALUES(fulfilled_by),fulfillable_qty=VALUES(fulfillable_qty),your_price=VALUES(your_price),unfulfillable_qty=VALUES(unfulfillable_qty),reserved_qty=VALUES(reserved_qty),inbound_shipped_qty=VALUES(inbound_shipped_qty),user_id=VALUES(user_id);";

                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}

public function process_fba_storage_fee_data($user_id,$report_file,$country,$request_type)
{
    // print_r($country);
    // die();
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "rep_fba_storage_fee_data";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while(!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                //print_r($country);
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    $asin= isset($buffer[0])?$this->db->escape($buffer[0]):'';
                    $fnsku= isset($buffer[1])?$this->db->escape($buffer[1]):'';
                    $product_name= isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $fulfillment_center= isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $country_code= isset($buffer[4])?$this->db->escape(str_replace('GB','UK',$buffer[4])):'';
                    $longest_side= isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $median_side= isset($buffer[6])?$this->db->escape($buffer[6]):'';
                    $shortest_side= isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $measurement_units= isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $weight= isset($buffer[9])?$this->db->escape($buffer[9]):'';
                    $weight_units= isset($buffer[10])?$this->db->escape($buffer[10]):'';
                    $item_volume= isset($buffer[11])?$this->db->escape($buffer[11]):'';
                    $volume_units= isset($buffer[12])?$this->db->escape($buffer[12]):'';
    			     //print_r($country_code);
                    if($country!='US')
                    {
                        $product_size_tier="''";
                        $average_quantity_on_hand= isset($buffer[13])?$this->db->escape($buffer[13]):'';
                        $average_quantity_pending_removal= isset($buffer[14])?$this->db->escape($buffer[14]):'';
                        $estimated_total_item_volume= isset($buffer[15])?$this->db->escape($buffer[15]):'';
                        $month_of_charge= isset($buffer[16])?$this->db->escape($buffer[16]):'';
                        $storage_rate= isset($buffer[17])?$this->db->escape($buffer[17]):'';
                        $currency= isset($buffer[18])?$this->db->escape($buffer[18]):'';
                        $estimated_monthly_storage_fee= isset($buffer[19])?$this->db->escape($buffer[19]):'';
                    }
                else
                {
                    $product_size_tier= isset($buffer[13])?$this->db->escape($buffer[13]):'';
                    $average_quantity_on_hand= isset($buffer[14])?$this->db->escape($buffer[14]):'';
                    $average_quantity_pending_removal= isset($buffer[15])?$this->db->escape($buffer[15]):'';
                    $estimated_total_item_volume= isset($buffer[16])?$this->db->escape($buffer[16]):'';
                    $month_of_charge= isset($buffer[17])?$this->db->escape($buffer[17]):'';
                    $storage_rate= isset($buffer[18])?$this->db->escape($buffer[18]):'';
                    $currency= isset($buffer[19])?$this->db->escape($buffer[19]):'';
                    $estimated_monthly_storage_fee= isset($buffer[20])?$this->db->escape($buffer[20]):'';
                }
                $bulk_data[]="(".$asin.",".$fnsku.",".$product_name.",".$fulfillment_center.",".$country_code.",".$longest_side.",".$median_side.",".$shortest_side.",".$measurement_units.",".$weight.",".$weight_units.",".$item_volume.",".$volume_units.",".$product_size_tier.",".$average_quantity_on_hand.",".$average_quantity_pending_removal.",".$estimated_total_item_volume.",".$month_of_charge.",".$storage_rate.",".$currency.",".$estimated_monthly_storage_fee.",".$user_id.")";
            }
    		//if($i>=1 && !empty($buffer[0]) && $country!='US')
            //{
            //
            //       $asin= isset($buffer[0])?$this->db->escape($buffer[0]):'';
            //       $fnsku= isset($buffer[1])?$this->db->escape($buffer[1]):'';
    		//	   $prod_name= isset($buffer[2])?$this->db->escape($buffer[2]):'';
    		//	   $pro_name=str_replace("'","",$prod_name);
    		//	   $product_name=trim($pro_name);
    		//	   $fulfillment_center= isset($buffer[3])?$this->db->escape($buffer[3]):'';
            //       $country_code= isset($buffer[4])?$this->db->escape($buffer[4]):'';
            //       $longest_side= isset($buffer[5])?$this->db->escape($buffer[5]):'';
            //       $median_side= isset($buffer[6])?$this->db->escape($buffer[6]):'';
            //       $shortest_side= isset($buffer[7])?$this->db->escape($buffer[7]):'';
            //       $measurement_units= isset($buffer[8])?$this->db->escape($buffer[8]):'';
    		//	   $weight= isset($buffer[9])?$this->db->escape($buffer[9]):'';
    		//	   $weight_units= isset($buffer[10])?$this->db->escape($buffer[10]):'';
    		//	   $item_volume= isset($buffer[11])?$this->db->escape($buffer[11]):'';
            //       $volume_units= isset($buffer[12])?$this->db->escape($buffer[12]):'';
    		//	   $product_size_tier='';
    		//	   $average_quantity_on_hand= isset($buffer[13])?$this->db->escape($buffer[13]):'';
    		//	   $average_quantity_pending_removal= isset($buffer[14])?$this->db->escape($buffer[14]):'';
    		//	   $estimated_total_item_volume= isset($buffer[15])?$this->db->escape($buffer[15]):'';
    		//	   $month_of_charge= isset($buffer[16])?$this->db->escape($buffer[16]):'';
    		//	   $storage_rate= isset($buffer[17])?$this->db->escape($buffer[17]):'';
            //       $currency= isset($buffer[18])?$this->db->escape($buffer[18]):'';
    		//	   $estimated_monthly_storage_fee= isset($buffer[19])?$this->db->escape($buffer[19]):'';
    		//
            //
            //     $bulk_data[]="(".$asin.",".$fnsku.",".$product_name.",".$fulfillment_center.",".$country_code.",".$longest_side.",".$median_side.",".$shortest_side.",".$measurement_units.",".$weight.",".$weight_units.",".$item_volume.",".$volume_units.",".$product_size_tier.",".$average_quantity_on_hand.",".$average_quantity_pending_removal.",".$estimated_total_item_volume.",".$month_of_charge.",".$storage_rate.",".$currency.",".$estimated_monthly_storage_fee.",".$user_id.")";
            //
            //}

            if(isset($bulk_data) && count($bulk_data)==500)
            {
                $quer=implode(',',$bulk_data);
                $qi="INSERT INTO `rep_fba_storage_fee_data`(`asin`,`fnsku`,`product_name`,`fulfillment_center`,`country_code`,`longest_side`,`median_side`,`shortest_side`,`measurement_units`,`weight`,`weight_units`,`item_volume`,`volume_units`,`product_size_tier`,`average_quantity_on_hand`,`average_quantity_pending_removal`,`estimated_total_item_volume`,`month_of_charge`,`storage_rate`,`currency`,`estimated_monthly_storage_fee`,`usr_id`)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                asin=VALUES(asin),fnsku=VALUES(fnsku),product_name=VALUES(product_name),fulfillment_center=VALUES(fulfillment_center),country_code=VALUES(country_code),longest_side=VALUES(longest_side),median_side=VALUES(median_side),shortest_side=VALUES(shortest_side),measurement_units=VALUES(measurement_units),weight=VALUES(weight),weight_units=VALUES(weight_units),item_volume=VALUES(item_volume),volume_units=VALUES(volume_units),product_size_tier=VALUES(product_size_tier),average_quantity_on_hand=VALUES(average_quantity_on_hand),average_quantity_pending_removal=VALUES(average_quantity_pending_removal),estimated_total_item_volume=VALUES(estimated_total_item_volume),month_of_charge=VALUES(month_of_charge),storage_rate=VALUES(storage_rate),currency=VALUES(currency),estimated_monthly_storage_fee=VALUES(estimated_monthly_storage_fee),usr_id=VALUES(usr_id);";
                //print_r($qi);

                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                    break;
                }
                unset($bulk_data);
                unset($quer);
            }
            $i++;
        }
        if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
        {
            $quer=implode(',',$bulk_data);
            $qi="INSERT INTO `rep_fba_storage_fee_data`(`asin`,`fnsku`,`product_name`,`fulfillment_center`,`country_code`,`longest_side`,`median_side`,`shortest_side`,`measurement_units`,`weight`,`weight_units`,`item_volume`,`volume_units`,`product_size_tier`,`average_quantity_on_hand`,`average_quantity_pending_removal`,`estimated_total_item_volume`,`month_of_charge`,`storage_rate`,`currency`,`estimated_monthly_storage_fee`,`usr_id`)VALUES
            $quer
            ON DUPLICATE KEY
            UPDATE
            asin=VALUES(asin),fnsku=VALUES(fnsku),product_name=VALUES(product_name),fulfillment_center=VALUES(fulfillment_center),country_code=VALUES(country_code),longest_side=VALUES(longest_side),median_side=VALUES(median_side),shortest_side=VALUES(shortest_side),measurement_units=VALUES(measurement_units),weight=VALUES(weight),weight_units=VALUES(weight_units),item_volume=VALUES(item_volume),volume_units=VALUES(volume_units),product_size_tier=VALUES(product_size_tier),average_quantity_on_hand=VALUES(average_quantity_on_hand),average_quantity_pending_removal=VALUES(average_quantity_pending_removal),estimated_total_item_volume=VALUES(estimated_total_item_volume),month_of_charge=VALUES(month_of_charge),storage_rate=VALUES(storage_rate),currency=VALUES(currency),estimated_monthly_storage_fee=VALUES(estimated_monthly_storage_fee),usr_id=VALUES(usr_id);";
            //print_r($qi);

            $checkAddUpdateData = $this->db->query($qi);
            if (!$checkAddUpdateData) {
                $error = get_instance()->db->error();
                $responseData['response'] = 2;
                $responseData['msg']      = $error;
                $responseData['fileName'] = $report_file;
                return $responseData;
            }
            unset($bulk_data);
            unset($quer);
        }
        fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}



public function process_fba_shipment_replacement_data($user_id,$report_file,$country,$request_type)
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "fba_shipment_replacement_data";
    try {
        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            while(!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                //print_r($buffer);
                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                }
                if($i>=1 && !empty($buffer[7]) )
                {
                    $shipment_date= isset($buffer[0])?$this->db->escape($buffer[0]):'';
                    $sku= isset($buffer[1])?$this->db->escape($buffer[1]):'';
                    $asin= isset($buffer[2])?$this->db->escape($buffer[2]):'';
                    $fulfillment_center_id= isset($buffer[3])?$this->db->escape($buffer[3]):'';
                    $original_fulfillment_center_id= isset($buffer[4])?$this->db->escape($buffer[4]):'';
                    $quantity= isset($buffer[5])?$this->db->escape($buffer[5]):'';
                    $replacement_reason_code= isset($buffer[6])?$this->db->escape($buffer[6]):'';
                    $replacement_amazon_order_id= isset($buffer[7])?$this->db->escape($buffer[7]):'';
                    $original_amazon_order_id= isset($buffer[8])?$this->db->escape($buffer[8]):'';
                    $bulk_data[]="(".$shipment_date.",".$sku.",".$asin.",".$fulfillment_center_id.",".$original_fulfillment_center_id.",".$quantity.",".$replacement_reason_code.",".$replacement_amazon_order_id.",".$original_amazon_order_id.",".$user_id.")";
                }

                if(isset($bulk_data) && count($bulk_data)==500)
                {
                    $quer=implode(',',$bulk_data);
                    $qi="INSERT INTO `fba_shipment_replacement_data`(shipment_date,sku,asin,fulfillment_center_id,original_fulfillment_center_id,quantity,replacement_reason_code,replacement_amazon_order_id,original_amazon_order_id,user_id)VALUES
                    $quer
                    ON DUPLICATE KEY
                    UPDATE
                    shipment_date=VALUES(shipment_date),sku=VALUES(sku),asin=VALUES(asin),fulfillment_center_id=VALUES(fulfillment_center_id),quantity=VALUES(quantity),replacement_reason_code=VALUES(replacement_reason_code),replacement_amazon_order_id=VALUES(replacement_amazon_order_id),original_amazon_order_id=VALUES(original_amazon_order_id),user_id=VALUES(user_id);";
                    //print_r($qi);

                    $checkAddUpdateData = $this->db->query($qi);
                    if (!$checkAddUpdateData) {
                        $error = get_instance()->db->error();
                        $responseData['response'] = 2;
                        $responseData['msg']      = $error;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                    unset($bulk_data);
                    unset($quer);
                }
                $i++;
            }
            if(isset($bulk_data) && count($bulk_data)<500 && count($bulk_data)>0)
            {
                $quer=implode(',',$bulk_data);
                $qi="INSERT INTO `fba_shipment_replacement_data`(shipment_date,sku,asin,fulfillment_center_id,original_fulfillment_center_id,quantity,replacement_reason_code,replacement_amazon_order_id,original_amazon_order_id,user_id)VALUES
                $quer
                ON DUPLICATE KEY
                UPDATE
                shipment_date=VALUES(shipment_date),sku=VALUES(sku),asin=VALUES(asin),fulfillment_center_id=VALUES(fulfillment_center_id),quantity=VALUES(quantity),replacement_reason_code=VALUES(replacement_reason_code),replacement_amazon_order_id=VALUES(replacement_amazon_order_id),original_amazon_order_id=VALUES(original_amazon_order_id),user_id=VALUES(user_id);";
                //print_r($qi);
                $checkAddUpdateData = $this->db->query($qi);
                if (!$checkAddUpdateData) {
                    $error = get_instance()->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $error;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
                unset($bulk_data);
                unset($quer);
            }
            fclose($fp);
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}




public function process_fba_estimated_fees_txt_data($user_id,$report_file,$country,$request_type,$usr=array())
{
    $responseData = array();
    $responseData['response'] = 1;
    $responseData['msg'] = "";
    $responseData['table_name'] = "fba_estimated_fees_txt_data";
    try {
        $dataBaseColumnName = array(
                                        'sku'   => 'sku',
                                        'fnsku' => 'fnsku',
                                        'asin'  => 'asin',
                                        'product-name'  => 'product_name',
                                        'product-group' => 'product_group',
                                        'brand' => 'brand',
                                        'fulfilled-by'  => 'fulfilled_by',
                                        'has-local-inventory'   => 'has_local_inventory',
                                        'your-price'    => 'your_price',
                                        'sales-price'   => 'sales_price',
                                        'longest-side'  => 'longest_side',
                                        'median-side'   => 'median_side',
                                        'shortest-side' => 'shortest_side',
                                        'length-and-girth'  => 'length_and_girth',
                                        'unit-of-dimension' => 'unit_of_dimension',
                                        'item-package-weight'   => 'item_package_weight',
                                        'unit-of-weight'    => 'unit_of_weight',
                                        'currency'  => 'currency',
                                        'estimated-fee-total'   => 'estimated_fee_total',
                                        'estimated-referral-fee-per-unit'   => 'estimated_referral_fee_per_unit',
                                        'estimated-variable-closing-fee'    => 'estimated_variable_closing_fee',
                                        'expected-efn-fulfilment-fee-per-unit-uk'   => 'expected_efn_fulfilment_fee_per_unit_uk',
                                        'expected-efn-fulfilment-fee-per-unit-de'   => 'expected_efn_fulfilment_fee_per_unit_de',
                                        'expected-efn-fulfilment-fee-per-unit-fr'   => 'expected_efn_fulfilment_fee_per_unit_fr',
                                        'expected-efn-fulfilment-fee-per-unit-it'   => 'expected_efn_fulfilment_fee_per_unit_it',
                                        'expected-efn-fulfilment-fee-per-unit-es'   => 'expected_efn_fulfilment_fee_per_unit_es',
                                        'estimated-order-handling-fee-per-order'    => 'estimated_order_handling_fee_per_order',
                                        'estimated-pick-pack-fee-per-unit'  => 'estimated_pick_pack_fee_per_unit',
                                        'estimated-weight-handling-fee-per-unit'    => 'estimated_weight_handling_fee_per_unit',
                                        'estimated-future-fee'  => 'estimated_future_fee',
                                        'estimated-future-order-handling-fee-per-order' => 'estimated_future_order_handling_fee_per_order',
                                        'estimated-future-pick-pack-fee-per-unit'   => 'estimated_future_pick_pack_fee_per_unit',
                                        'estimated-future-weight-handling-fee-per-unit' => 'estimated_future_weight_handling_fee_per_unit',
                                        'expected-future-fulfillment-fee-per-unit'  => 'expected_future_fulfillment_fee_per_unit'
                                    );

        if($country!='US') {
            $dataBaseColumnName['product-size-weight-band'] = 'product_size_tier';
            $dataBaseColumnName['expected-domestic-fulfilment-fee-per-unit'] = 'expected_fulfillment_fee_per_unit';
        } else {
            $dataBaseColumnName['product-size-tier'] = 'product_size_tier';
            $dataBaseColumnName['expected-fulfillment-fee-per-unit'] = 'expected_fulfillment_fee_per_unit';
        }

        $fp=fopen($report_file,'r');
        if ($fp)
        {
            $i=0;
            $csvColumnNames = array();
            $fba_estimated_fees_txt_data_bulk_query_data = array();
            while(!feof($fp))
            {
                $buffer = fgetcsv($fp,0,"\t");
                if ($i===0) {
                    $csvColumnNames = $buffer;
                }

                if($i>=1 && isset($buffer[0]) && !empty(trim($buffer[0])))
                {
                    if (strpos($buffer[0],"ErrorResponse")) {
                        $responseData['response'] = 2;
                        $getFileErrorXml     = simplexml_load_file($report_file);
                        $getFileErrorEncode  = $getFileErrorXml;
                        $responseData['msg'] = $getFileErrorEncode;
                        $responseData['fileName'] = $report_file;
                        return $responseData;
                        break;
                    }
                }
                if($i>=1 && !empty($buffer[7])) {
                    $insertData = array();
                    foreach ($csvColumnNames as $key => $csvColumnName) {
                        if (in_array($csvColumnName, $csvColumnNames) && array_key_exists($csvColumnName,$dataBaseColumnName)) {
                            $getMatchKey = array_search($csvColumnName, $csvColumnNames);
                            if (isset($dataBaseColumnName[$csvColumnName])) {
                                $insertData[$dataBaseColumnName[$csvColumnName]] = $buffer[$getMatchKey];
                            }
                        }
                    }

                    foreach ($dataBaseColumnName as $checkColumnName) {
                        if (!array_key_exists($checkColumnName,$insertData)) {
                            $insertData[$checkColumnName] = "";
                        }
                    }

                    $feftd_sku                                      = (isset($insertData["sku"]) && "" != trim($insertData["sku"])) ? $this->db->escape($insertData["sku"]) : $this->db->escape('');
                    $feftd_fnsku                                    = (isset($insertData["fnsku"]) && "" != trim($insertData["fnsku"])) ? $this->db->escape($insertData["fnsku"]) : $this->db->escape('');
                    $feftd_asin                                     = (isset($insertData["asin"]) && "" != trim($insertData["asin"])) ? $this->db->escape($insertData["asin"]) : $this->db->escape('');
                    $feftd_product_name                             = (isset($insertData["product_name"]) && "" != trim($insertData["product_name"])) ? $this->db->escape($insertData["product_name"]) : $this->db->escape('');
                    $feftd_product_group                            = (isset($insertData["product_group"]) && "" != trim($insertData["product_group"])) ? $this->db->escape($insertData["product_group"]) : $this->db->escape('');
                    $feftd_brand                                    = (isset($insertData["brand"]) && "" != trim($insertData["brand"])) ? $this->db->escape($insertData["brand"]) : $this->db->escape('');
                    $feftd_fulfilled_by                             = (isset($insertData["fulfilled_by"]) && "" != trim($insertData["fulfilled_by"])) ? $this->db->escape($insertData["fulfilled_by"]) : $this->db->escape('');
                    $feftd_your_price                               = (isset($insertData["your_price"]) && "" != trim($insertData["your_price"])) ? $this->db->escape($insertData["your_price"]) : $this->db->escape('0.00');
                    $feftd_sales_price                              = (isset($insertData["sales_price"]) && "" != trim($insertData["sales_price"])) ? $this->db->escape($insertData["sales_price"]) : $this->db->escape('0.00');
                    $feftd_longest_side                             = (isset($insertData["longest_side"]) && "" != trim($insertData["longest_side"])) ? $this->db->escape($insertData["longest_side"]) : $this->db->escape('0.00');
                    $feftd_median_side                              = (isset($insertData["median_side"]) && "" != trim($insertData["median_side"])) ? $this->db->escape($insertData["median_side"]) : $this->db->escape('0.00');
                    $feftd_shortest_side                            = (isset($insertData["shortest_side"]) && "" != trim($insertData["shortest_side"])) ? $this->db->escape($insertData["shortest_side"]) : $this->db->escape('0.00');
                    $feftd_length_and_girth                         = (isset($insertData["length_and_girth"]) && "" != trim($insertData["length_and_girth"])) ? $this->db->escape($insertData["length_and_girth"]) : $this->db->escape('0.00');
                    $feftd_unit_of_dimension                        = (isset($insertData["unit_of_dimension"]) && "" != trim($insertData["unit_of_dimension"])) ? $this->db->escape($insertData["unit_of_dimension"]) : $this->db->escape('');
                    $feftd_item_package_weight                      = (isset($insertData["item_package_weight"]) && "" != trim($insertData["item_package_weight"])) ? $this->db->escape($insertData["item_package_weight"]) : $this->db->escape('0.00');
                    $feftd_unit_of_weight                           = (isset($insertData["unit_of_weight"]) && "" != trim($insertData["unit_of_weight"])) ? $this->db->escape($insertData["unit_of_weight"]) : $this->db->escape('');
                    $feftd_product_size_tier                        = (isset($insertData["product_size_tier"]) && "" != trim($insertData["product_size_tier"])) ? $this->db->escape($insertData["product_size_tier"]) : $this->db->escape('');
                    $feftd_currency                                 = (isset($insertData["currency"]) && "" != trim($insertData["currency"])) ? $this->db->escape($insertData["currency"]) : $this->db->escape('');
                    $feftd_estimated_fee_total                      = (isset($insertData["estimated_fee_total"]) && "" != trim($insertData["estimated_fee_total"])) ? $this->db->escape($insertData["estimated_fee_total"]) : $this->db->escape('0.00');
                    $feftd_estimated_referral_fee_per_unit          = (isset($insertData["estimated_referral_fee_per_unit"]) && "" != trim($insertData["estimated_referral_fee_per_unit"])) ? $this->db->escape($insertData["estimated_referral_fee_per_unit"]) : $this->db->escape('0.00');
                    $feftd_estimated_variable_closing_fee           = (isset($insertData["estimated_variable_closing_fee"]) && "" != trim($insertData["estimated_variable_closing_fee"])) ? $this->db->escape($insertData["estimated_variable_closing_fee"]) : $this->db->escape('0.00');
                    $feftd_estimated_order_handling_fee_per_order   = (isset($insertData["estimated_order_handling_fee_per_order"]) && "" != trim($insertData["estimated_order_handling_fee_per_order"])) ? $this->db->escape($insertData["estimated_order_handling_fee_per_order"]) : $this->db->escape('0.00');
                    $feftd_estimated_pick_pack_fee_per_unit         = (isset($insertData["estimated_pick_pack_fee_per_unit"]) && "" != trim($insertData["estimated_pick_pack_fee_per_unit"])) ? $this->db->escape($insertData["estimated_pick_pack_fee_per_unit"]) : $this->db->escape('0.00');
                    $feftd_estimated_weight_handling_fee_per_unit   = (isset($insertData["estimated_weight_handling_fee_per_unit"]) && "" != trim($insertData["estimated_weight_handling_fee_per_unit"])) ? $this->db->escape($insertData["estimated_weight_handling_fee_per_unit"]) : $this->db->escape('0.00');
                    $feftd_expected_fulfillment_fee_per_unit        = (isset($insertData["expected_fulfillment_fee_per_unit"]) && "" != trim($insertData["expected_fulfillment_fee_per_unit"])) ? $this->db->escape($insertData["expected_fulfillment_fee_per_unit"]) : $this->db->escape('0.00');
                    $feftd_estimated_future_fee                     = (isset($insertData["estimated_future_fee"]) && "" != trim($insertData["estimated_future_fee"])) ? $this->db->escape($insertData["estimated_future_fee"]) : $this->db->escape('0.00');
                    $feftd_estimated_future_order_handling_fee_per_order = (isset($insertData["estimated_future_order_handling_fee_per_order"]) && "" != trim($insertData["estimated_future_order_handling_fee_per_order"])) ? $this->db->escape($insertData["estimated_future_order_handling_fee_per_order"]) : $this->db->escape('0.00');
                    $feftd_estimated_future_pick_pack_fee_per_unit  = (isset($insertData["estimated_future_pick_pack_fee_per_unit"]) && "" != trim($insertData["estimated_future_pick_pack_fee_per_unit"])) ? $this->db->escape($insertData["estimated_future_pick_pack_fee_per_unit"]) : $this->db->escape('0.00');
                    $feftd_estimated_future_weight_handling_fee_per_unit = (isset($insertData["estimated_future_weight_handling_fee_per_unit"]) && "" != trim($insertData["estimated_future_weight_handling_fee_per_unit"])) ? $this->db->escape($insertData["estimated_future_weight_handling_fee_per_unit"]) : $this->db->escape('0.00');
                    $feftd_expected_future_fulfillment_fee_per_unit = (isset($insertData["expected_future_fulfillment_fee_per_unit"]) && "" != trim($insertData["expected_future_fulfillment_fee_per_unit"])) ? $this->db->escape($insertData["expected_future_fulfillment_fee_per_unit"]) : $this->db->escape('0.00');
                    $feftd_has_local_inventory                      = (isset($insertData["has_local_inventory"]) && "" != trim($insertData["has_local_inventory"])) ? $this->db->escape($insertData["has_local_inventory"]) : $this->db->escape('');
                    $feftd_expected_efn_fulfilment_fee_per_unit_uk  = (isset($insertData["expected_efn_fulfilment_fee_per_unit_uk"]) && "" != trim($insertData["expected_efn_fulfilment_fee_per_unit_uk"])) ? $this->db->escape($insertData["expected_efn_fulfilment_fee_per_unit_uk"]) : $this->db->escape('0.00');
                    $feftd_expected_efn_fulfilment_fee_per_unit_de  = (isset($insertData["expected_efn_fulfilment_fee_per_unit_de"]) && "" != trim($insertData["expected_efn_fulfilment_fee_per_unit_de"])) ? $this->db->escape($insertData["expected_efn_fulfilment_fee_per_unit_de"]) : $this->db->escape('0.00');
                    $feftd_expected_efn_fulfilment_fee_per_unit_fr  = (isset($insertData["expected_efn_fulfilment_fee_per_unit_fr"]) && "" != trim($insertData["expected_efn_fulfilment_fee_per_unit_fr"])) ? $this->db->escape($insertData["expected_efn_fulfilment_fee_per_unit_fr"]) : $this->db->escape('0.00');
                    $feftd_expected_efn_fulfilment_fee_per_unit_it  = (isset($insertData["expected_efn_fulfilment_fee_per_unit_it"]) && "" != trim($insertData["expected_efn_fulfilment_fee_per_unit_it"])) ? $this->db->escape($insertData["expected_efn_fulfilment_fee_per_unit_it"]) : $this->db->escape('0.00');
                    $feftd_expected_efn_fulfilment_fee_per_unit_es  = (isset($insertData["expected_efn_fulfilment_fee_per_unit_es"]) && "" != trim($insertData["expected_efn_fulfilment_fee_per_unit_es"])) ? $this->db->escape($insertData["expected_efn_fulfilment_fee_per_unit_es"]) : $this->db->escape('0.00');
                    $feftd_user_id                                  = $this->db->escape($user_id);
                    $feftd_report_feed_data                         = $this->db->escape(json_encode($usr));


                    $fba_estimated_fees_txt_data_bulk_query_data[] = "({$feftd_sku},{$feftd_fnsku},{$feftd_asin},{$feftd_product_name},{$feftd_product_group},{$feftd_brand},{$feftd_fulfilled_by},{$feftd_your_price},{$feftd_sales_price},{$feftd_longest_side},{$feftd_median_side},{$feftd_shortest_side},{$feftd_length_and_girth},{$feftd_unit_of_dimension},{$feftd_item_package_weight},{$feftd_unit_of_weight},{$feftd_product_size_tier},{$feftd_currency},{$feftd_estimated_fee_total},{$feftd_estimated_referral_fee_per_unit},{$feftd_estimated_variable_closing_fee},{$feftd_estimated_order_handling_fee_per_order},{$feftd_estimated_pick_pack_fee_per_unit},{$feftd_estimated_weight_handling_fee_per_unit},{$feftd_expected_fulfillment_fee_per_unit},{$feftd_estimated_future_fee},{$feftd_estimated_future_order_handling_fee_per_order},{$feftd_estimated_future_pick_pack_fee_per_unit},{$feftd_estimated_future_weight_handling_fee_per_unit},{$feftd_expected_future_fulfillment_fee_per_unit},{$feftd_has_local_inventory},{$feftd_expected_efn_fulfilment_fee_per_unit_uk},{$feftd_expected_efn_fulfilment_fee_per_unit_de},{$feftd_expected_efn_fulfilment_fee_per_unit_fr},{$feftd_expected_efn_fulfilment_fee_per_unit_it},{$feftd_expected_efn_fulfilment_fee_per_unit_es},{$feftd_user_id},{$feftd_report_feed_data})";

                    if (!empty($fba_estimated_fees_txt_data_bulk_query_data) && count($fba_estimated_fees_txt_data_bulk_query_data) > 200) {
                        $fba_estimated_fees_txt_data_bulk_query_data_implode = implode(',',$fba_estimated_fees_txt_data_bulk_query_data);
                        $fba_estimated_fees_txt_data_sql_query = "INSERT INTO `fba_estimated_fees_txt_data` (`sku`, `fnsku`, `asin`, `product_name`, `product_group`, `brand`, `fulfilled_by`, `your_price`, `sales_price`, `longest_side`, `median_side`, `shortest_side`, `length_and_girth`, `unit_of_dimension`, `item_package_weight`, `unit_of_weight`, `product_size_tier`, `currency`, `estimated_fee_total`, `estimated_referral_fee_per_unit`, `estimated_variable_closing_fee`, `estimated_order_handling_fee_per_order`, `estimated_pick_pack_fee_per_unit`, `estimated_weight_handling_fee_per_unit`, `expected_fulfillment_fee_per_unit`, `estimated_future_fee`, `estimated_future_order_handling_fee_per_order`, `estimated_future_pick_pack_fee_per_unit`, `estimated_future_weight_handling_fee_per_unit`, `expected_future_fulfillment_fee_per_unit`, `has_local_inventory`, `expected_efn_fulfilment_fee_per_unit_uk`, `expected_efn_fulfilment_fee_per_unit_de`, `expected_efn_fulfilment_fee_per_unit_fr`, `expected_efn_fulfilment_fee_per_unit_it`, `expected_efn_fulfilment_fee_per_unit_es`, `user_id`, `report_feed_data`)
                                                         VALUES
                                                         $fba_estimated_fees_txt_data_bulk_query_data_implode
                                                         ON DUPLICATE KEY
                                                         UPDATE
                                                         sku=VALUES(sku), fnsku=VALUES(fnsku), asin=VALUES(asin), product_name=VALUES(product_name), product_group=VALUES(product_group), brand=VALUES(brand), fulfilled_by=VALUES(fulfilled_by), your_price=VALUES(your_price), sales_price=VALUES(sales_price), longest_side=VALUES(longest_side), median_side=VALUES(median_side), shortest_side=VALUES(shortest_side), length_and_girth=VALUES(length_and_girth), unit_of_dimension=VALUES(unit_of_dimension), item_package_weight=VALUES(item_package_weight), unit_of_weight=VALUES(unit_of_weight), product_size_tier=VALUES(product_size_tier), currency=VALUES(currency), estimated_fee_total=VALUES(estimated_fee_total), estimated_referral_fee_per_unit=VALUES(estimated_referral_fee_per_unit), estimated_variable_closing_fee=VALUES(estimated_variable_closing_fee), estimated_order_handling_fee_per_order=VALUES(estimated_order_handling_fee_per_order), estimated_pick_pack_fee_per_unit=VALUES(estimated_pick_pack_fee_per_unit), estimated_weight_handling_fee_per_unit=VALUES(estimated_weight_handling_fee_per_unit), expected_fulfillment_fee_per_unit=VALUES(expected_fulfillment_fee_per_unit), estimated_future_fee=VALUES(estimated_future_fee), estimated_future_order_handling_fee_per_order=VALUES(estimated_future_order_handling_fee_per_order), estimated_future_pick_pack_fee_per_unit=VALUES(estimated_future_pick_pack_fee_per_unit), estimated_future_weight_handling_fee_per_unit=VALUES(estimated_future_weight_handling_fee_per_unit), expected_future_fulfillment_fee_per_unit=VALUES(expected_future_fulfillment_fee_per_unit), has_local_inventory=VALUES(has_local_inventory), expected_efn_fulfilment_fee_per_unit_uk=VALUES(expected_efn_fulfilment_fee_per_unit_uk), expected_efn_fulfilment_fee_per_unit_de=VALUES(expected_efn_fulfilment_fee_per_unit_de), expected_efn_fulfilment_fee_per_unit_fr=VALUES(expected_efn_fulfilment_fee_per_unit_fr), expected_efn_fulfilment_fee_per_unit_it=VALUES(expected_efn_fulfilment_fee_per_unit_it), expected_efn_fulfilment_fee_per_unit_es=VALUES(expected_efn_fulfilment_fee_per_unit_es), user_id=VALUES(user_id), report_feed_data=VALUES(report_feed_data)";

                        $check_fba_estimated_fees_txt_data_sql_query = $this->db->query($fba_estimated_fees_txt_data_sql_query);
                        if (!$check_fba_estimated_fees_txt_data_sql_query) {
                            $getError = $this->db->error();
                            $responseData['response'] = 2;
                            $responseData['msg']      = $getError;
                            $responseData['fileName'] = $report_file;
                            return $responseData;
                            break;
                        }
                        $fba_estimated_fees_txt_data_bulk_query_data = array();
                    }
                }
                $i++;
            }
            fclose($fp);

            if (!empty($fba_estimated_fees_txt_data_bulk_query_data) && count($fba_estimated_fees_txt_data_bulk_query_data) > 0) {
                $fba_estimated_fees_txt_data_bulk_query_data_implode = implode(',',$fba_estimated_fees_txt_data_bulk_query_data);
                $fba_estimated_fees_txt_data_sql_query = "INSERT INTO `fba_estimated_fees_txt_data` (`sku`, `fnsku`, `asin`, `product_name`, `product_group`, `brand`, `fulfilled_by`, `your_price`, `sales_price`, `longest_side`, `median_side`, `shortest_side`, `length_and_girth`, `unit_of_dimension`, `item_package_weight`, `unit_of_weight`, `product_size_tier`, `currency`, `estimated_fee_total`, `estimated_referral_fee_per_unit`, `estimated_variable_closing_fee`, `estimated_order_handling_fee_per_order`, `estimated_pick_pack_fee_per_unit`, `estimated_weight_handling_fee_per_unit`, `expected_fulfillment_fee_per_unit`, `estimated_future_fee`, `estimated_future_order_handling_fee_per_order`, `estimated_future_pick_pack_fee_per_unit`, `estimated_future_weight_handling_fee_per_unit`, `expected_future_fulfillment_fee_per_unit`, `has_local_inventory`, `expected_efn_fulfilment_fee_per_unit_uk`, `expected_efn_fulfilment_fee_per_unit_de`, `expected_efn_fulfilment_fee_per_unit_fr`, `expected_efn_fulfilment_fee_per_unit_it`, `expected_efn_fulfilment_fee_per_unit_es`, `user_id`, `report_feed_data`)
                                                 VALUES
                                                 $fba_estimated_fees_txt_data_bulk_query_data_implode
                                                 ON DUPLICATE KEY
                                                 UPDATE
                                                 sku=VALUES(sku), fnsku=VALUES(fnsku), asin=VALUES(asin), product_name=VALUES(product_name), product_group=VALUES(product_group), brand=VALUES(brand), fulfilled_by=VALUES(fulfilled_by), your_price=VALUES(your_price), sales_price=VALUES(sales_price), longest_side=VALUES(longest_side), median_side=VALUES(median_side), shortest_side=VALUES(shortest_side), length_and_girth=VALUES(length_and_girth), unit_of_dimension=VALUES(unit_of_dimension), item_package_weight=VALUES(item_package_weight), unit_of_weight=VALUES(unit_of_weight), product_size_tier=VALUES(product_size_tier), currency=VALUES(currency), estimated_fee_total=VALUES(estimated_fee_total), estimated_referral_fee_per_unit=VALUES(estimated_referral_fee_per_unit), estimated_variable_closing_fee=VALUES(estimated_variable_closing_fee), estimated_order_handling_fee_per_order=VALUES(estimated_order_handling_fee_per_order), estimated_pick_pack_fee_per_unit=VALUES(estimated_pick_pack_fee_per_unit), estimated_weight_handling_fee_per_unit=VALUES(estimated_weight_handling_fee_per_unit), expected_fulfillment_fee_per_unit=VALUES(expected_fulfillment_fee_per_unit), estimated_future_fee=VALUES(estimated_future_fee), estimated_future_order_handling_fee_per_order=VALUES(estimated_future_order_handling_fee_per_order), estimated_future_pick_pack_fee_per_unit=VALUES(estimated_future_pick_pack_fee_per_unit), estimated_future_weight_handling_fee_per_unit=VALUES(estimated_future_weight_handling_fee_per_unit), expected_future_fulfillment_fee_per_unit=VALUES(expected_future_fulfillment_fee_per_unit), has_local_inventory=VALUES(has_local_inventory), expected_efn_fulfilment_fee_per_unit_uk=VALUES(expected_efn_fulfilment_fee_per_unit_uk), expected_efn_fulfilment_fee_per_unit_de=VALUES(expected_efn_fulfilment_fee_per_unit_de), expected_efn_fulfilment_fee_per_unit_fr=VALUES(expected_efn_fulfilment_fee_per_unit_fr), expected_efn_fulfilment_fee_per_unit_it=VALUES(expected_efn_fulfilment_fee_per_unit_it), expected_efn_fulfilment_fee_per_unit_es=VALUES(expected_efn_fulfilment_fee_per_unit_es), user_id=VALUES(user_id), report_feed_data=VALUES(report_feed_data)";

                $check_fba_estimated_fees_txt_data_sql_query = $this->db->query($fba_estimated_fees_txt_data_sql_query);
                if (!$check_fba_estimated_fees_txt_data_sql_query) {
                    $getError = $this->db->error();
                    $responseData['response'] = 2;
                    $responseData['msg']      = $getError;
                    $responseData['fileName'] = $report_file;
                    return $responseData;
                }
            }
        }
        return $responseData;
    } catch(Exception $e) {
        $responseData['response'] = 2;
        $responseData['msg']      = $e->getMessage();
        $responseData['fileName'] = $report_file;
        return $responseData;
    }
}


public function process_report_data_for_testing($user_id,$report_file,$country,$request_type)
{
    $fp=fopen($report_file,'r');
    if ($fp)
    {
       $i=0;
       while(!feof($fp))
       {
        $buffer = fgetcsv($fp,0,"\t");
        print_r($buffer);
        if($i==2)
        {
          die();
      }
      $i++;
  }

  fclose($fp);
}
}


}
?>
