<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'rachel_wp');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '3g4d!!');

/** MySQL hostname */
define('DB_HOST', 'localhost');

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
define('AUTH_KEY',         'W-TBy-c#C-G Y4|%e R#_!mEBQ9GT^V#K1)z3-eXGz-;3uZm*L+~O|F,NYv4J~$;');
define('SECURE_AUTH_KEY',  '-6O}yIYWr:Hnvq$ZeTnimRu%Yvzg+Q#V3Y<8{zMtIaVlAO6Df;`U0=i<|@p*WshP');
define('LOGGED_IN_KEY',    ',vDv,eo:EBqk9+@n1f1m?]iPaCv_Eg<byiD${yDH=nJy6]jRa2u@rJ/xF.)g`{8#');
define('NONCE_KEY',        'q@,w#jx6EM}0iZ5aLbRQ(B`W*TzrTbY((E+3lg6{+[2G>chFvXS}]3[mJMP;-8kU');
define('AUTH_SALT',        '-XCLJO>kFD85^>Yt!-Y0e;Z|^ge<wvX?E~Ej@6,{WS2!|RQrS>0P?^DU_BO-@@>r');
define('SECURE_AUTH_SALT', '#r7k+!D`y/XX|dS*P,C4jCi~%I$U4+$oS[$-0PVp5G7p#$IUIQI8:j@mUmSW!44q');
define('LOGGED_IN_SALT',   '(C1~Ac;lv|JXtN3[POu0RzTu$+4$I5*KN,t;rXO}eH*ZLYJ;2}gZM|c&N0I)`7`N');
define('NONCE_SALT',       '+;h9v>d3tVq h3{H7E|;>-l{5+~Od|aO0(U||+D@aC=Kl4G{D*I`_ 6wOQoz3=nx');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress.  A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de.mo to wp-content/languages and set WPLANG to 'de' to enable German
 * language support.
 */
define ('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
