<?php
$slugs = array( 'name', 'slug', 'version', 'author', 'author_profile', 'contributors', 'requires', 'tested', 'rating', 'num_ratings', 'homepage', 'description', 'homepage', 'short_description', 'download' );

// Key 1 is the query send to the script from Alfred
$query = $argv[1];

// Break query into components
$pieces = explode( ' ', $query );

if ( count( $pieces ) < 2 ) {
	echo 'You must provide at least a "slug" and a search query (e.g., "wpplugin version debug bar").';
	die();
}

$slug = array_shift( $pieces );
$number = is_numeric( end( $pieces ) ) ? ( int ) array_pop( $pieces ) : 1;
$search_term = implode( ' ', $pieces );

// Whitelist slugs
if ( ! in_array( $slug, $slugs ) ) {
	echo 'Invalid slug. Please use one of the valid slugs: ' . implode( ', ', $slugs );
	die();
}

// Set values need for cURL post fields
$action = 'query_plugins';
$args = array(
	'page' => 1,
	'per_page' => $number,
	'search' => $search_term
);

$postfields = 'action=' . $action . '&request=' . serialize( (object) $args );

// Initiate and set cURL options
$handle = curl_init();
curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 15 );
curl_setopt( $handle, CURLOPT_TIMEOUT, 15 );
curl_setopt( $handle, CURLOPT_URL, 'http://api.wordpress.org/plugins/info/1.0/' );
curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, true );
curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, true );
curl_setopt( $handle, CURLOPT_POST, true );
curl_setopt( $handle, CURLOPT_POSTFIELDS, $postfields );

// Execute the request
$theResponse = curl_exec( $handle );

// Get the object
$results = unserialize( $theResponse );
$count = count( $results->plugins );

// Print result if available
if ( $count > 0 ) {
	$delimiter = $count > 1 ? "\n" : '';

	// Print only the requested piece of information
	foreach ( $results->plugins as $key => $plugin ) {
		echo $plugin->name . ' (' . $slug . ')' . ":\n";

		if ( 'contributors' != $slug ) {
			echo strip_tags( $plugin->$slug ) . $delimiter . $delimiter;
		} elseif ( 'download' == $slug ) {
			echo '<a href="http://downloads.wordpress.org/plugin/' . $plugin->slug . '.zip">Download</a>' . $delimiter . $delimiter;
		} else {
			if ( is_array( $plugin->$slug ) ) {
				echo strip_tags( implode( ', ', array_keys( $plugin->$slug ) ) ) . $delimiter . $delimiter;
			} else {
				echo 'An unexpected error occurred.';
				die();
			}
		} 
	}
} else {
	echo 'No results matched your query';
}