<?php
/*
Plugin Name: Out:think Videos
Plugin URI: http://outthinkgroup.com
Description: This plugin allows you to add vimeo or Youtube videos, set custom thumbnails, and play one at a time
Author: Joseph Hinson
Version: 1.0
Author URI: http://code.hinson.co
*/

add_action('init', 'govideos_setup');
add_action( 'init', 'govideo_initialize_cmb_meta_boxes', 9999 );
/**
 * Initialize the metabox class.
 */
function govideo_initialize_cmb_meta_boxes() {
	if ( ! class_exists( 'cmb_Meta_Box' ) )
		require_once 'cmb-metaboxes/init.php';
}

add_filter( 'cmb_meta_boxes', 'govideo_video_boxes', 10 );

function govideos_setup() {
	add_shortcode("show_videos", "GOVideo_Videos");

	// end shortcode
	add_image_size('video_thumb', 300, 225, true);
	add_theme_support( 'post-thumbnails', array( 'videos' ) );          // Posts only
	
	// Register the post type:
	register_post_type('videos', array(
	'label' => 'Videos',
	'description' => '',
	'public' => true,
	'show_ui' => true,
	'show_in_menu' => true,
	'capability_type' => 'post',
	'map_meta_cap' => true,
	'hierarchical' => false,
	'rewrite' => array('slug' => 'videos', 'with_front' => true),
	'query_var' => true,
	'exclude_from_search' => true,
	'supports' => array('title','custom-fields','thumbnail', 'page-attributes'),
	'labels' => array (
	  'name' => 'Videos',
	  'singular_name' => 'Video',
	  'menu_name' => 'Videos',
	  'add_new' => 'Add Video',
	  'add_new_item' => 'Add New Video',
	  'edit' => 'Edit',
	  'edit_item' => 'Edit Video',
	  'new_item' => 'New Video',
	  'view' => 'View Video',
	  'view_item' => 'View Video',
	  'search_items' => 'Search Videos',
	  'not_found' => 'No Videos Found',
	  'not_found_in_trash' => 'No Videos Found in Trash',
	  'parent' => 'Parent Video',
	)
	) );
}

function gov_get_video_id_from_url($url)
{
	if(strpos($url, "youtube.com") !== false || strpos($url, "youtu.be") !== false)
	{
		//Video is from YouTube
		$url_string = parse_url($url, PHP_URL_QUERY);
  		parse_str($url_string, $args);
  		if(isset($args['v']))
		{
			return $args['v'];
		}
		else
		{
			$path = parse_url($url, PHP_URL_PATH);
			if(strlen($path) > 1)
			{
				$path = str_replace("/", "", $path);
				return $path;
			}
			else
			{
				return false;
			}	
		}	

	}
	if(strpos($url, "vimeo") !== false)
	{
		//Video is from Vimeo	
		$path = parse_url($url, PHP_URL_PATH);
		if(strlen($path) > 1)
		{
			$path = str_replace("/", "", $path);
			return $path;
		}
		else
		{
			return false;
		}	
	}
}

// [show_videos]
function GOVideo_Videos($atts) {
		extract(shortcode_atts(array(
			"" => ""
        ), $atts));
		$return = '';
		$videos = get_posts('numberposts=-1&orderby=menu_order&order=ASC&post_type=videos&post_status=publish');
		$return .= '<style>.embed-container { position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden; max-width: 100%; height: auto; } .embed-container iframe, .embed-container object, .embed-container embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }</style>';
		$c = 1;
		$videoids = array();
		foreach ($videos as $video) {
			// if there's a youtube url
			if ($youtube = get_post_meta($video->ID, 'youtube_url', true)) {
				$vidID = gov_get_video_id_from_url($youtube);
				$embed = '<iframe width="560" height="300" src="//www.youtube.com/embed/'.$vidID.'?rel=0" frameborder="0" allowfullscreen></iframe>';
			} elseif ($vimeo = get_post_meta($video->ID, 'vimeo_url', true)) {
				$vidID = gov_get_video_id_from_url($vimeo);
				$embed = '<iframe src="//player.vimeo.com/video/'.$vidID.'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';				
			} elseif ($embed = get_post_meta($video->ID, 'iframe_embed', true)) {
				$embed = $embed;
			}
			if (has_post_thumbnail($video->ID)) {
				$thecontent[] = get_the_post_thumbnail($video->ID, 'video_thumb').'<br /><strong>'.$video->post_title.'</strong>';
			} elseif ($youtube) {
				$thecontent[] =  '<img src="http://img.youtube.com/vi/'.$vidID.'/0.jpg" alt="" title="" /><br /><strong>'.$video->post_title.'</strong>';
			}
			$videoids[] = $embed;
			$vidID = false;
			$youtube = false;
			$vimeo = false;
		}
		if (!empty($_GET['v']) ) {
			$mainvideo = $_GET['v'];
		} else {
			$mainvideo = 0;
		}
		$return .= '
			<style type="text/css">
			.video-thumb {
				width: 24%;
				margin-right: 1%;
				float: left;
			}
			.main-video {
				margin-bottom: 20px;
			}
			.govid_clr {
				clear: both;
				margin-bottom: 20px;
			}
		</style>';
		$return .= '<div class="main-video">
			<div class="embed-container">'.$videoids[$mainvideo].'</div>
		</div>';
		foreach ($videoids as $key => $value) {
			if ($key != $mainvideo ) {
				$return .= '
				<div class="video-thumb">
				<a href="?v='.$key.'">'.$thecontent[$key].'</a></div>';
			}
			
		}
		$return .= '<div class="govid_clr"></div>';
		return $return;
}



/**
 * Define the metabox and field configurations.
 *
 * @param  array $meta_boxes
 * @return array
 */
function govideo_video_boxes( array $meta_boxes ) {

	// Start with an underscore to hide fields from custom fields list
	$prefix = 'ac_';

	$meta_boxes[] = array(
		'id'         => 'alt_title',
		'title'      => 'Alternate Title',
		'pages'      => array( 'page', ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name'    => 'Red Text',
				'desc'    => 'Red text to be displayed in the title',
				'id'      => $prefix . 'red_text',
				'type'    => 'text'
			),
			array(
				'name'    => 'Black Text',
				'desc'    => 'Black text to be displayed in the title',
				'id'      => $prefix . 'black_text',
				'type'    => 'text'
			),
		), // end meta boxes for pages
		
		'id'         => 'video_id',
		'title'      => 'Video Details',
		'pages'      => array( 'videos', ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name'    => 'Youtube URL',
				'desc'    => 'Add Youtube URL (not embed code) here',
				'id'      => 'youtube_url',
				'type'    => 'text'
			),
			array(
				'name'    => 'Vimeo URL',
				'desc'    => 'Add Vimeo URL (not embed code) here',
				'id'      => 'vimeo_url',
				'type'    => 'text'
			),
			array(
				'name'    => 'Custom Embed Code',
				'desc'    => 'Add custom embed code here',
				'id'      => 'iframe_embed',
				'type'    => 'textarea_code'
			),
		), // end meta boxes for pages
	
	);

	return $meta_boxes;
}