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
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpresswebsite' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'Vt!grj6#yFb9mHN^%fX] yRi>I %L^(?2|I90g+*2-@{zxXkoE1G0L/F]*Dc@2~?' );
define( 'SECURE_AUTH_KEY',  ']yn8,%S+.30Ne_X*t7[y]>Hm*a:0:@,9dMW==~:]gV*J2K2]G|p}xsrsx[RN-KTA' );
define( 'LOGGED_IN_KEY',    '-f.i|@leU>ckPsf!,*yK38Um;&;hrOCREc$Z@=OQ~1R5?XyKE(h4*8g$>#`L GWn' );
define( 'NONCE_KEY',        'HK >V@Imh-5%7(x=x1m^-$Ey1Y|#yWjTIc`2dnYiu]1^#I_JwSOca=^oXVz2P/?T' );
define( 'AUTH_SALT',        'I{+[BZ+LUH~|?|r_ wW]4GON~7kOrx6L`A_W`tKILe(6%xs<X;^>xu}kH:W/e28-' );
define( 'SECURE_AUTH_SALT', 'sj;o[bI:ZQ_y;y-, $#BSKCimgf0jcV0)i+:ET)al)33vq4Wdu}uI)>KwH-S*z#a' );
define( 'LOGGED_IN_SALT',   'Ripl]>EVJ<jGjYR:{3Mqr.fS=NfT#5Z~R(m{&RF23>}?h+Wy8dQz[~5R`xr/NHiv' );
define( 'NONCE_SALT',       'q-|&QgB<MhaY[s/>hx{ ru9|bY@~kK1@pvvMGtm~{N`7s/B*|g%a-6s:Jq*hTW4P' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

define('WP_HOME','http://localhost/wordpress/home/');
define('WP_SITEURL','http://localhost/wordpress/');