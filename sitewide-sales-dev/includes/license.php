<?php

if ( ! defined( 'SS_LICENSE_SERVER' ) ) {
    define( 'SS_LICENSE_SERVER', 'https://license.strangerstudios.com/' );
}

/**
 * Add a monthly check to see if license key is still active.
 */
function swsales_license_activation() {
    swsales_maybe_schedule_event( current_time( 'timestamp' ), 'monthly', 'swsales_license_check_key' );
}
register_activation_hook( __FILE__, 'swsales_license_activation' );

/**
 * Remove monthly check when plugin is deactivated.
 */
function swsales_license_deactivation() {
	wp_clear_scheduled_hook( 'swsales_license_check_key' );
}
register_deactivation_hook( __FILE__, 'swsales_license_deactivation' );

/**
 * Check to see if customer has a valid license key.
 * 
 * @param string $key the value of the license key.
 * @param string $type the type of key, often used is 'license'.
 * @param bool $force skip the cache when checking license key.
 */
function swsales_license_is_valid( $key = NULL, $type = NULL, $force = false ) {

    $swsales_license_check = get_option( 'swsales_license_check', false );

    if ( empty($force) && $swsales_license_check !== false && $swsales_license_check['enddate'] > current_time( 'timestamp' ) ) {

        if ( empty( $type ) ) {
            return true;
        } elseif ( $type == $swsales_license_check['license'] ) {
            return true;
        } else {
            return false;
        }
    }

    // Get the key and site URL
    if ( empty( $key ) ) {
        $key = get_option( 'swsales_license_key', '' );
    }

    if ( ! empty( $key ) ) {
        return swsales_license_check_key( $key );
    } else {
        delete_option( 'swsales_license_check' );
        add_option( 'swsales_license_check', array( 'license' => false, 'enddate' => 0 ), NULL, 'no' );

        return false;
    }
}

/**
 * Check if license key is valid or not.
 * 
 * @param string $key the license key value we need to check.
 */
function swsales_license_check_key( $key = NULL ) {

    if ( empty( $key ) ) {
        $key = get_option( 'swsales_license_key' );
    }

    if ( ! empty( $key ) ) {
        $url = add_query_arg( array( 'license' => $key, 'domain' => site_url() ), SS_LICENSE_SERVER );

        $timeout = apply_filters( 'swsales_license_check_key_timeout', 5 );
        $r = wp_remote_get( $url, array( 'timeout' => $timeout ) );

        if ( is_wp_error( $r ) ) {
           /// Handle errors here.
        } elseif ( ! empty( $r ) && wp_remote_retrieve_response_code( $r ) == 200 ) {

            $r = json_decode( $r['body'] );

            if ( $r->active == 1 ) {

				if ( ! empty( $r->enddate ) ) {
					$enddate = strtotime( $r->enddate, current_time( 'timestamp' ) );
                } else {
					$enddate = strtotime( '+1 Year', current_time( 'timestamp' ) );
                }

				delete_option('swsales_license_check');
				add_option('swsales_license_check', array( 'license' => $r->license, 'enddate' => $enddate), NULL, 'no');	
				return true;
			} elseif ( ! empty( $r->error ) ) {
				//invalid key
                /// Handle errors here.				
				delete_option('swsales_license_check');
				add_option('swsales_license_check', array('license'=>false, 'enddate'=>0), NULL, 'no');
                
			}
        }
    }
}
add_action( 'swsales_license_check_key', 'swsales_license_check_key' );

/**
 * Check if license prompt is paused.
 */
function swsales_license_pause() {
    if ( ! empty($_REQUEST['swsales_nag_paused'] ) && current_user_can( 'manage_options' ) ) {
		$swsales_nag_paused = current_time( 'timestamp' ) + ( 3600*24*7 );
		update_option('swsales_nag_paused', $swsales_nag_paused, 'no');
		
		return;
    }
}
add_action( 'admin_init', 'swsales_license_pause' );

/**
 * Add license nag to plugin admin page headers.
 */
