<?php
/**
 * XML-RPC protocol support for WordPress
 *
 * @license GPL v2 <./license.txt>
 * @package WordPress
 */

/**
 * Whether this is a XMLRPC Request
 *
 * @var bool
 */
define('XMLRPC_REQUEST', true);

// Some browser-embedded clients send cookies. We don't want them.
$_COOKIE = array();

// A bug in PHP < 5.2.2 makes $HTTP_RAW_POST_DATA not set by default,
// but we can do it ourself.
if ( !isset( $HTTP_RAW_POST_DATA ) ) {
	$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
}

// fix for mozBlog and other cases where '<?xml' isn't on the very first line
if ( isset($HTTP_RAW_POST_DATA) )
	$HTTP_RAW_POST_DATA = trim($HTTP_RAW_POST_DATA);

/** Include the bootstrap for setting up WordPress environment */
include('./wp-load.php');

if ( isset( $_GET['rsd'] ) ) { // http://archipelago.phrasewise.com/rsd
header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
?>
<?php echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
  <service>
    <engineName>WordPress</engineName>
    <engineLink>http://wordpress.org/</engineLink>
    <homePageLink><?php bloginfo_rss('url') ?></homePageLink>
    <apis>
      <api name="WordPress" blogID="1" preferred="true" apiLink="<?php echo site_url('xmlrpc.php', 'rpc') ?>" />
      <api name="Movable Type" blogID="1" preferred="false" apiLink="<?php echo site_url('xmlrpc.php', 'rpc') ?>" />
      <api name="MetaWeblog" blogID="1" preferred="false" apiLink="<?php echo site_url('xmlrpc.php', 'rpc') ?>" />
      <api name="Blogger" blogID="1" preferred="false" apiLink="<?php echo site_url('xmlrpc.php', 'rpc') ?>" />
      <api name="Atom" blogID="" preferred="false" apiLink="<?php echo apply_filters('atom_service_url', site_url('wp-app.php/service', 'rpc') ) ?>" />
    </apis>
  </service>
</rsd>
<?php
exit;
}

include_once(ABSPATH . 'wp-admin/includes/admin.php');
include_once(ABSPATH . WPINC . '/class-IXR.php');

// Turn off all warnings and errors.
// error_reporting(0);

/**
 * Posts submitted via the xmlrpc interface get that title
 * @name post_default_title
 * @var string
 */
$post_default_title = "";

/**
 * Whether to enable XMLRPC Logging.
 *
 * @name xmlrpc_logging
 * @var int|bool
 */
$xmlrpc_logging = 0;

/**
 * logIO() - Writes logging info to a file.
 *
 * @uses $xmlrpc_logging
 * @package WordPress
 * @subpackage Logging
 *
 * @param string $io Whether input or output
 * @param string $msg Information describing logging reason.
 * @return bool Always return true
 */
function logIO($io,$msg) {
	global $xmlrpc_logging;
	if ($xmlrpc_logging) {
		$fp = fopen("../xmlrpc.log","a+");
		$date = gmdate("Y-m-d H:i:s ");
		$iot = ($io == "I") ? " Input: " : " Output: ";
		fwrite($fp, "\n\n".$date.$iot.$msg);
		fclose($fp);
	}
	return true;
}

if ( isset($HTTP_RAW_POST_DATA) )
	logIO("I", $HTTP_RAW_POST_DATA);

/**
 * WordPress XMLRPC server implementation.
 *
 * Implements compatability for Blogger API, MetaWeblog API, MovableType, and
 * pingback. Additional WordPress API for managing comments, pages, posts,
 * options, etc.
 *
 * Since WordPress 2.6.0, WordPress XMLRPC server can be disabled in the
 * administration panels.
 *
 * @package WordPress
 * @subpackage Publishing
 * @since 1.5.0
 */
class wp_xmlrpc_server extends IXR_Server {

