<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'x23c4064_wordpress_c');

/** MySQL database username */
define('DB_USER',       'x23c4064_wordpress_a');

/** MySQL database password */
define('DB_PASSWORD',       '0f!Gb7WuZ5');

/** MySQL hostname */
define('DB_HOST', 'localhost:3306');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',       'dmVh@Ny@pzEAflkx5S!DE*GkTp7ddfajNQa(MF8(Hl^YdC!dwim@O2JToxfdtgio');
define('SECURE_AUTH_KEY',       '0CCuoRLryZ2!XSUG6%yxR#TI66vhEdZyj7CmI@#ydQnq#y7wZtX^mlLN#w2l(Lur');
define('LOGGED_IN_KEY',       'lXBE9i%aINpJw7)2qcS^A7rarn!xDVL05NKS5rLvhnXI2CVWS5Pub3zlq62mvZo0');
define('NONCE_KEY',       ')Pn3j2gpAvJx1pwi#gxPXg3eU0NWxsdQ)1Bdw&jMXrZQK(vMrHO(@6%q@6XXAcVf');
define('AUTH_SALT',       'Bw7uNP%d!af3L23t2i*hzouqy8cs@JCChWltlXtQYCcWd8Ak9FXPREv38GVu6hzS');
define('SECURE_AUTH_SALT',       'm!bFbl@%ylaqu%ppH7SI^lbxsjxXyj@54h^F8z*qDt4Q0SjvNtX7qLxKWx&GGUh6');
define('LOGGED_IN_SALT',       'nznGr!yT40yZ2wt0^itOzkWBcrCnQVn6%f*pn%JBg^WHPXS)16YiNcvmrzRN*mdC');
define('NONCE_SALT',       'ngNTXby2u(U^Ic3xe7JFFXDr)bD9s^OI6rgUePzwSeFFV)bB*fO#E5prZ(ZK@ISo');
/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'aDOtz4PiG8_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'DISALLOW_FILE_EDIT', true );
define( 'CONCATENATE_SCRIPTS', false );
/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

define( 'WP_ALLOW_MULTISITE', true );

define ('FS_METHOD', 'direct');