function swsales_license_nag() {
    static $swsales_nagged;

    // Did we already nag the user?
    if ( ! empty( $swsales_nagged ) ) {
        return;
    }

    $swsales_nagged = true;

    // Blocked by constant
    if ( defined( 'SWSALES_LICENSE_NAG' ) && ! SWSALES_LICENSE_NAG ) {
        return;
    }

    if ( empty( $_REQUEST['post_type'] ) ) {
        return;
    }

    if ( $_REQUEST['post_type'] !== 'sitewide_sale') {
        return;        
    }

    // Don't load nag on license page.
    if ( ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] === 'sitewide_sales_license' ) {
        return;
    }

    // Bail if they have a valid license already.
    if ( swsales_license_is_valid() ) {
        return;
    }

    $swsales_nag_paused = get_option( 'swsales_nag_paused', 0 );

    if ( current_time( 'timestamp' ) < $swsales_nag_paused && $swsales_nag_paused < current_time( 'timestamp' ) * 3600*24*35 ) {
        return;
    }

    // Get the key to use later.
    $key = get_option( 'swsales_license_key' );

    //okay, show nag
    ?>
	<div class="<?php if ( ! empty( $key ) ) { ?>error<?php } else { ?>notice notice-warning<?php } ?> fade">
		<p>
            <?php
                //only show the invalid part if they've entered a key
				if ( ! empty( $key ) ) { ?>
					<strong><?php _e( 'Your Sitewide Sales license key is invalid or expired.', 'sitewide-sales' );?></strong>
				<?php } else { ?>
					<strong><?php _e( 'Enter your Sitewide Sales license key.', 'sitewide-sales' ); ?></strong>
				<?php }
			?>
            <?php _e( 'A license key is required to receive automatic updates and support.', 'sitewide-sales' );?>
            <a href="<?php echo admin_url('edit.php?post_type=sitewide_sale&page=sitewide_sales_license');?>"><?php _e('More Info', 'sitewide-sales' );?></a>&nbsp;|&nbsp;<a href="<?php echo add_query_arg('swsales_nag_paused', '1', $_SERVER['REQUEST_URI']);?>"><?php _e('Dismiss', 'sitewide-sales' );?></a>
        </p>
    </div>
    <?php
}
add_action( 'admin_notices', 'swsales_license_nag' );

/**
 * Setup plugins api filters
 *
 * @since 1.0
 */
function swsales_updates_setup() {
	add_filter( 'plugins_api', 'swsales_plugins_api', 10, 3 );
	add_filter( 'pre_set_site_transient_update_plugins', 'swsales_update_plugins_filter' );
	add_filter( 'http_request_args', 'swsales_http_request_args_for_addons', 10, 2 );
	add_action( 'update_option_swsales_license_key', 'swsales_reset_update_plugins_cache', 10, 2 );
}
add_action( 'init', 'swsales_updates_setup' );

/**
 * Get update information from license server.
 *
 * @since  1.0
 */
function swsales_get_updates() {
	static $connection_error = false;
    
    // check if forcing a pull from the server
	$updates = get_option( 'swsales_updates', array() );
	$updates_timestamp = get_option( 'swsales_updates_timestamp', 0 );

	// if no updates locally, we need to hit the server
	if ( empty( $updates ) || ! empty( $_REQUEST['force-check'] ) || current_time( 'timestamp' ) > $updates_timestamp + 86400 && ! $connection_error ) {
		/**
		 * Filter to change the timeout for this wp_remote_get() request.
		 *
		 * @since 1.0
		 *
		 * @param int $timeout The number of seconds before the request times out
		 */
		$timeout = apply_filters( 'swsales_get_update_timeout', 5 );

		// get em
		$remote_updates = wp_remote_get( SS_LICENSE_SERVER . 'addons/', $timeout );

		// make sure we have at least an array to pass back
		if ( empty( $updates ) ) {
			$updates = array();
		}

		// test response
		if ( is_wp_error( $remote_updates ) ) {
			// Error. Make sure to only show it once.
            if ( ! $connection_error ) {
                $connection_error = true;
                ?>
                <div class="error">
                <p><?php _e( 'Could not connect to the Stranger Studios License Server to get update information. Try again later.', 'error' ); ?></p>
                </div>
                <?php
            }
		} elseif ( ! empty( $remote_updates ) && $remote_updates['response']['code'] == 200 ) {
			// update addons in cache
			$updates = json_decode( wp_remote_retrieve_body( $remote_updates ), true );
			delete_option( 'swsales_updates' );
			add_option( 'swsales_updates', $updates, null, 'no' );
		}

		// save timestamp of last update
		delete_option( 'swsales_updates_timestamp' );
		add_option( 'swsales_updates_timestamp', current_time( 'timestamp' ), null, 'no' );
	}

	return $updates;
}

/**
 * Find an update by slug.
 *
 * @since 1.0
 *
 * @param object $slug  The identifying slug for the plugin (typically the directory name)
 * @return object $update containing plugin information or false if not found
 */
function swsales_get_update_by_slug( $slug ) {
	$updates = swsales_get_updates();

	if ( empty( $updates ) ) {
		return false;
	}

	foreach ( $updates as $update ) {
		if ( $update['Slug'] == $slug ) {
			return $update;
		}
	}

	return false;
}

/**
 * Infuse plugin update details when WordPress runs its update checker.
 *
 * @since 1.0
 *
 * @param object $value  The WordPress update object.
 * @return object $value Amended WordPress update object on success, default if object is empty.
 */
