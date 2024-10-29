jQuery(document).ready(function(e) {

jQuery('.free_products_coupondata_products').hide()
jQuery('#general_coupon_data #discount_type').change(function(){
  if(jQuery('#general_coupon_data #discount_type').val() == 'free_product'){
    jQuery('.free_products_coupondata_products').show();
  }else{
    jQuery('.free_products_coupondata_products').hide();
  }
});

if((jQuery('#_free_products_type').val() == 'cheapest') || (jQuery('#_free_products_type').val() == 'buy_x_get_y')){
  jQuery('._free_products_gift_id_field').hide();
}

jQuery('#_free_products_type').change(function(){
  if((jQuery('#_free_products_type').val() == 'cheapest') || (jQuery('#_free_products_type').val() == 'buy_x_get_y')){
    jQuery('._free_products_gift_id_field').hide();
  }else if (jQuery('#_free_products_type').val() == 'gift') {
    jQuery('._free_products_gift_id_field').show();
  }
});



});