	/**
	 * Register all of the XMLRPC methods that XMLRPC server understands.
	 *
	 * PHP4 constructor and sets up server and method property. Passes XMLRPC
	 * methods through the 'xmlrpc_methods' filter to allow plugins to extend
	 * or replace XMLRPC methods.
	 *
	 * @since 1.5.0
	 *
	 * @return wp_xmlrpc_server
	 */
	function wp_xmlrpc_server() {
		$this->methods = array(
			// WordPress API
			'wp.getUsersBlogs'		=> 'this:wp_getUsersBlogs',
			'wp.getPage'			=> 'this:wp_getPage',
			'wp.getPages'			=> 'this:wp_getPages',
			'wp.newPage'			=> 'this:wp_newPage',
			'wp.deletePage'			=> 'this:wp_deletePage',
			'wp.editPage'			=> 'this:wp_editPage',
			'wp.getPageList'		=> 'this:wp_getPageList',
			'wp.getAuthors'			=> 'this:wp_getAuthors',
			'wp.getCategories'		=> 'this:mw_getCategories',		// Alias
			'wp.getTags'			=> 'this:wp_getTags',
			'wp.newCategory'		=> 'this:wp_newCategory',
			'wp.deleteCategory'		=> 'this:wp_deleteCategory',
			'wp.suggestCategories'	=> 'this:wp_suggestCategories',
			'wp.uploadFile'			=> 'this:mw_newMediaObject',	// Alias
			'wp.getCommentCount'	=> 'this:wp_getCommentCount',
			'wp.getPostStatusList'	=> 'this:wp_getPostStatusList',
			'wp.getPageStatusList'	=> 'this:wp_getPageStatusList',
			'wp.getPageTemplates'	=> 'this:wp_getPageTemplates',
			'wp.getOptions'			=> 'this:wp_getOptions',
			'wp.setOptions'			=> 'this:wp_setOptions',
			'wp.getComment'			=> 'this:wp_getComment',
			'wp.getComments'		=> 'this:wp_getComments',
			'wp.deleteComment'		=> 'this:wp_deleteComment',
			'wp.editComment'		=> 'this:wp_editComment',
			'wp.newComment'			=> 'this:wp_newComment',
			'wp.getCommentStatusList' => 'this:wp_getCommentStatusList',

			// Blogger API
			'blogger.getUsersBlogs' => 'this:blogger_getUsersBlogs',
			'blogger.getUserInfo' => 'this:blogger_getUserInfo',
			'blogger.getPost' => 'this:blogger_getPost',
			'blogger.getRecentPosts' => 'this:blogger_getRecentPosts',
			'blogger.getTemplate' => 'this:blogger_getTemplate',
			'blogger.setTemplate' => 'this:blogger_setTemplate',
			'blogger.newPost' => 'this:blogger_newPost',
			'blogger.editPost' => 'this:blogger_editPost',
			'blogger.deletePost' => 'this:blogger_deletePost',

			// MetaWeblog API (with MT extensions to structs)
			'metaWeblog.newPost' => 'this:mw_newPost',
			'metaWeblog.editPost' => 'this:mw_editPost',
			'metaWeblog.getPost' => 'this:mw_getPost',
			'metaWeblog.getRecentPosts' => 'this:mw_getRecentPosts',
			'metaWeblog.getCategories' => 'this:mw_getCategories',
			'metaWeblog.newMediaObject' => 'this:mw_newMediaObject',

			// MetaWeblog API aliases for Blogger API
			// see http://www.xmlrpc.com/stories/storyReader$2460
			'metaWeblog.deletePost' => 'this:blogger_deletePost',
			'metaWeblog.getTemplate' => 'this:blogger_getTemplate',
			'metaWeblog.setTemplate' => 'this:blogger_setTemplate',
			'metaWeblog.getUsersBlogs' => 'this:blogger_getUsersBlogs',

			// MovableType API
			'mt.getCategoryList' => 'this:mt_getCategoryList',
			'mt.getRecentPostTitles' => 'this:mt_getRecentPostTitles',
			'mt.getPostCategories' => 'this:mt_getPostCategories',
			'mt.setPostCategories' => 'this:mt_setPostCategories',
			'mt.supportedMethods' => 'this:mt_supportedMethods',
			'mt.supportedTextFilters' => 'this:mt_supportedTextFilters',
			'mt.getTrackbackPings' => 'this:mt_getTrackbackPings',
			'mt.publishPost' => 'this:mt_publishPost',

			// PingBack
			'pingback.ping' => 'this:pingback_ping',
			'pingback.extensions.getPingbacks' => 'this:pingback_extensions_getPingbacks',

			'demo.sayHello' => 'this:sayHello',
			'demo.addTwoNumbers' => 'this:addTwoNumbers'
		);

		$this->initialise_blog_option_info( );
		$this->methods = apply_filters('xmlrpc_methods', $this->methods);
	}

	function serve_request() {
		$this->IXR_Server($this->methods);
	}

	/**
	 * Test XMLRPC API by saying, "Hello!" to client.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Method Parameters.
	 * @return string
	 */
	function sayHello($args) {
		return 'Hello!';
	}

	/**
	 * Test XMLRPC API by adding two numbers for client.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Method Parameters.
	 * @return int
	 */
	function addTwoNumbers($args) {
		$number1 = $args[0];
		$number2 = $args[1];
		return $number1 + $number2;
	}

	/**
	 * Check user's credentials.
	 *
	 * @since 1.5.0
	 *
	 * @param string $user_login User's username.
	 * @param string $user_pass User's password.
	 * @return bool Whether authentication passed.
	 * @deprecated use wp_xmlrpc_server::login
	 * @see wp_xmlrpc_server::login
	 */
	function login_pass_ok($user_login, $user_pass) {
		if ( !get_option( 'enable_xmlrpc' ) ) {
			$this->error = new IXR_Error( 405, sprintf( __( 'XML-RPC services are disabled on this site.  An admin user can enable them at %s'),  admin_url('options-writing.php') ) );
			return false;
		}

		if (!user_pass_ok($user_login, $user_pass)) {
			$this->error = new IXR_Error(403, __('Bad login/pass combination.'));
			return false;
		}
		return true;
	}

	/**
	 * Log user in.
	 *
	 * @since 2.8
	 *
	 * @param string $username User's username.
	 * @param string $password User's password.
	 * @return mixed WP_User object if authentication passed, false otherwise
	 */
	function login($username, $password) {
		if ( !get_option( 'enable_xmlrpc' ) ) {
			$this->error = new IXR_Error( 405, sprintf( __( 'XML-RPC services are disabled on this site.  An admin user can enable them at %s'),  admin_url('options-writing.php') ) );
			return false;
		}

		$user = wp_authenticate($username, $password);

		if (is_wp_error($user)) {
			$this->error = new IXR_Error(403, __('Bad login/pass combination.'));
			return false;
		}

		wp_set_current_user( $user->ID );
		return $user;
	}

	/**
	 * Sanitize string or array of strings for database.
	 *
	 * @since 1.5.2
	 *
	 * @param string|array $array Sanitize single string or array of strings.
	 * @return string|array Type matches $array and sanitized for the database.
	 */
	function escape(&$array) {
		global $wpdb;

		if (!is_array($array)) {
			return($wpdb->escape($array));
		} else {
			foreach ( (array) $array as $k => $v ) {
				if ( is_array($v) ) {
					$this->escape($array[$k]);
				} else if ( is_object($v) ) {
					//skip
				} else {
					$array[$k] = $wpdb->escape($v);
				}
			}
		}
	}

	/**
	 * Retrieve custom fields for post.
	 *
	 * @since 2.5.0
	 *
	 * @param int $post_id Post ID.
	 * @return array Custom fields, if exist.
	 */
	function get_custom_fields($post_id) {
		$post_id = (int) $post_id;

		$custom_fields = array();

		foreach ( (array) has_meta($post_id) as $meta ) {
			// Don't expose protected fields.
			if ( strpos($meta['meta_key'], '_wp_') === 0 ) {
				continue;
			}

			$custom_fields[] = array(
				"id"    => $meta['meta_id'],
				"key"   => $meta['meta_key'],
				"value" => $meta['meta_value']
			);
		}

		return $custom_fields;
	}

