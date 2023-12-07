/**
 * General frontend JavaScript for banner display and sale performance tracking.
 */

// Get the banner and landing page tracking cookie data.
function swsales_get_tracking_cookie() {
	var cookie_string = wpCookies.get( 'swsales_' + swsales.sitewide_sale_id + '_tracking', '/' );
	var cookie_array;
	if ( null == cookie_string ) {
		cookie_array = {'banner': 0, 'landing_page': 0};
	} else {
		// get array from the cookie text
		var parts    = cookie_string.split( ';' );
		cookie_array = {'banner': parts[0], 'landing_page': parts[1]};
	}

	return cookie_array;
}

// Set the banner and landing page tracking cookie data.
function swsales_set_tracking_cookie(cookie_array) {
	var cookie_string = cookie_array.banner + ';' + cookie_array.landing_page;
	wpCookies.set( 'swsales_' + swsales.sitewide_sale_id + '_tracking', cookie_string, 86400 * 30, '/' );
}

// Update the sitewide sale report data.
function swsales_send_ajax(report) {
	jQuery.post(
	    swsales.ajax_url,
	    {
	        'action': 'swsales_ajax_tracking',
	        'report': report,
					'sitewide_sale_id': swsales.sitewide_sale_id
	    },
	    function(response) {
	        //console.log('The server responded: ', response);
	    }
	);
}

// Get the banner view cookie data.
function swsales_get_banner_cookie() {
	var cookie_string = wpCookies.get( 'swsales_' + swsales.sitewide_sale_id + '_banner', '/' );
	var cookie_array;
	if ( null == cookie_string ) {
		cookie_val = 0;
	} else {
		cookie_val = cookie_string;
	}

	return cookie_val;
}

// Set the banner view cookie data.
function swsales_set_banner_cookie(cookie_val) {
	var cookie_string = cookie_val;
	wpCookies.set( 'swsales_' + swsales.sitewide_sale_id + '_banner', cookie_string, 0, '/' );
}

// Track banner and landing page views.
function swsales_track() {
	var trackingcookie = swsales_get_tracking_cookie();
	if ( jQuery( '.swsales-banner' ).length ) {
		if ( trackingcookie['banner'] == 0 ) {
			trackingcookie['banner'] = 1;
			swsales_send_ajax( 'swsales_banner_impressions' );
			swsales_set_tracking_cookie( trackingcookie );
		}
	}

	if ( swsales.landing_page == 1 ) {
		if ( trackingcookie['landing_page'] == 0 ) {
			trackingcookie['landing_page'] = 1;
			swsales_send_ajax( 'swsales_landing_page_visits' );
			swsales_set_tracking_cookie( trackingcookie );
		}
	}
}

// Run logic to hide and show banner based on settings and user cookie.
function swsales_banner_close_behavior() {
	// Set cookie and hide or show banner based on sale settings.
	if ( swsales.banner_close_behavior == 'session' ) {
		var bannercookie = swsales_get_banner_cookie();
		jQuery('.swsales-dismiss').on( 'click', function() {
			bannercookie = 1;
			swsales_set_banner_cookie( bannercookie );
			jQuery(this).closest('.swsales-banner').hide();			
		});
		if ( bannercookie == 1 ) {
			jQuery('.swsales-banner').hide();
		} else {
			jQuery('.swsales-banner').show();
		}
	} else {
		jQuery('.swsales-dismiss').on( 'click', function() {			
			jQuery(this).closest('.swsales-banner').hide();			
		});
		jQuery('.swsales-banner').show();
	}
}

// Logic to build the countdown timer based on sale start or end date.
function getTimeRemaining(endtime) {
	const total = Date.parse(endtime) - Date.parse(new Date());
	const seconds = Math.floor((total / 1000) % 60);
	const minutes = Math.floor((total / 1000 / 60) % 60);
	const hours = Math.floor((total / (1000 * 60 * 60)) % 24);
	const days = Math.floor(total / (1000 * 60 * 60 * 24));
	return {
		total,
		days,
		hours,
		minutes,
		seconds
	};
}

function initializeClock(id, endtime) {
	const clock = document.getElementById(id);
	const daysSpan = clock.querySelector('.swsalesDays');
	const hoursSpan = clock.querySelector('.swsalesHours');
	const minutesSpan = clock.querySelector('.swsalesMinutes');
	const secondsSpan = clock.querySelector('.swsalesSeconds');

	function updateClock() {
		const t = getTimeRemaining(endtime);

		daysSpan.innerHTML = t.days;
		hoursSpan.innerHTML = ('0' + t.hours).slice(-2);
		minutesSpan.innerHTML = ('0' + t.minutes).slice(-2);
		secondsSpan.innerHTML = ('0' + t.seconds).slice(-2);

		if (t.total <= 0) {
		  clearInterval(timeinterval);
		}
	}

	updateClock();
	const timeinterval = setInterval(updateClock, 1000);
}

jQuery( document ).ready(
	function() {
		///console.log(swsales);
		swsales_track();
		swsales_banner_close_behavior();
	}
);
