jQuery( document ).ready(
	function($) {
		function swsales_custom_toggle_automatic_discount() {
			if ( $( '#swsales_sale_type_select' ).val() === 'custom' ) {
				$( '#swsales_automatic_discount_select' ).parent().parent().hide();
			} else {
				$( '#swsales_automatic_discount_select' ).parent().parent().show();
			}
		}

		$( '#swsales_sale_type_select' ).change(
			function(){
				swsales_custom_toggle_automatic_discount();
			}
		);
		swsales_custom_toggle_automatic_discount();

		// Only allow numbers in order value field.
		$('#swsales_custom_average_order_value').on('keypress', function(e){
			return e.metaKey ||  // cmd/ctrl
				e.which <= 0 ||  // arrow keys
				e.which == 8 ||  // delete key
				e.which == 46 || // period key
				/[0-9]/.test(String.fromCharCode(e.which)); // numbers
		});
	}
);