	/**
	 * Set custom fields for post.
	 *
	 * @since 2.5.0
	 *
	 * @param int $post_id Post ID.
	 * @param array $fields Custom fields.
	 */
	function set_custom_fields($post_id, $fields) {
		$post_id = (int) $post_id;

		foreach ( (array) $fields as $meta ) {
			if ( isset($meta['id']) ) {
				$meta['id'] = (int) $meta['id'];

				if ( isset($meta['key']) ) {
					update_meta($meta['id'], $meta['key'], $meta['value']);
				}
				else {
					delete_meta($meta['id']);
				}
			}
			else {
				$_POST['metakeyinput'] = $meta['key'];
				$_POST['metavalue'] = $meta['value'];
				add_meta($post_id);
			}
		}
	}

	/**
	 * Set up blog options property.
	 *
	 * Passes property through 'xmlrpc_blog_options' filter.
	 *
	 * @since 2.6.0
	 */
	function initialise_blog_option_info( ) {
		global $wp_version;

		$this->blog_options = array(
			// Read only options
			'software_name'		=> array(
				'desc'			=> __( 'Software Name' ),
				'readonly'		=> true,
				'value'			=> 'WordPress'
			),
			'software_version'	=> array(
				'desc'			=> __( 'Software Version' ),
				'readonly'		=> true,
				'value'			=> $wp_version
			),
			'blog_url'			=> array(
				'desc'			=> __( 'Site URL' ),
				'readonly'		=> true,
				'option'		=> 'siteurl'
			),

			// Updatable options
			'time_zone'			=> array(
				'desc'			=> __( 'Time Zone' ),
				'readonly'		=> false,
				'option'		=> 'gmt_offset'
			),
			'blog_title'		=> array(
				'desc'			=> __( 'Site Title' ),
				'readonly'		=> false,
				'option'			=> 'blogname'
			),
			'blog_tagline'		=> array(
				'desc'			=> __( 'Site Tagline' ),
				'readonly'		=> false,
				'option'		=> 'blogdescription'
			),
			'date_format'		=> array(
				'desc'			=> __( 'Date Format' ),
				'readonly'		=> false,
				'option'		=> 'date_format'
			),
			'time_format'		=> array(
				'desc'			=> __( 'Time Format' ),
				'readonly'		=> false,
				'option'		=> 'time_format'
			),
			'users_can_register'	=> array(
				'desc'			=> __( 'Allow new users to sign up' ),
				'readonly'		=> false,
				'option'		=> 'users_can_register'
			)
		);

		$this->blog_options = apply_filters( 'xmlrpc_blog_options', $this->blog_options );
	}

	/**
	 * Retrieve the blogs of the user.
	 *
	 * @since 2.6.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getUsersBlogs( $args ) {
		global $current_site;
		// If this isn't on WPMU then just use blogger_getUsersBlogs
		if ( !is_multisite() ) {
			array_unshift( $args, 1 );
			return $this->blogger_getUsersBlogs( $args );
		}

		$this->escape( $args );

		$username = $args[0];
		$password = $args[1];

		if ( !$user = $this->login($username, $password) )
			return $this->error;


		do_action( 'xmlrpc_call', 'wp.getUsersBlogs' );

		$blogs = (array) get_blogs_of_user( $user->ID );
		$struct = array( );

		foreach ( $blogs as $blog ) {
			// Don't include blogs that aren't hosted at this site
			if ( $blog->site_id != $current_site->id )
				continue;

			$blog_id = $blog->userblog_id;
			switch_to_blog($blog_id);
			$is_admin = current_user_can('manage_options');

			$struct[] = array(
				'isAdmin'		=> $is_admin,
				'url'			=> get_option( 'home' ) . '/',
				'blogid'		=> $blog_id,
				'blogName'		=> get_option( 'blogname' ),
				'xmlrpc'		=> site_url( 'xmlrpc.php' )
			);

			restore_current_blog( );
		}

		return $struct;
	}

	/**
	 * Retrieve page.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getPage($args) {
		$this->escape($args);

		$blog_id	= (int) $args[0];
		$page_id	= (int) $args[1];
		$username	= $args[2];
		$password	= $args[3];

		if ( !$user = $this->login($username, $password) ) {
			return $this->error;
		}

		if ( !current_user_can( 'edit_page', $page_id ) )
			return new IXR_Error( 401, __( 'Sorry, you cannot edit this page.' ) );

		do_action('xmlrpc_call', 'wp.getPage');

		// Lookup page info.
		$page = get_page($page_id);

		// If we found the page then format the data.
		if ( $page->ID && ($page->post_type == "page") ) {
			// Get all of the page content and link.
			$full_page = get_extended($page->post_content);
			$link = post_permalink($page->ID);

			// Get info the page parent if there is one.
			$parent_title = "";
			if ( !empty($page->post_parent) ) {
				$parent = get_page($page->post_parent);
				$parent_title = $parent->post_title;
			}

			// Determine comment and ping settings.
			$allow_comments = comments_open($page->ID) ? 1 : 0;
			$allow_pings = pings_open($page->ID) ? 1 : 0;

			// Format page date.
			$page_date = mysql2date("Ymd\TH:i:s", $page->post_date, false);
			$page_date_gmt = mysql2date("Ymd\TH:i:s", $page->post_date_gmt, false);

			// For drafts use the GMT version of the date
			if ( $page->post_status == 'draft' )
				$page_date_gmt = get_gmt_from_date( mysql2date( 'Y-m-d H:i:s', $page->post_date ), 'Ymd\TH:i:s' );

			// Pull the categories info together.
			$categories = array();
			foreach ( wp_get_post_categories($page->ID) as $cat_id ) {
				$categories[] = get_cat_name($cat_id);
			}

			// Get the author info.
			$author = get_userdata($page->post_author);

			$page_template = get_post_meta( $page->ID, '_wp_page_template', true );
			if ( empty( $page_template ) )
				$page_template = 'default';

			$page_struct = array(
				"dateCreated"			=> new IXR_Date($page_date),
				"userid"				=> $page->post_author,
				"page_id"				=> $page->ID,
				"page_status"			=> $page->post_status,
				"description"			=> $full_page["main"],
				"title"					=> $page->post_title,
				"link"					=> $link,
				"permaLink"				=> $link,
				"categories"			=> $categories,
				"excerpt"				=> $page->post_excerpt,
				"text_more"				=> $full_page["extended"],
				"mt_allow_comments"		=> $allow_comments,
				"mt_allow_pings"		=> $allow_pings,
				"wp_slug"				=> $page->post_name,
				"wp_password"			=> $page->post_password,
				"wp_author"				=> $author->display_name,
				"wp_page_parent_id"		=> $page->post_parent,
				"wp_page_parent_title"	=> $parent_title,
				"wp_page_order"			=> $page->menu_order,
				"wp_author_id"			=> $author->ID,
				"wp_author_display_name"	=> $author->display_name,
				"date_created_gmt"		=> new IXR_Date($page_date_gmt),
				"custom_fields"			=> $this->get_custom_fields($page_id),
				"wp_page_template"		=> $page_template
			);

			return($page_struct);
		}
		// If the page doesn't exist indicate that.
		else {
			return(new IXR_Error(404, __("Sorry, no such page.")));
		}
	}

	/**
	 * Retrieve Pages.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getPages($args) {
		$this->escape($args);

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$num_pages	= isset($args[3]) ? (int) $args[3] : 10;

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_pages' ) )
			return new IXR_Error( 401, __( 'Sorry, you cannot edit pages.' ) );

		do_action('xmlrpc_call', 'wp.getPages');

		$pages = get_posts( array('post_type' => 'page', 'post_status' => 'any', 'numberposts' => $num_pages) );
		$num_pages = count($pages);

		// If we have pages, put together their info.
		if ( $num_pages >= 1 ) {
			$pages_struct = array();

			for ( $i = 0; $i < $num_pages; $i++ ) {
				$page = wp_xmlrpc_server::wp_getPage(array(
					$blog_id, $pages[$i]->ID, $username, $password
				));
				$pages_struct[] = $page;
			}

			return($pages_struct);
		}
		// If no pages were found return an error.
		else {
			return(array());
		}
	}

	/**
	 * Create new page.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args Method parameters.
	 * @return unknown
	 */
	function wp_newPage($args) {
		// Items not escaped here will be escaped in newPost.
		$username	= $this->escape($args[1]);
		$password	= $this->escape($args[2]);
		$page		= $args[3];
		$publish	= $args[4];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'wp.newPage');

