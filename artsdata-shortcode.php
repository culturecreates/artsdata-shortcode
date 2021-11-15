<?php

/*
Plugin Name:  Artsdata Shortcodes for WP
Version: 0.1
Description: Collection of shortcodes to display data from Artsdata.ca.
Author: Culture Creates
Author URI: https://culturecreates.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: artsdata
*/

/**
 * [artsdata_orgs] returns the HTML code for a list of organizations.
 * params: 
 * members = artsdata graph of members to display. i.e. CapacoaMembers
 * path = permalink /%postname%/ to load details of individual org id. 
 * @return string HTML Code
*/
add_shortcode( 'artsdata_orgs', 'artsdata_list_orgs' );


/**
 * [artsdata_id] returns the HTML code for the org id.
 * @return string HTML Code
*/
add_shortcode('artsdata_id', 'artsdata_show_id');


function artsdata_init(){

  /** Enqueuing the Stylesheet for Artsdata */
  function artsdata_enqueue_scripts() {
    global $post;
    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'artsdata_orgs') ) {
    wp_register_style( 'artsdata-stylesheet',  plugin_dir_url( __FILE__ ) . 'css/style.css' );
        wp_enqueue_style( 'artsdata-stylesheet' );
    }
  }
  add_action( 'wp_enqueue_scripts', 'artsdata_enqueue_scripts');

  function artsdata_list_orgs($atts) {
    # controller
    $a = shortcode_atts( array(
      'membership' => 'CapacoaMembers',
      'path' => 'resource'
    ), $atts);
    $response = wp_remote_get( 'http://api.artsdata.ca/organizations.jsonld?source=' . $a['membership'] );
    $body     = wp_remote_retrieve_body( $response );
    $j = json_decode( $body, true);
  
    # view
    $html = '<div class="artsdata-orgs"><p><ul>';
    foreach ($j['@graph'] as $org) {
      $html .= '<li><a href="/' . $a['path'] . '?id=' . strval($org['id']) . '">' . $org['namePref']  . '</a></li>' ;
    } 
    $html .= '</ul></p></div>';
    return  $html;
  }

  function artsdata_show_id() {
    # controller
    $api_url = "http://api.artsdata.ca/ranked/" . $_GET['id'] . "?format=json" ; 
    $response = wp_remote_get(  $api_url );
    $body     = wp_remote_retrieve_body( $response );
    $j = json_decode( $body, true);
    $data = $j['data'][0];
    $name = $data["namePref"];
    $logo = $data["logo"];
    $url = $data["url"];
    $address = $data["address"]["streetAddress"];
    $province = $data["address"]["addressRegion"];
    $municipality = $data["address"]["addressLocality"];
    $postalcode = $data["address"]["postalCode"];

    # view
    $html = '<div class="artsdata-org-detail"><h2>' . $name . '</h2>';
    $html .= '<p>';
    $html .= '<a href="' . $url . '">' . $url . '</a> ' ;
    $html .= '<p><img style="width:300px;margin-right:15px;float:left" src="' . $logo . '">';
    $html .= $municipality . ', ' . $province . '<br>' . $address . '<br>' . $postalcode . '<br>';
    $html .= '<a href="' . $_GET['id'] . '">' .  'Artsdata ID </a> ' ;
    $html .= '</p></div>';
    
    return $html;
  }
}

add_action('init', 'artsdata_init');



/** Added by GSF to unlock users **/

add_filter('rest_user_query', 'remove_has_published_posts_from_api_user_query', 10, 2);
function remove_has_published_posts_from_api_user_query($prepared_args, $request)
{
	unset($prepared_args['has_published_posts']);

	return $prepared_args;
}

/** Added by GSF to add user metadata fields to Users API **/

function get_user_meta_for_restapi($user, $field_name, $request) {
 // return get_userdata( $user['id'] );
	  $userObj = get_userdata( $user['id'] );

    return array (
		'operating_name1' => $userObj->operating_name1,
		'operating_name1_language' => $userObj->operating_name1_language,
		'operating_name2' => $userObj->operating_name2,
		'operating_name2_language' => $userObj->operating_name2_language,
		'legal_name' => $userObj->legal_name,
		'legal_name_language' => $userObj->legal_name_language,
		'business_number' => $userObj->business_number,
		'legal_form' => $userObj->legal_form,
		'charitable_status' => $userObj->charitable_status,
		'civic_number' => $userObj->civic_number,
		'street_address' => $userObj->street_address,
		'suite_apartment' => $userObj->suite_apartment,
		'municipality' => $userObj->municipality,
		'province' => $userObj->province,
		'country' => $userObj->country,
		'postal_code' => $userObj->postal_code,
		'artsdata_id' => $userObj->artsdata_id,
		'wikidata_id' => $userObj->wikidata_id,
		'discipline_genre' => $userObj->discipline_genre,
		'website_official' => $userObj->website_official,
    );
}

function create_restapi_user_obj_field() {
    // register_rest_field ( 'name-of-post-type', 'name-of-field-to-return', array-of-callbacks-and-schema() )
    register_rest_field('user', 'usermeta', array(
        'get_callback'    => 'get_user_meta_for_restapi',
        'schema'          => null,
    ));

}
add_action('rest_api_init', 'create_restapi_user_obj_field');

?>
