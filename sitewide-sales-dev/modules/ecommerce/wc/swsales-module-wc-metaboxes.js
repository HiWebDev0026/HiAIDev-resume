jQuery( document ).ready(
	function($) {

		// multiselects
		$( "#swsales_wc_coupon_select" ).selectWoo();

		// toggling the discount code input layout
		function swsales_wc_toggle_coupon() {
			var coupon_id = $( '#swsales_wc_coupon_select' ).val();

			if (coupon_id == 0) {
				$( '#swsales_wc_after_coupon_select' ).hide();
			} else {
				$( '#swsales_wc_edit_coupon' ).attr( 'href', swsales_wc_metaboxes.admin_url + 'post.php?action=edit&post=' + coupon_id );
				$( '#swsales_wc_after_coupon_select' ).show();
			}
		}
		$( '#swsales_wc_coupon_select' ).change(
			function(){
				$( '#swsales_wc_coupon_expiry_error' ).hide();
				swsales_wc_toggle_coupon();
			}
		);
		swsales_wc_toggle_coupon();

		// create new coupon AJAX
		$( '#swsales_wc_create_coupon' ).click(
			function() {
				$( '#swsales_wc_create_coupon' ).attr( 'disabled','disabled' );
				var data = {
					'action': 'swsales_wc_create_coupon',
					'swsales_wc_id': $( '#post_ID' ).val(),
					'swsales_start': $( '#swsales_start_day' ).val(),
					'swsales_end': $( '#swsales_end_day' ).val(),
					'nonce': swsales_wc_metaboxes.create_coupon_nonce,
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
							$( '#swsales_wc_coupon_select' ).append( '<option value="' + response.coupon_id + '">' + response.coupon_code + '</option>' );
							$( '#swsales_wc_coupon_select' ).val( response.coupon_id );
							swsales_wc_toggle_coupon();
						}
					}
				);
			}
		);
	}
);