function swsales_update_plugins_filter( $value ) {

	// If no update object exists, return early.
	if ( empty( $value ) ) {
		return $value;
	}

	// get update information
	$updates = swsales_get_updates();

	// no addons?
	if ( empty( $updates ) ) {
		return $value;
	}

	// check addons
	foreach ( $updates as $update ) {
		// skip wordpress.org plugins
		if ( empty( $update['License'] ) || $update['License'] == 'wordpress.org' ) {
			continue;
		}

		// get data for plugin
		$plugin_file = $update['Slug'] . '/' . $update['Slug'] . '.php';
		$plugin_file_abs = WP_PLUGIN_DIR . '/' . $plugin_file;

		// couldn't find plugin, skip
		if ( ! file_exists( $plugin_file_abs ) ) {
			continue;
		} else {
			$plugin_data = get_plugin_data( $plugin_file_abs, false, true );
		}

		// compare versions
		if ( ! empty( $update['License'] ) && version_compare( $plugin_data['Version'], $update['Version'], '<' ) ) {
			$value->response[ $plugin_file ] = swsales_get_plugin_api_object_from_update( $update );
			$value->response[ $plugin_file ]->new_version = $update['Version'];
		}
	}

	// Return the update object.
	return $value;
}

/**
 * Disables SSL verification to prevent download package failures.
 *
 * @since 1.0
 *
 * @param array  $args  Array of request args.
 * @param string $url  The URL to be pinged.
 * @return array $args Amended array of request args.
 */
function swsales_http_request_args_for_addons( $args, $url ) {
	// If this is an SSL request and we are performing an upgrade routine, disable SSL verification.
	if ( strpos( $url, 'https://' ) !== false && strpos( $url, SS_LICENSE_SERVER ) !== false && strpos( $url, 'download' ) !== false ) {
		$args['sslverify'] = false;
	}

	return $args;
}

/**
 * Setup plugin updaters
 *
 * @since  1.0
 */
function swsales_plugins_api( $api, $action = '', $args = null ) {
	// Not even looking for plugin information? Or not given slug?
	if ( 'plugin_information' != $action || empty( $args->slug ) ) {
		return $api;
	}

	// get addon information
	$update = swsales_get_update_by_slug( $args->slug );

	// no addons?
	if ( empty( $update ) ) {
		return $api;
	}

	// handled by wordpress.org?
	if ( empty( $update['License'] ) || $update['License'] == 'wordpress.org' ) {
		return $api;
	}

	// Create a new stdClass object and populate it with our plugin information.
	$api = swsales_get_plugin_api_object_from_update( $update );
	return $api;
}

/**
 * Convert the format from the swsales_get_updates function to that needed for plugins_api
 *
 * @since  1.0
 */
function swsales_get_plugin_api_object_from_update( $update ) {
	$api                        = new stdClass();

	if ( empty( $update ) ) {
		return $api;
	}

	// add info
	$api->name                  = isset( $update['Name'] ) ? $update['Name'] : '';
	$api->slug                  = isset( $update['Slug'] ) ? $update['Slug'] : '';
	$api->plugin                = isset( $update['plugin'] ) ? $update['plugin'] : '';
	$api->version               = isset( $update['Version'] ) ? $update['Version'] : '';
	$api->author                = isset( $update['Author'] ) ? $update['Author'] : '';
	$api->author_profile        = isset( $update['AuthorURI'] ) ? $update['AuthorURI'] : '';
	$api->requires              = isset( $update['Requires'] ) ? $update['Requires'] : '';
	$api->tested                = isset( $update['Tested'] ) ? $update['Tested'] : '';
	$api->last_updated          = isset( $update['LastUpdated'] ) ? $update['LastUpdated'] : '';
	$api->homepage              = isset( $update['URI'] ) ? $update['URI'] : '';
	$api->download_link         = isset( $update['Download'] ) ? $update['Download'] : '';
	$api->package               = isset( $update['Download'] ) ? $update['Download'] : '';

	// add sections
	if ( !empty( $update['Description'] ) ) {
		$api->sections['description'] = $update['Description'];
	}
	if ( !empty( $update['Installation'] ) ) {
		$api->sections['installation'] = $update['Installation'];
	}
	if ( !empty( $update['FAQ'] ) ) {
		$api->sections['faq'] = $update['FAQ'];
	}
	if ( !empty( $update['Changelog'] ) ) {
		$api->sections['changelog'] = $update['Changelog'];
	}

	// get license key if one is available
	$key = get_option( 'swsales_license_key', '' );
	if ( ! empty( $key ) && ! empty( $api->download_link ) ) {
		$api->download_link = add_query_arg( 'key', $key, $api->download_link );
	}
	if ( ! empty( $key ) && ! empty( $api->package ) ) {
		$api->package = add_query_arg( 'key', $key, $api->package );
	}
	if ( empty( $api->upgrade_notice ) && ! swsales_license_is_valid( null, 'plus' ) ) {
		$api->upgrade_notice = __( 'Important: This plugin requires a valid Sitewide Sales license key to update.', 'sitewide-sales' );
	}

	return $api;
}

