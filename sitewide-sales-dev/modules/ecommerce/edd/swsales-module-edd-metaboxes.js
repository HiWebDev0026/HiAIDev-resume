jQuery( document ).ready(
	function($) {

		// multiselects
		$( "#swsales_edd_coupon_select" ).selectWoo();

		// toggling the discount code input layout
		function swsales_edd_toggle_coupon() {
			var coupon_id = $( '#swsales_edd_coupon_select' ).val();

			if (coupon_id == 0) {
				$( '#swsales_edd_after_coupon_select' ).hide();
			} else {
				$( '#swsales_edd_edit_coupon' ).attr( 'href', swsales_edd_metaboxes.admin_url + 'edit.php?post_type=download&page=edd-discounts&edd-action=edit_discount&discount=' + coupon_id );
				$( '#swsales_edd_after_coupon_select' ).show();
			}
		}
		$( '#swsales_edd_coupon_select' ).change(
			function(){
				$( '#swsales_edd_coupon_expiry_error' ).hide();
				swsales_edd_toggle_coupon();
			}
		);
		swsales_edd_toggle_coupon();

		// create new coupon AJAX
		$( '#swsales_edd_create_coupon' ).click(
			function() {
				$( '#swsales_edd_create_coupon' ).attr( 'disabled','disabled' );
				var data = {
					'action': 'swsales_edd_create_coupon',
					'swsales_edd_id': $( '#post_ID' ).val(),
					'swsales_start': $( '#swsales_start_day' ).val() + ' ' + $( '#swsales_start_time' ).val() + ':00',
					'swsales_end': $( '#swsales_end_day' ).val() + ' ' + $( '#swsales_end_time' ).val() + ':59',
					'nonce': swsales_edd_metaboxes.create_coupon_nonce,
				};
				$.post(
					ajaxurl,
					data,
					function(response) {
						response = $.parseJSON( response );
						if (response.status == 'error' ) {
							alert( response.error );
						} else {
							// success
							$( '#swsales_edd_coupon_select' ).append( '<option value="' + response.coupon_id + '">' + response.coupon_code + '</option>' );
							$( '#swsales_edd_coupon_select' ).val( response.coupon_id );
							swsales_edd_toggle_coupon();
						}
					}
				);
			}
		);
	}
);
