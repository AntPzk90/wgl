<?php
/**
 * @package Test-Gallery-List
 */
/*
Plugin Name: Test-Gallery-List
Description: A Test gallery plugin for wordpress.
Version: 1.0.0
*/
/** Install Folder */
define('WGL_FOLDER', '/' . dirname( plugin_basename(__FILE__)));
/** Path for front-end links */
define('WGL_URL', WP_PLUGIN_URL . WGL_FOLDER);
// Post type
define('TEST_WGL_POSTYPE', 'wgl');
// =========================
// ** checking for posts wgl
// =========================
function is_post_type() {
  global $wpdb;
	$post_type = $wpdb->get_results( "SELECT post_type FROM $wpdb->posts WHERE post_type = 'wgl' LIMIT 1");
  if(!empty($post_type)) {
    return true;
  } else {
    return false;
  }
}
// ==================================================
// ** Create 5 ramdom posts on first activated plugin
// ==================================================
if(!is_post_type( 'wgl' )) {
  register_activation_hook( __FILE__, 'wgl_get_5_posts');
}

add_action( 'init', 'wgl_create_post_type' );
add_action( 'admin_head', 'wgl_posttype_icon' );
add_action( 'save_post', 'wgl_save_postdata', 1, 2 );

// ===================
// ** Setup the style and script
// ===================
add_action( 'init', 'wp_add_gallery_style' );
add_action('init', 'wp_add_gallery_script');

	
function wp_add_gallery_style(){
	wp_register_style('slick-wgl.css', WGL_URL . '/style/slick.css');
	wp_enqueue_style('slick-wgl.css');
	wp_register_style('gallery-wgl.css', WGL_URL . '/style/gallery.css');
	wp_enqueue_style('gallery-wgl.css');
	wp_register_style('swiper-wgl.css', WGL_URL . '/style/swiper-bundle.min.css');
	wp_enqueue_style('swiper-wgl.css');
}
	
function wp_add_gallery_script(){
	wp_register_script( 'jquery-wgl', WGL_URL . '/js/jquery-3.5.1.min.js');
    wp_enqueue_script( 'jquery-wgl' );
    wp_register_script( 'slick-wgl', WGL_URL . '/js/slick.min.js');
	wp_enqueue_script( 'slick-wgl' );
	wp_register_script( 'script-wgl', WGL_URL . '/js/script.js');
    wp_enqueue_script( 'script-wgl' );
}
// =======================
// ** Create the post type
// =======================
function wgl_create_post_type() {
	// Define the labels
	$labels = array(
		'name' => _x('WGL', 'post type general name'),
		'add_new' => _x('Add New View', 'new view')
	);
		
	// Register the post type
	register_post_type(TEST_WGL_POSTYPE, array(
		'labels' => $labels,
		'public' => true,
		'show_ui' => true,
		'capability_type' => 'post',
		'query_var' => true,
		'menu_icon' => WGL_URL .'/img/gallery_ico.png', 
		'register_meta_box_cb' => 'wgl_add_url_box',
		'supports' => array(
				'title'
			)
	));
}
// ===========================
// ** Create the icon in admin
// ===========================
function wgl_posttype_icon() {
	global $post_type;
	$qry_postype = ( isset($_GET['post_type']) ) ? $_GET['post_type'] : '' ; 
		
	if (($qry_postype == TEST_WGL_POSTYPE) || ($post_type == TEST_WGL_POSTYPE)) {
	    $icon_url = WGL_URL . '/images/gallery_ico.png';
    ?>
	    <style type="text/css" media="all">
	    /*<![CDATA[*/
	    .icon32 { background: url(<?php echo $icon_url; ?>) no-repeat 1px !important;}
	    /*]]>*/
		</style>
	<?php
	}
}	
// ===================================
// ** Create box arrays for image urls
// ===================================
$wgl_box_images = array (
	'Url for large image' => array (
		array( 'wgl_large_img_url', 'Location of the large image:', 'text')
	)
);
// ===================================
// ** Add boxes for image's urls
// ===================================
function wgl_add_url_box() {
	global $wgl_box_images, $post;
	if ( function_exists( 'add_meta_box' ) ) {
		$val = explode(";", get_post_meta($post->ID, 'wgl_img_url', true));
		foreach ( array_keys( $wgl_box_images ) as $key=>$wgl_box_image ) {
			add_meta_box( $wgl_box_image, __( $wgl_box_image), 'wgl_post_url_box', TEST_WGL_POSTYPE, 'normal', 'high', $val[$key] );
		}
	}
}
	
