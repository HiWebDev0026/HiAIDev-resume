jQuery( document ).ready(
	function($) {
		$( "#swsales_banner_location_select" ).selectWoo();

		function swsales_banner_swsales_toggle_css_hint() {
			var banner = $( '#swsales_banner_location_select' ).val();

			if (typeof banner == 'undefined' ) {
				return;
			}

			$( '.swsales_banner_css_selectors' ).hide();
			$( '.swsales_banner_css_selectors[data-swsales-banner=' + banner + ']' ).show();
		}
		swsales_banner_swsales_toggle_css_hint();
		$( '#swsales_banner_location_select' ).change(
			function(){
				swsales_banner_swsales_toggle_css_hint();
			}
		);
		swsales_banner_swsales_toggle_css_hint();
	}
);
