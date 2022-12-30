<?php
/*
Plugin Name: VK Posts
Description: Displays VK posts on a WordPress site using a shortcode.
*/

// Register the shortcode
add_shortcode( 'vk_posts', 'vk_posts_shortcode' );
function vk_posts_enqueue_scripts() {
	wp_enqueue_style( 'vk-posts', plugin_dir_url( __FILE__ ) . 'css/vk-posts.css' );
}
add_action( 'wp_enqueue_scripts', 'vk_posts_enqueue_scripts' );

function vk_get_group_id( $group_name ) {
	// Build the API request URL
	$request_url = 'https://api.vk.com/method/groups.getById?' . http_build_query( array(
		'group_id' => $group_name,
		'access_token' => '779c1d75779c1d75779c1d7557748dfdda7779c779c1d751405ad6093bc6fc5825d09a9', // Replace with your own access token
		'domain' => 'kreiser33', // Replace with your own domain
		'v' => '5.131', // API version
	) );

	// Send the API request
	$response = wp_remote_get( $request_url );

	// Check for errors
	if ( is_wp_error( $response ) ) {
		return false;
	}

	// Decode the API response
	$response_body = json_decode( wp_remote_retrieve_body( $response ) );

	// Check for errors in the API response
	if ( ! empty( $response_body->error ) ) {
		return false;
	}

	// Return the group's ID
	return $response_body->response[0]->id;
}

function vk_posts_shortcode( $atts ) {
	// Extract the shortcode attributes
	extract( shortcode_atts( array(
		'group_name' => '',
		'count' => 5,
		'offset' => 0,
	), $atts ) );

	// Make sure a group name is specified
	if ( empty( $group_name ) ) {
		return 'Error: No VK group name specified.';
	}

	// Get the group's ID by its name
	$group_id = vk_get_group_id( $group_name );
	if ( ! $group_id ) {
		return 'Error: Invalid VK group name.';
	}

	// Build the API request URL
	$request_url = 'https://api.vk.com/method/wall.get?' . http_build_query( array(
		'owner_id' => '-'.$group_id,
		'count' => $count,
		'offset' => $offset,
		'access_token' => '779c1d75779c1d75779c1d7557748dfdda7779c779c1d751405ad6093bc6fc5825d09a9', // Replace with your own access token
		'domain' => 'kreiser33', // Replace with your own domain
		'v' => '5.131', // API version
	) );

	// Send the API request
	$response = wp_remote_get( $request_url );

	// Check for errors
	if ( is_wp_error( $response ) ) {
		return 'Error: ' . $response->get_error_message();
	}
	// Decode the API response
	$response_body = json_decode( wp_remote_retrieve_body( $response ) );

	// Check for errors in the API response
	if ( ! empty( $response_body->error ) ) {
		return 'Error: ' . $response_body->error->error_msg;
	}

	// Build the output HTML
	$output = '<div class="vk-posts">';
	foreach ( $response_body->response->items as $item ) {
		$output .= '<div class="vk-post">';
		$output .= '<h2 class="vk-post-title">' . esc_html( $item->text ) . '</h2>';
        
		$output .= '<div class="vk-post-date">' . date( 'F j, Y', $item->date ) . '</div>';
		$output .= '</div>';
	}
	$output .= '</div>';

	return $output;
}

/**
 * Gets the group's ID by its name using the VK API's groups.getById method.
 *
 * @param string $group