		// Make sure the user is allowed to add new pages.
		if ( !current_user_can("publish_pages") )
			return(new IXR_Error(401, __("Sorry, you cannot add new pages.")));

		// Mark this as content for a page.
		$args[3]["post_type"] = "page";

		// Let mw_newPost do all of the heavy lifting.
		return($this->mw_newPost($args));
	}

	/**
	 * Delete page.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args Method parameters.
	 * @return bool True, if success.
	 */
	function wp_deletePage($args) {
		$this->escape($args);

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$page_id	= (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'wp.deletePage');

		// Get the current page based on the page_id and
		// make sure it is a page and not a post.
		$actual_page = wp_get_single_post($page_id, ARRAY_A);
		if ( !$actual_page || ($actual_page["post_type"] != "page") )
			return(new IXR_Error(404, __("Sorry, no such page.")));

		// Make sure the user can delete pages.
		if ( !current_user_can("delete_page", $page_id) )
			return(new IXR_Error(401, __("Sorry, you do not have the right to delete this page.")));

		// Attempt to delete the page.
		$result = wp_delete_post($page_id);
		if ( !$result )
			return(new IXR_Error(500, __("Failed to delete the page.")));

		return(true);
	}

	/**
	 * Edit page.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args Method parameters.
	 * @return unknown
	 */
	function wp_editPage($args) {
		// Items not escaped here will be escaped in editPost.
		$blog_id	= (int) $args[0];
		$page_id	= (int) $this->escape($args[1]);
		$username	= $this->escape($args[2]);
		$password	= $this->escape($args[3]);
		$content	= $args[4];
		$publish	= $args[5];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'wp.editPage');

		// Get the page data and make sure it is a page.
		$actual_page = wp_get_single_post($page_id, ARRAY_A);
		if ( !$actual_page || ($actual_page["post_type"] != "page") )
			return(new IXR_Error(404, __("Sorry, no such page.")));

		// Make sure the user is allowed to edit pages.
		if ( !current_user_can("edit_page", $page_id) )
			return(new IXR_Error(401, __("Sorry, you do not have the right to edit this page.")));

		// Mark this as content for a page.
		$content["post_type"] = "page";

		// Arrange args in the way mw_editPost understands.
		$args = array(
			$page_id,
			$username,
			$password,
			$content,
			$publish
		);

		// Let mw_editPost do all of the heavy lifting.
		return($this->mw_editPost($args));
	}

	/**
	 * Retrieve page list.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args Method parameters.
	 * @return unknown
	 */
	function wp_getPageList($args) {
		global $wpdb;

		$this->escape($args);

		$blog_id				= (int) $args[0];
		$username				= $args[1];
		$password				= $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_pages' ) )
			return new IXR_Error( 401, __( 'Sorry, you cannot edit pages.' ) );

		do_action('xmlrpc_call', 'wp.getPageList');

		// Get list of pages ids and titles
		$page_list = $wpdb->get_results("
			SELECT ID page_id,
				post_title page_title,
				post_parent page_parent_id,
				post_date_gmt,
				post_date,
				post_status
			FROM {$wpdb->posts}
			WHERE post_type = 'page'
			ORDER BY ID
		");

		// The date needs to be formated properly.
		$num_pages = count($page_list);
		for ( $i = 0; $i < $num_pages; $i++ ) {
			$post_date = mysql2date("Ymd\TH:i:s", $page_list[$i]->post_date, false);
			$post_date_gmt = mysql2date("Ymd\TH:i:s", $page_list[$i]->post_date_gmt, false);

			$page_list[$i]->dateCreated = new IXR_Date($post_date);
			$page_list[$i]->date_created_gmt = new IXR_Date($post_date_gmt);

			// For drafts use the GMT version of the date
			if ( $page_list[$i]->post_status == 'draft' ) {
				$page_list[$i]->date_created_gmt = get_gmt_from_date( mysql2date( 'Y-m-d H:i:s', $page_list[$i]->post_date ), 'Ymd\TH:i:s' );
				$page_list[$i]->date_created_gmt = new IXR_Date( $page_list[$i]->date_created_gmt );
			}

			unset($page_list[$i]->post_date_gmt);
			unset($page_list[$i]->post_date);
			unset($page_list[$i]->post_status);
		}

		return($page_list);
	}

	/**
	 * Retrieve authors list.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getAuthors($args) {

		$this->escape($args);

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can("edit_posts") )
			return(new IXR_Error(401, __("Sorry, you cannot edit posts on this site.")));

		do_action('xmlrpc_call', 'wp.getAuthors');

		$authors = array();
		foreach ( (array) get_users_of_blog() as $row ) {
			$authors[] = array(
				"user_id"       => $row->user_id,
				"user_login"    => $row->user_login,
				"display_name"  => $row->display_name
			);
		}

		return($authors);
	}

	/**
	 * Get list of all tags
	 *
	 * @since 2.7
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getTags( $args ) {
		$this->escape( $args );

		$blog_id		= (int) $args[0];
		$username		= $args[1];
		$password		= $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_posts' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to edit posts on this site in order to view tags.' ) );

		do_action( 'xmlrpc_call', 'wp.getKeywords' );

		$tags = array( );

		if ( $all_tags = get_tags() ) {
			foreach( (array) $all_tags as $tag ) {
				$struct['tag_id']			= $tag->term_id;
				$struct['name']				= $tag->name;
				$struct['count']			= $tag->count;
				$struct['slug']				= $tag->slug;
				$struct['html_url']			= esc_html( get_tag_link( $tag->term_id ) );
				$struct['rss_url']			= esc_html( get_tag_feed_link( $tag->term_id ) );

				$tags[] = $struct;
			}
		}

		return $tags;
	}

	/**
	 * Create new category.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args Method parameters.
	 * @return int Category ID.
	 */
	function wp_newCategory($args) {
		$this->escape($args);

		$blog_id				= (int) $args[0];
		$username				= $args[1];
		$password				= $args[2];
		$category				= $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'wp.newCategory');

		// Make sure the user is allowed to add a category.
		if ( !current_user_can("manage_categories") )
			return(new IXR_Error(401, __("Sorry, you do not have the right to add a category.")));

		// If no slug was provided make it empty so that
		// WordPress will generate one.
		if ( empty($category["slug"]) )
			$category["slug"] = "";

		// If no parent_id was provided make it empty
		// so that it will be a top level page (no parent).
		if ( !isset($category["parent_id"]) )
			$category["parent_id"] = "";

		// If no description was provided make it empty.
		if ( empty($category["description"]) )
			$category["description"] = "";

		$new_category = array(
			"cat_name"				=> $category["name"],
			"category_nicename"		=> $category["slug"],
			"category_parent"		=> $category["parent_id"],
			"category_description"	=> $category["description"]
		);

		$cat_id = wp_insert_category($new_category);
		if ( !$cat_id )
			return(new IXR_Error(500, __("Sorry, the new category failed.")));

		return($cat_id);
	}

	/**
	 * Remove category.
	 *
	 * @since 2.5.0
	 *
	 * @param array $args Method parameters.
	 * @return mixed See {@link wp_delete_category()} for return info.
	 */
	function wp_deleteCategory($args) {
		$this->escape($args);

		$blog_id		= (int) $args[0];
		$username		= $args[1];
		$password		= $args[2];
		$category_id	= (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'wp.deleteCategory');

		if ( !current_user_can("manage_categories") )
			return new IXR_Error( 401, __( "Sorry, you do not have the right to delete a category." ) );

		return wp_delete_category( $category_id );
	}

	/**
	 * Retrieve category list.
	 *
	 * @since 2.2.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_suggestCategories($args) {
		$this->escape($args);

		$blog_id				= (int) $args[0];
		$username				= $args[1];
		$password				= $args[2];
		$category				= $args[3];
		$max_results			= (int) $args[4];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_posts' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to edit posts to this site in order to view categories.' ) );

		do_action('xmlrpc_call', 'wp.suggestCategories');

		$category_suggestions = array();
		$args = array('get' => 'all', 'number' => $max_results, 'name__like' => $category);
		foreach ( (array) get_categories($args) as $cat ) {
			$category_suggestions[] = array(
				"category_id"	=> $cat->cat_ID,
				"category_name"	=> $cat->cat_name
			);
		}

		return($category_suggestions);
	}

	/**
	 * Retrieve comment.
	 *
	 * @since 2.7.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getComment($args) {
		$this->escape($args);

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$comment_id	= (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'moderate_comments' ) )
			return new IXR_Error( 403, __( 'You are not allowed to moderate comments on this site.' ) );

		do_action('xmlrpc_call', 'wp.getComment');

		if ( ! $comment = get_comment($comment_id) )
			return new IXR_Error( 404, __( 'Invalid comment ID.' ) );

		// Format page date.
		$comment_date = mysql2date("Ymd\TH:i:s", $comment->comment_date, false);
		$comment_date_gmt = mysql2date("Ymd\TH:i:s", $comment->comment_date_gmt, false);

		if ( '0' == $comment->comment_approved )
			$comment_status = 'hold';
		else if ( 'spam' == $comment->comment_approved )
			$comment_status = 'spam';
		else if ( '1' == $comment->comment_approved )
			$comment_status = 'approve';
		else
			$comment_status = $comment->comment_approved;

		$link = get_comment_link($comment);

		$comment_struct = array(
			"date_created_gmt"		=> new IXR_Date($comment_date_gmt),
			"user_id"				=> $comment->user_id,
			"comment_id"			=> $comment->comment_ID,
			"parent"				=> $comment->comment_parent,
			"status"				=> $comment_status,
			"content"				=> $comment->comment_content,
			"link"					=> $link,
			"post_id"				=> $comment->comment_post_ID,
			"post_title"			=> get_the_title($comment->comment_post_ID),
			"author"				=> $comment->comment_author,
			"author_url"			=> $comment->comment_author_url,
			"author_email"			=> $comment->comment_author_email,
			"author_ip"				=> $comment->comment_author_IP,
			"type"					=> $comment->comment_type,
		);

		return $comment_struct;
	}

	/**
	 * Retrieve comments.
	 *
	 * @since 2.7.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getComments($args) {
		$raw_args = $args;
		$this->escape($args);

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$struct		= $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'moderate_comments' ) )
			return new IXR_Error( 401, __( 'Sorry, you cannot edit comments.' ) );

		do_action('xmlrpc_call', 'wp.getComments');

		if ( isset($struct['status']) )
			$status = $struct['status'];
		else
			$status = '';

		$post_id = '';
		if ( isset($struct['post_id']) )
			$post_id = absint($struct['post_id']);

		$offset = 0;
		if ( isset($struct['offset']) )
			$offset = absint($struct['offset']);

		$number = 10;
		if ( isset($struct['number']) )
			$number = absint($struct['number']);

		$comments = get_comments( array('status' => $status, 'post_id' => $post_id, 'offset' => $offset, 'number' => $number ) );
		$num_comments = count($comments);

		if ( ! $num_comments )
			return array();

		$comments_struct = array();

		for ( $i = 0; $i < $num_comments; $i++ ) {
			$comment = wp_xmlrpc_server::wp_getComment(array(
				$raw_args[0], $raw_args[1], $raw_args[2], $comments[$i]->comment_ID,
			));
			$comments_struct[] = $comment;
		}

		return $comments_struct;
	}

	/**
	 * Remove comment.
	 *
	 * @since 2.7.0
	 *
	 * @param array $args Method parameters.
	 * @return mixed {@link wp_delete_comment()}
	 */
	function wp_deleteComment($args) {
		$this->escape($args);

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$comment_ID	= (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'moderate_comments' ) )
			return new IXR_Error( 403, __( 'You are not allowed to moderate comments on this site.' ) );

		do_action('xmlrpc_call', 'wp.deleteComment');

		if ( ! get_comment($comment_ID) )
			return new IXR_Error( 404, __( 'Invalid comment ID.' ) );

		return wp_delete_comment($comment_ID);
	}

	/**
	 * Edit comment.
	 *
	 * @since 2.7.0
	 *
	 * @param array $args Method parameters.
	 * @return bool True, on success.
	 */
	function wp_editComment($args) {
		$this->escape($args);

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$comment_ID	= (int) $args[3];
		$content_struct = $args[4];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'moderate_comments' ) )
			return new IXR_Error( 403, __( 'You are not allowed to moderate comments on this site.' ) );

		do_action('xmlrpc_call', 'wp.editComment');

		if ( ! get_comment($comment_ID) )
			return new IXR_Error( 404, __( 'Invalid comment ID.' ) );

		if ( isset($content_struct['status']) ) {
			$statuses = get_comment_statuses();
			$statuses = array_keys($statuses);

			if ( ! in_array($content_struct['status'], $statuses) )
				return new IXR_Error( 401, __( 'Invalid comment status.' ) );
			$comment_approved = $content_struct['status'];
		}

		// Do some timestamp voodoo
		if ( !empty( $content_struct['date_created_gmt'] ) ) {
			$dateCreated = str_replace( 'Z', '', $content_struct['date_created_gmt']->getIso() ) . 'Z'; // We know this is supposed to be GMT, so we're going to slap that Z on there by force
			$comment_date = get_date_from_gmt(iso8601_to_datetime($dateCreated));
			$comment_date_gmt = iso8601_to_datetime($dateCreated, GMT);
		}

		if ( isset($content_struct['content']) )
			$comment_content = $content_struct['content'];

		if ( isset($content_struct['author']) )
			$comment_author = $content_struct['author'];

		if ( isset($content_struct['author_url']) )
			$comment_author_url = $content_struct['author_url'];

		if ( isset($content_struct['author_email']) )
			$comment_author_email = $content_struct['author_email'];

		// We've got all the data -- post it:
		$comment = compact('comment_ID', 'comment_content', 'comment_approved', 'comment_date', 'comment_date_gmt', 'comment_author', 'comment_author_email', 'comment_author_url');

		$result = wp_update_comment($comment);
		if ( is_wp_error( $result ) )
			return new IXR_Error(500, $result->get_error_message());

		if ( !$result )
			return new IXR_Error(500, __('Sorry, the comment could not be edited. Something wrong happened.'));

		return true;
	}

	/**
	 * Create new comment.
	 *
	 * @since 2.7.0
	 *
	 * @param array $args Method parameters.
	 * @return mixed {@link wp_new_comment()}
	 */
	function wp_newComment($args) {
		global $wpdb;

		$this->escape($args);

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$post		= $args[3];
		$content_struct = $args[4];

		$allow_anon = apply_filters('xmlrpc_allow_anonymous_comments', false);

		$user = $this->login($username, $password);

		if ( !$user ) {
			$logged_in = false;
			if ( $allow_anon && get_option('comment_registration') )
				return new IXR_Error( 403, __( 'You must be registered to comment' ) );
			else if ( !$allow_anon )
				return $this->error;
		} else {
			$logged_in = true;
		}

		if ( is_numeric($post) )
			$post_id = absint($post);
		else
			$post_id = url_to_postid($post);

		if ( ! $post_id )
			return new IXR_Error( 404, __( 'Invalid post ID.' ) );

		if ( ! get_post($post_id) )
			return new IXR_Error( 404, __( 'Invalid post ID.' ) );

		$comment['comment_post_ID'] = $post_id;

		if ( $logged_in ) {
			$comment['comment_author'] = $wpdb->escape( $user->display_name );
			$comment['comment_author_email'] = $wpdb->escape( $user->user_email );
			$comment['comment_author_url'] = $wpdb->escape( $user->user_url );
			$comment['user_ID'] = $user->ID;
		} else {
			$comment['comment_author'] = '';
			if ( isset($content_struct['author']) )
				$comment['comment_author'] = $content_struct['author'];

			$comment['comment_author_email'] = '';
			if ( isset($content_struct['author_email']) )
				$comment['comment_author_email'] = $content_struct['author_email'];

			$comment['comment_author_url'] = '';
			if ( isset($content_struct['author_url']) )
				$comment['comment_author_url'] = $content_struct['author_url'];

			$comment['user_ID'] = 0;

			if ( get_option('require_name_email') ) {
				if ( 6 > strlen($comment['comment_author_email']) || '' == $comment['comment_author'] )
					return new IXR_Error( 403, __( 'Comment author name and email are required' ) );
				elseif ( !is_email($comment['comment_author_email']) )
					return new IXR_Error( 403, __( 'A valid email address is required' ) );
			}
		}

		$comment['comment_parent'] = isset($content_struct['comment_parent']) ? absint($content_struct['comment_parent']) : 0;

		$comment['comment_content'] = $content_struct['content'];

		do_action('xmlrpc_call', 'wp.newComment');

		return wp_new_comment($comment);
	}

	/**
	 * Retrieve all of the comment status.
	 *
	 * @since 2.7.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getCommentStatusList($args) {
		$this->escape( $args );

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'moderate_comments' ) )
			return new IXR_Error( 403, __( 'You are not allowed access to details about this site.' ) );

		do_action('xmlrpc_call', 'wp.getCommentStatusList');

		return get_comment_statuses( );
	}

	/**
	 * Retrieve comment count.
	 *
	 * @since 2.5.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getCommentCount( $args ) {
		$this->escape($args);

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$post_id	= (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_posts' ) )
			return new IXR_Error( 403, __( 'You are not allowed access to details about comments.' ) );

		do_action('xmlrpc_call', 'wp.getCommentCount');

		$count = wp_count_comments( $post_id );
		return array(
			"approved" => $count->approved,
			"awaiting_moderation" => $count->moderated,
			"spam" => $count->spam,
			"total_comments" => $count->total_comments
		);
	}

	/**
	 * Retrieve post statuses.
	 *
	 * @since 2.5.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getPostStatusList( $args ) {
		$this->escape( $args );

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_posts' ) )
			return new IXR_Error( 403, __( 'You are not allowed access to details about this site.' ) );

		do_action('xmlrpc_call', 'wp.getPostStatusList');

		return get_post_statuses( );
	}

	/**
	 * Retrieve page statuses.
	 *
	 * @since 2.5.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getPageStatusList( $args ) {
		$this->escape( $args );

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_posts' ) )
			return new IXR_Error( 403, __( 'You are not allowed access to details about this site.' ) );

		do_action('xmlrpc_call', 'wp.getPageStatusList');

		return get_page_statuses( );
	}

	/**
	 * Retrieve page templates.
	 *
	 * @since 2.6.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getPageTemplates( $args ) {
		$this->escape( $args );

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_pages' ) )
			return new IXR_Error( 403, __( 'You are not allowed access to details about this site.' ) );

		$templates = get_page_templates( );
		$templates['Default'] = 'default';

		return $templates;
	}

	/**
	 * Retrieve blog options.
	 *
	 * @since 2.6.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function wp_getOptions( $args ) {
		$this->escape( $args );

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$options	= (array) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		// If no specific options where asked for, return all of them
		if ( count( $options ) == 0 )
			$options = array_keys($this->blog_options);

		return $this->_getOptions($options);
	}

	/**
	 * Retrieve blog options value from list.
	 *
	 * @since 2.6.0
	 *
	 * @param array $options Options to retrieve.
	 * @return array
	 */
	function _getOptions($options) {
		$data = array( );
		foreach ( $options as $option ) {
			if ( array_key_exists( $option, $this->blog_options ) ) {
				$data[$option] = $this->blog_options[$option];
				//Is the value static or dynamic?
				if ( isset( $data[$option]['option'] ) ) {
					$data[$option]['value'] = get_option( $data[$option]['option'] );
					unset($data[$option]['option']);
				}
			}
		}

		return $data;
	}

	/**
	 * Update blog options.
	 *
	 * @since 2.6.0
	 *
	 * @param array $args Method parameters.
	 * @return unknown
	 */
	function wp_setOptions( $args ) {
		$this->escape( $args );

		$blog_id	= (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$options	= (array) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'manage_options' ) )
			return new IXR_Error( 403, __( 'You are not allowed to update options.' ) );

		foreach ( $options as $o_name => $o_value ) {
			$option_names[] = $o_name;
			if ( !array_key_exists( $o_name, $this->blog_options ) )
				continue;

			if ( $this->blog_options[$o_name]['readonly'] == true )
				continue;

			update_option( $this->blog_options[$o_name]['option'], $o_value );
		}

		//Now return the updated values
		return $this->_getOptions($option_names);
	}

	/* Blogger API functions.
	 * specs on http://plant.blogger.com/api and http://groups.yahoo.com/group/bloggerDev/
	 */

	/**
	 * Retrieve blogs that user owns.
	 *
	 * Will make more sense once we support multiple blogs.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function blogger_getUsersBlogs($args) {
		if ( is_multisite() )
			return $this->_multisite_getUsersBlogs($args);

		$this->escape($args);

		$username = $args[1];
		$password  = $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'blogger.getUsersBlogs');

		$is_admin = current_user_can('manage_options');

		$struct = array(
			'isAdmin'  => $is_admin,
			'url'      => get_option('home') . '/',
			'blogid'   => '1',
			'blogName' => get_option('blogname'),
			'xmlrpc'   => site_url( 'xmlrpc.php' )
		);

		return array($struct);
	}

	/**
	 * Private function for retrieving a users blogs for multisite setups
	 *
	 * @access protected
	 */
	function _multisite_getUsersBlogs($args) {
		global $current_blog;
		$domain = $current_blog->domain;
		$path = $current_blog->path . 'xmlrpc.php';
		$protocol = is_ssl() ? 'https' : 'http';

		$rpc = new IXR_Client("$protocol://{$domain}{$path}");
		$rpc->query('wp.getUsersBlogs', $args[1], $args[2]);
		$blogs = $rpc->getResponse();

		if ( isset($blogs['faultCode']) )
			return new IXR_Error($blogs['faultCode'], $blogs['faultString']);

		if ( $_SERVER['HTTP_HOST'] == $domain && $_SERVER['REQUEST_URI'] == $path ) {
			return $blogs;
		} else {
			foreach ( (array) $blogs as $blog ) {
				if ( strpos($blog['url'], $_SERVER['HTTP_HOST']) )
					return array($blog);
			}
			return array();
		}
	}

	/**
	 * Retrieve user's data.
	 *
	 * Gives your client some info about you, so you don't have to.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function blogger_getUserInfo($args) {

		$this->escape($args);

		$username = $args[1];
		$password  = $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_posts' ) )
			return new IXR_Error( 401, __( 'Sorry, you do not have access to user data on this site.' ) );

		do_action('xmlrpc_call', 'blogger.getUserInfo');

		$struct = array(
			'nickname'  => $user->nickname,
			'userid'    => $user->ID,
			'url'       => $user->user_url,
			'lastname'  => $user->last_name,
			'firstname' => $user->first_name
		);

		return $struct;
	}

	/**
	 * Retrieve post.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function blogger_getPost($args) {

		$this->escape($args);

		$post_ID    = (int) $args[1];
		$username = $args[2];
		$password  = $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !current_user_can( 'edit_post', $post_ID ) )
			return new IXR_Error( 401, __( 'Sorry, you cannot edit this post.' ) );

		do_action('xmlrpc_call', 'blogger.getPost');

		$post_data = wp_get_single_post($post_ID, ARRAY_A);

		$categories = implode(',', wp_get_post_categories($post_ID));

		$content  = '<title>'.stripslashes($post_data['post_title']).'</title>';
		$content .= '<category>'.$categories.'</category>';
		$content .= stripslashes($post_data['post_content']);

		$struct = array(
			'userid'    => $post_data['post_author'],
			'dateCreated' => new IXR_Date(mysql2date('Ymd\TH:i:s', $post_data['post_date'], false)),
			'content'     => $content,
			'postid'  => $post_data['ID']
		);

		return $struct;
	}

	/**
	 * Retrieve list of recent posts.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Method parameters.
	 * @return array
	 */
	function blogger_getRecentPosts($args) {

		$this->escape($args);

		$blog_ID    = (int) $args[1]; /* though we don't use it yet */
		$username = $args[2];
		$password  = $args[3];
		$num_posts  = $args[4];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'blogger.getRecentPosts');

		$posts_list = wp_get_recent_posts($num_posts);

		if ( !$posts_list ) {
			$this->error = new IXR_Error(500, __('Either there are no posts, or something went wrong.'));
			return $this->error;
		}

		foreach ($posts_list as $entry) {
			if ( !current_user_can( 'edit_post', $entry['ID'] ) )
				continue;

			$post_date = mysql2date('Ymd\TH:i:s', $entry['post_date'], false);
			$categories = implode(',', wp_get_post_categories($entry['ID']));

			$content  = '<title>'.stripslashes($entry['post_title']).'</title>';
			$content .= '<category>'.$categories.'</category>';
			$content .= stripslashes($entry['post_content']);

			$struct[] = array(
				'userid' => $entry['post_author'],
				'dateCreated' => new IXR_Date($post_date),
				'content' => $content,
				'postid' => $entry['ID'],
			);

		}

		$recent_posts = array();
		for ( $j=0; $j<count($struct); $j++ ) {
			array_push($recent_posts, $struct[$j]);
		}

		return $recent_posts;
	}

	/**
	 * Retrieve blog_filename content.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Method parameters.
	 * @return string
	 */
	function blogger_getTemplate($args) {

		$this->escape($args);

		$blog_ID    = (int) $args[1];
		$username = $args[2];
		$password  = $args[3];
		$template   = $args[4]; /* could be 'main' or 'archiveIndex', but we don't use it */

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		do_action('xmlrpc_call', 'blogger.getTemplate');

		if ( !current_user_can('edit_themes') )
			return new IXR_Error(401, __('Sorry, this user can not edit the template.'));

		/* warning: here we make the assumption that the blog's URL is on the same server */
		$filename = get_option('home') . '/';
		$filename = preg_replace('#https?://.+?/#', $_SERVER['DOCUMENT_ROOT'].'/', $filename);

		$f = fopen($filename, 'r');
		$content = fread($f, filesize($filename));
		fclose($f);

		/* so it is actually editable with a windows/mac client */
		// FIXME: (or delete me) do we really want to cater to bad clients at the expense of good ones by BEEPing up their line breaks? commented.     $content = str_replace("\n", "\r