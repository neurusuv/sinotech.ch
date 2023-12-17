<?php
define('WP_CACHE', false); // WP Compress Cache

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
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'sinotech.ch' );

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
define( 'AUTH_KEY',         'Vnl,xXWxi/J[./O9e{^:`ef92zQXw/$X7EOn!_9hGORKw4)Og@d;n`eMBIm~2nhN' );
define( 'SECURE_AUTH_KEY',  '7dL/eun;D)F~]9!x45q~-08a[Rbjz,Nxa5-, 6(#qguVlHrHX_Yw*{@mzK}6I=E!' );
define( 'LOGGED_IN_KEY',    '?CMh$UY$dhWeO0}X,b1.N<x|&c]%3ripM-h,6keDw6ZpJlLWzF5WhO=)l~3dlb/.' );
define( 'NONCE_KEY',        '^utp#ZgaU2 !~2owrDhGw_w006(+IEbXfke[6+k&.4:SOcRa6Hb-7?e1jbU,XYn/' );
define( 'AUTH_SALT',        '?H9,e%.+C0fn`F8<Jk>lzwnjXdoL2<St$7<;[/+|V9qO~vTNqrsB1wr} =4O!omn' );
define( 'SECURE_AUTH_SALT', 'N-*?:m9M< >TkAHR&;_N-n>kFK[#bmNZC>Ca{7d{;>r8,TT1%V[+6a:w-r?3&P[;' );
define( 'LOGGED_IN_SALT',   'Og0B3Z4*&pwVMu:<lk#^&:;7(W}rB<a}N1OMGKx>:Kwh$%~UhXw$0:wh>QAxX,AA' );
define( 'NONCE_SALT',       ')dG48{e/Yjvq{n.] iM_?j!.pC*)%|9VSTvu:xV04V*S/x4:jD:`Qh{58Eo7.+]-' );

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
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
