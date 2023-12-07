jQuery( document ).ready(
	function($) {

		// multiselects
		$( "#swsales_pmpro_discount_code_select" ).selectWoo();
		$( "#swsales_pmpro_hide_sale_levels_select" ).selectWoo();
		$( "#swsales_pmpro_hide_levels_select" ).selectWoo();

		// toggling the discount code input layout
		function swsales_pmpro_toggle_discount_code() {
			var discount_code_id = $( '#swsales_pmpro_discount_code_select' ).val();

			if (discount_code_id == 0) {
				$( '#swsales_pmpro_after_discount_code_select' ).hide();
			} else {
				$( '#swsales_pmpro_edit_discount_code' ).attr( 'href', swsales_pmpro_metaboxes.admin_url + 'admin.php?page=pmpro-discountcodes&edit=' + discount_code_id );
				$( '#swsales_pmpro_after_discount_code_select' ).show();
			}
		}
		$( '#swsales_pmpro_discount_code_select' ).change(
			function(){
				$( '#swsales_pmpro_discount_code_error' ).hide();
				swsales_pmpro_toggle_discount_code();
			}
		);
		swsales_pmpro_toggle_discount_code();

		// create new discount code AJAX
		$( '#swsales_pmpro_create_discount_code' ).click(
			function() {
				$( '#swsales_pmpro_create_discount_code' ).attr( 'disabled','disabled' );
				var data = {
					'action': 'swsales_pmpro_create_discount_code',
					'swsales_pmpro_id': $( '#post_ID' ).val(),
					'swsales_start': $( '#swsales_start_day' ).val(),
					'swsales_end': $( '#swsales_end_day' ).val(),
					'nonce': swsales_pmpro_metaboxes.create_discount_code_nonce,
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
							$( '#swsales_pmpro_discount_code_select' ).append( '<option value="' + response.code.id + '">' + response.code.code + '</option>' );
							$( '#swsales_pmpro_discount_code_select' ).val( response.code.id );
							swsales_pmpro_toggle_discount_code();
						}
					}
				);
			}
		);
	}
);