function wgl_post_url_box ( $obj, $box) {
	global $wgl_box_images, $post;
	// Generate box contents
	foreach ( $wgl_box_images[$box['id']] as $wgl_box ) {
		echo '<br /><label for="'.$wgl_box["0"].'">'.$wgl_box["1"].'</label><br />'
		. '<input style="width: 95%;" type="text" name="'.$wgl_box["0"].'" value="'.$box['args'].'"/>';
	}
}
// ===============================
// ** Save data when post is saved
// ===============================
function wgl_save_postdata($post_id, $post) {
	global $wgl_box_images;
		
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post->ID ))
			return $post->ID;
	} else {
		if ( ! current_user_can( 'edit_post', $post->ID ))
			return $post->ID;
	}
		
	foreach ( $wgl_box_images as $wgl_box_image ) {
		foreach ( $wgl_box_image as $wgl_fields ) {
			$wgl_data[$wgl_fields[0]] =  $_POST[$wgl_fields[0]];
		}
	}
		
	if ( 'revision' == $post->post_type  ) {
		// don't store custom data twice
		return;
	}
			
	if ( get_post_meta($post->ID, $key, FALSE) ) {
		// Field has a value.
		update_post_meta($post->ID, 'wgl_img_url', $wgl_data['wgl_large_img_url']);
	} else {
		// Field does not have a value.
		add_post_meta($post->ID, 'wgl_img_url', $wgl_data['wgl_large_img_url']);
	}
	
	if (!$wgl_data['wgl_large_img_url']) {
		delete_post_meta($post->ID, 'wgl_img_url');
	}			
}

//========================================================
// ** add 5 random posts from nasa_api on activated plugin
//=========================================================

function wgl_get_5_posts () {
    $response = file_get_contents('https://api.nasa.gov/planetary/apod?api_key=BVOKzxYrJdn1eILDN3GhBBLLLWmhjGm9hftyUr73&count=5');
    $response = json_decode($response);

    $nasa_gallery = $response;

    foreach ( (object)  $nasa_gallery as $item ) {
    $date = $item->date;
    $src = $item->hdurl;
    $post_data = array(
      'post_title' => wp_strip_all_tags($date),
      'post_type'     => 'wgl',
      'post_status'   =>  'publish'
    );
    $post_id = wp_insert_post( $post_data );
    update_post_meta($post_id, 'wgl_img_url', $src);
	}
}

//==================================
// ** add last post from nasa_api
//==================================

if ( ! wp_next_scheduled( 'wgl_cron') ) {
  $time_now = time();
  wp_schedule_event( $time_now, 'daily', 'wgl_cron');
}
 
add_action( 'wgl_cron', 'wgl_get_last_day_photo');

function wgl_get_last_day_photo () {
  $date = date ( 'Y-m-d' );
  $response = file_get_contents('https://api.nasa.gov/planetary/apod?api_key=BVOKzxYrJdn1eILDN3GhBBLLLWmhjGm9hftyUr73&date='. $date);
  $response = json_decode($response);

  $last_photo = $response;

  $date = $last_photo->date;
  $src = $last_photo->hdurl;
  $post_data = array(
    'post_title' => wp_strip_all_tags($date),
    'post_type'     => 'wgl',
    'post_status'   =>  'publish'
  );
  $post_id = wp_insert_post( $post_data );
  update_post_meta($post_id, 'wgl_img_url', $src);

}
	
//==================================
// ** Add Shortcode [wpgallerylist]
//==================================
	
add_shortcode('wpgallerylist', 'wpgallerylist_shortcode');

function wpgallerylist_shortcode() { 
	global $wpdb;
		
	$rows = $wpdb->get_results( "SELECT w.id, w.post_date, w.post_title, w.post_content, m.meta_key, m.meta_value FROM $wpdb->posts w LEFT JOIN $wpdb->postmeta m on (w.id = m.post_id) WHERE w.post_type = 'wgl' and w.post_status='publish' and m.meta_key in ('wgl_img_url') ORDER BY w.post_date DESC");
		
	$wgl_display = '';
	$values = json_encode($rows);
	$wgl_display .= '<script id="data_wgl_in_json">'.$values.'</script>';
		
	$wgl_display .= '<div class="wgl__list" id="wpgallery">';
		
	foreach ( (array) $rows as $row ) {
		$wgl_display .= '<div  class="wgl__list-item">';
	
		$wp_images = explode( ";", $row->meta_value);
		
		$wgl_display .= '<div class="wgl__img-container">';
		$wgl_display .= '<img src="'.$wp_images[0].'" height="300" width="500" alt="'.$row->post_title.' date img" class="wgl__img">';
		$wgl_display .= '</div>';
		
		$wgl_display .= '<div class="wgl__text-container">';
		$wgl_display .= '<p class="wgl__title-date">'.$row->post_title.'</p>';
		$wgl_display .= '</div>';
			
		$wgl_display .= '</div>';
	}		

	$wgl_display .= '</div>';
		
	return $wgl_display;
}