jQuery( document ).ready(
	function($) {
		// create new reusable block banner AJAX
		$( '#swsales_create_reusable_block_banner' ).click(
			function() {
				$( '#swsales_create_reusable_block_banner' ).attr( 'disabled','disabled' );
				var data = {
					'action': 'swsales_create_reusable_block_banner',
					'swsales_id': $( '#post_ID' ).val(),
					'swsales_reusable_block_banner_title': $( '#title' ).val(),
					'nonce': swsales_blocks.create_reusable_block_banner_nonce,
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
							$( '#swsales_banner_block_id' ).append( '<option value="' + response.post.ID + '">' + response.post.post_title + '</option>' );
							$( '#swsales_banner_block_id' ).val( response.post.ID );
							swsales_toggle_reusable_block_banner();
						}
					}
				);
			}
		);

		// toggling the reusable block input layout
		function swsales_toggle_reusable_block_banner() {
			var reusable_block_id = $( '#swsales_banner_block_id' ).val();
			if (reusable_block_id == '-1') {
				$( '#swsales_after_reusable_block_select' ).hide();
				$( '#swsales_banner_block_id' ).prop( 'disabled', true );
			} else {
				$( '#swsales_banner_block_id' ).prop( 'disabled', false );
				$( '#swsales_edit_banner_block' ).attr( 'href', swsales.admin_url + 'post.php?post=' + reusable_block_id + '&action=edit' );
				$( '#swsales_after_reusable_block_select' ).show();
				$( '#swsales_banner_block_id_not_found' ).remove();
			}
		}
		$( '#swsales_banner_block_id' ).change(
			function(){
				swsales_toggle_reusable_block_banner();
			}
		);
		swsales_toggle_reusable_block_banner();
	}
);