/**
 * Force update of plugin update data when the License key is updated
 *
 * @since 1.0
 *
 * @param array  $args  Array of request args.
 * @param string $url  The URL to be pinged.
 * @return array $args Amended array of request args.
 */
function swsales_reset_update_plugins_cache( $old_value, $value ) {
	delete_option( 'swsales_addons_timestamp' );
	delete_site_transient( 'update_themes' );
}

/**
 * Detect when trying to update Sitewide Sales without a valid license key.
 *
 * @since 1.0
 */
function swsales_admin_init_updating_plugins() {
	// if user can't edit plugins, then WP will catch this later
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	// updating one or more plugins via Dashboard -> Upgrade
	if ( basename( $_SERVER['SCRIPT_NAME'] ) == 'update.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'update-selected' && ! empty( $_REQUEST['plugins'] ) ) {
		// figure out which plugin we are updating
		$plugins = explode( ',', stripslashes( $_GET['plugins'] ) );
		$plugins = array_map( 'urldecode', $plugins );

		// look for updates
		$updates_plugin_names = array();
		$updates_plugins = array();
		foreach ( $plugins as $plugin ) {
			$slug = str_replace( '.php', '', basename( $plugin ) );
			$addon = swsales_get_update_by_slug( $slug );
			if ( ! empty( $addon ) && $addon['License'] == 'plus' ) {
				$updates_plugin_names[] = $addon['Name'];
				$updates_plugins[] = $plugin;
			}
		}
		unset( $plugin );

		// if Plus addons found, check license key
		if ( ! empty( $updates_plugins ) && ! swsales_license_is_valid( null, 'plus' ) ) {
			// show error
			$msg = __( 'You must have a <a href="https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/?utm_source=wp-admin&utm_pluginlink=bulkupdate">valid Sitewide Sales License Key</a> to update Sitewide Sales. The following plugins will not be updated:', 'sitewide-sales' );
			echo '<div class="error"><p>' . $msg . ' <strong>' . implode( ', ', $updates_plugin_names ) . '</strong></p></div>';
		}

		// can exit out of this function now
		return;
	}

	// upgrading just one or plugin via an update.php link
	if ( basename( $_SERVER['SCRIPT_NAME'] ) == 'update.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'upgrade-plugin' && ! empty( $_REQUEST['plugin'] ) ) {
		// figure out which plugin we are updating
		$plugin = urldecode( trim( $_REQUEST['plugin'] ) );

		$slug = str_replace( '.php', '', basename( $plugin ) );
		$update = swsales_get_update_by_slug( $slug );
		if ( ! empty( $update ) && ! swsales_license_is_valid( null, 'plus' ) ) {
			require_once( ABSPATH . 'wp-admin/admin-header.php' );

			echo '<div class="wrap"><h2>' . __( 'Update Plugin' ) . '</h2>';

			$msg = __( 'You must have a <a href="https://www.strangerstudios.com/wordpress-plugins/sitewide-sales/?utm_source=wp-admin&utm_pluginlink=update">valid Sitewide Sales License Key</a> to update Sitewide Salses.', 'sitewide-sales' );
			echo '<div class="error"><p>' . $msg . '</p></div>';

			echo '</div>';

			include( ABSPATH . 'wp-admin/admin-footer.php' );

			// can exit WP now
			exit;
		}
	}

	// updating via AJAX on the plugins page
	if ( basename( $_SERVER['SCRIPT_NAME'] ) == 'admin-ajax.php' && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'update-plugin' && ! empty( $_REQUEST['plugin'] ) ) {
		// figure out which plugin we are updating
		$plugin = urldecode( trim( $_REQUEST['plugin'] ) );

		$slug = str_replace( '.php', '', basename( $plugin ) );
		$update = swsales_get_update_by_slug( $slug );
		if ( ! empty( $update ) && ! swsales_license_is_valid( null, 'plus' ) ) {
			$msg = __( 'You must enter a valid Sitewide Sales License Key under Sitewide Sales > License to update this plugin.', 'sitewide-sales' );
			echo '<div class="error"><p>' . $msg . '</p></div>';

			// can exit WP now
			exit;
		}
	}
}
add_action( 'admin_init', 'swsales_admin_init_updating_plugins' );