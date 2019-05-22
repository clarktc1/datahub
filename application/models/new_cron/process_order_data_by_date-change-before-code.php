<?php
if($i>=1 && !empty($buffer[0]) )
{
    $order_id= isset($buffer[0])?$this->db->escape($buffer[0]):'';
    $po_date= isset($buffer[2])?$this->db->escape($buffer[2]):'';
    $last_update= isset($buffer[3])?$this->db->escape($buffer[3]):'';             
    $order_status= isset($buffer[4])?$buffer[4]:'';             
    if($order_status=='Cancelled')
    {
        $order_status='Canceled'; 
    }
    $order_status=!empty($order_status)?$this->db->escape($order_status):'';
    $fullfill=isset($buffer[5])?$this->db->escape($buffer[5]):'';
    $sale_channel= isset($buffer[6]);
    $cnt=explode('.',$sale_channel);
    $contry=$cnt[count($cnt)-1];
    $country2=strtoupper($contry); 
    $cont2=str_replace('COM','US',(string)$country2); 
    $sale_channel= isset($buffer[6])?$this->db->escape($buffer[6]):'';    

    $ship_service=isset($buffer[9])?$this->db->escape($buffer[9]):'';
    $title=isset($buffer[10])?$this->db->escape($buffer[10]):'';
    $sku=isset($buffer[11])?$this->db->escape($buffer[11]):'';
    $asin=isset($buffer[12])?$this->db->escape($buffer[12]):'';
    $itm_status=isset($buffer[13])?$this->db->escape($buffer[13]):'';
    $qty=isset($buffer[14])?$this->db->escape($buffer[14]):'';
    $curr=isset($buffer[15])?$this->db->escape($buffer[15]):'';
    $itm_price=isset($buffer[16])?$this->db->escape($buffer[16]):'';
    $itm_tax=isset($buffer[17])?$this->db->escape($buffer[17]):'';
    $ship_price=isset($buffer[18])?$this->db->escape($buffer[18]):'';
    $ship_tax=isset($buffer[19])?$this->db->escape($buffer[19]):'';
    $gift_price=isset($buffer[20])?$this->db->escape($buffer[20]):'';
    $gift_tax=isset($buffer[21])?$this->db->escape($buffer[21]):'';
    $promo_disc=isset($buffer[22])?$this->db->escape($buffer[22]):'';
    $ship_disc=isset($buffer[23])?$this->db->escape($buffer[23]):'';
    $ship_city=isset($buffer[24])?$this->db->escape($buffer[24]):'';
    $ship_state=isset($buffer[25])?$this->db->escape($buffer[25]):'';
    $ship_post=isset($buffer[26])?$this->db->escape($buffer[26]):'';
    $ship_country=isset($buffer[27])?$this->db->escape($buffer[27]):'';
    $promo_id=isset($buffer[28])?$this->db->escape($buffer[28]):'';

    $bulk_data[]="(".$order_id.",".$po_date.",".$last_update.",".$order_status.",".$fullfill.",'".$cont2."',".$sku.",".$asin.",".$itm_status.",".$gift_price.",".$title.",".$ship_service.",".$qty.",".$curr.",".$itm_price.",".$itm_tax.",".$ship_price.",".$ship_tax.",".$gift_tax.",".$promo_disc.",".$ship_disc.",".$ship_city.",".$ship_state.",".$ship_post.",".$ship_country.",".$promo_id.",".$user_id.")";
}

if(isset($bulk_data) && count($bulk_data)==500)
{
    $quer=implode(',',$bulk_data);
    $qi="INSERT INTO `rep_orders_data_order_date_list`(order_id,po_date,last_update_date,ord_status,fulfillment,sales_channel,ord_sku,asin,itm_status,gift_price,title,ship_service,qty,currency,itm_price,itm_tax,ship_price,ship_tax,gift_tax,itm_promo_discount,itm_ship_discount,ship_city,ship_state,ship_post,ship_country,promo_id,user_id)VALUES 
    $quer 
    ON DUPLICATE KEY 
    UPDATE
    order_id=VALUES(order_id),po_date=VALUES(po_date),last_update_date=VALUES(last_update_date),ord_status=VALUES(ord_status),fulfillment=VALUES(fulfillment),sales_channel=VALUES(sales_channel),ord_sku=VALUES(ord_sku),asin=VALUES(asin),itm_status=VALUES(itm_status),gift_price=VALUES(gift_price),title=VALUES(title),ship_service=VALUES(ship_service),currency=VALUES(currency),itm_price=VALUES(itm_price),itm_tax=VALUES(itm_tax),ship_price=VALUES(ship_price),ship_tax=VALUES(ship_tax),gift_tax=VALUES(gift_tax),itm_promo_discount=VALUES(itm_promo_discount),itm_ship_discount=VALUES(itm_ship_discount),ship_city=VALUES(ship_city),ship_state=VALUES(ship_state),ship_post=VALUES(ship_post),ship_country=VALUES(ship_country),promo_id=VALUES(promo_id),user_id=VALUES(user_id);";
    $this->db->query($qi);
    unset($bulk_data);
    unset($quer);
}
$i++;