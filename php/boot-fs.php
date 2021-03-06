<?php

// This file needs to parse without error in PHP < 5.3

if ( 'cli' !== PHP_SAPI ) {
	echo "Only CLI access.\n";
	die(-1);
}

if ( 0 == `/usr/bin/id -u` ) {
	echo "Do not run wp-cli as root.  Run as a user with this command:\n";
	echo "sudo -E -u \\#12345 -g \\#12345 wp\n";
	die(-1);
}

if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
	printf( "Error: WP-CLI requires PHP %s or newer. You are running version %s.\n", '5.3.0', PHP_VERSION );
	die(-1);
}

define( 'WP_CLI_ROOT', dirname( __DIR__ ) );

include WP_CLI_ROOT . '/php/wp-cli.php';

