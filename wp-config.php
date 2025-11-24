<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp1' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'password' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'Tf^APnT{P30^~2*ya<Ao]qC#Ay?=uuRgg>wrj2$s!w!D`S5N==5TVTlP2jB%ZI A' );
define( 'SECURE_AUTH_KEY',   ')]$#P+CeW(3QcLw/%,_51kln-:*f2am +ZtD21JF@q9I7zA-:cUkWKol;||&ReCq' );
define( 'LOGGED_IN_KEY',     '6&_,- yC,[FXB#)3P9FujWD7e@tfc@UMLzTq<fpQ ksv87F=Sw7{eU9fWvMBTDtQ' );
define( 'NONCE_KEY',         'kc+tAjWgJ9Yl5,}1BFDm9@iv*7K:olmDxnW]AWOYo5D0>4|O_$_$Z+zpQvuJQ Az' );
define( 'AUTH_SALT',         'I`D)gW)UDz,ipv6Xf#{-k%6RxlPfj>984&^E?JMw ]%}|0b3* JB^fe&-t?;.Ltl' );
define( 'SECURE_AUTH_SALT',  'T!rT4EM;J%)z8qhD>Bh;< KtVcV*5g6=P[TXJf7 AgXI-!KV,~`@3pfpMGmEH+q0' );
define( 'LOGGED_IN_SALT',    '#1EF$JavLvJZn-Jxc?[laF[4L9myJ<bX}:zh@8uPDOYq;n-;OpCB/fD,XIOJEpof' );
define( 'NONCE_SALT',        '7SflLc?_w 9& zTQp<Vk(<K,MKho u]hP/9p].=iVmty;M,mDVS-~91Y%IOJF.*<' );
define( 'WP_CACHE_KEY_SALT', 'y`YL7pKIaCMjjvgLYo0X[Lot+e&iIb1/hl>JuAKlIyV3j5AymSlII>pNO#UTwgVV' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
