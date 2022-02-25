<?php

/*
Plugin Name:  Artsdata Shortcodes for WP
Version: 0.5
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
      'membership' => 'http://kg.artsdata.ca/culture-creates/huginn/capacoa-members',
      'path' => 'resource'
    ), $atts);
    $response = wp_remote_get( 'http://api.artsdata.ca/organizations.jsonld?limit=200&source=' . $a['membership'] );
    $body     = wp_remote_retrieve_body( $response );
    $j = json_decode( $body, true);
    $graph = $j['@graph'];
    usort($graph, function ($x, $y) {
      if ($x['namePref'] === $y['namePref']) {
          return 0;
      }
      return $x['namePref'] < $y['namePref'] ? -1 : 1;
    });
    
    # view
    $html = '<div class="artsdata-orgs"><p><ul>';

    foreach ($graph as $org) {
      $html .= '<li><a href="/' . $a['path'] . '?uri=' . strval( $org['sameAs'][0]["id"]) . '">' . $org['namePref']  . '</a> </li>' ;
    } 
    $html .= '</ul></p></div>';
   // $html .=  print_r($graph);
    return  $html;
  }

  function artsdata_show_id() {
    # Org controller
    $api_url = "http://api.artsdata.ca/ranked/" . $_GET['uri'] . "?format=json&frame=ranked_org" ; 
    $response = wp_remote_get(  $api_url );
    $body     = wp_remote_retrieve_body( $response );
    $j = json_decode( $body, true);
    $data = $j['data'][0];
    $name = languageService($data, 'name')  ;
    if ($name == "") { $name = $data["nameFr"] ;}
    $logo = $data["logo"];
    $url = checkUrl($data["url"][0]);
    $locality = $data["address"]["addressLocality"];
    $region = $data["address"]["addressRegion"];
    $country = $data["address"]["addressCountry"];
    $organization_type = generalType( $data["additionalType"],"PrimaryActivity" ) ;
    $disciplines =  generalType( $data["additionalType"],"Discipline" ) ;
    $presentationFormat =  generalType( $data["additionalType"],"PresentingFormat" ) ;
    $artsdataId =  $_GET['uri'];
    $wikidataId =  $data["identifier"] ;
    $facebook = linkExtraction($data["sameAs"] , "facebook.com") ;
    $twitter = linkExtraction($data["sameAs"] , "twitter.com") ;
    $youtube = linkExtraction($data["sameAs"] , "youtube.com") ;
    $wikipedia = linkExtraction($data["sameAs"] , "wikipedia.org") ;
    $instagram = linkExtraction($data["sameAs"] , "instagram.com") ;
    $venue1Role = $data["location"][0]["roleName"] ;
    $venue1Name = $data["location"][0]["location"]["namePref"];
    $venue1Wikidata = $data["location"][0]["location"]["identifier"];
    $venue2Role =  $data["location"][1]["roleName"];
    $venue2Name = $data["location"][1]["location"]["namePref"];
    $venue2Wikidata = $data["location"][1]["location"]["identifier"];
    $urlEvents = checkUrl($data["url"][1]["url"][0]);

    # Events Controller
    $api_path = "http://api.artsdata.ca/events.json" ;
    $api_frame =  '?frame=event_location' ;
    $api_query = '&predicate=schema:organizer&object=' . $_GET['uri'] ;
    $event_api_url = $api_path .  $api_frame . $api_query ;
    $event_response = wp_remote_get( $event_api_url ) ;
    $event_body     = wp_remote_retrieve_body( $event_response );
    $event_j = json_decode( $event_body, true);
    $event_data = $event_j['data'];


    # Org View
    $html = '<div class="artsdata-org-detail"><h3>' . $name . '</h3>';
    $html .= '<p>';
    $html .= $locality . ', ' . $region . ', ' . $country . '<br>';
    $html .= '<a href="' . $url . '">' . $url . ' </a> ' ;
    $html .= '</p>';
    $html .= '<p>';
    $html .= 'Organization Type: <br><b>' .  $organization_type  . '</b>' ;
    $html .= '</p>';
    
    if ($disciplines) {
      $html .= '<p>';
      $html .= 'Disciplines:<br> <b>' . $disciplines . '</b><br>' ; 
      $html .= '</p>';
    }
    if ( $presentationFormat &&  $presentationFormat !== "empty") {
    $html .= '<p>';
    $html .= 'Presentation Format: <br><b>' . $presentationFormat . '</b><br>' ;
    $html .= '</p>';
    }
    $html .= 'Artsdata ID <a href="' . $artsdataId . '">' . ltrim($artsdataId, "http://kg.artsdata.ca/resource/") . ' </a> <br>' ;
    $html .= 'Wikidata ID <a href="' . $wikidataId . '">' . $wikidataId . ' </a> </p>' ;
    $html .= '<p>';
    if ( $facebook) { $html .= '<a href="' . $facebook . '">Facebook</a>  '  ; }
    if ( $twitter) { $html .= '<a href="' . $twitter . '">Twitter</a>  '  ; }
    if ( $youtube) { $html .= '<a href="' . $youtube . '">Youtube</a> '  ; }
    if ( $wikipedia) { $html .= '<a href="' . $wikipedia . '">Wikipedia</a> '  ; }
    if ( $instagram) { $html .= '<a href="' . $instagram . '">Instagram</a>  '  ; }
    $html .= '</p>';

    if ($venue1Name || $venue2Name ) {
      $html .= '<h5>Venues</h5>';
    }

    if ($venue1Name) { 
      $html .= '<p>';
      if ($venue1Role) { $html .= $venue1Role . ':<br>' ; }
      $html .= '<b>' . $venue1Name . '</b>' ;
      if ($venue1Wikidata) { $html .= ' (' .  $venue1Wikidata . ') ' ; }
      $html .= '</p>';
    }
    if ($venue2Name) { 
      $html .= '<p>';
      if ($venue2Role) { $html .= $venue2Role . ':<br>' ; }
      $html .= '<b>' . $venue2Name . '</b>' ;
      if ($venue2Wikidata) { $html .= ' (' .  $venue2Wikidata . ') ' ; }
      $html .= '</p>';
    }

    if ($event_data || $urlEvents ) {
    $html .= '<h5> Upcoming Events </h5>';
    }
    foreach ($event_data as $event) {
      $html .= '<div style="overflow: auto;">' ;
      $html .= '<img style="width:300px;margin:0 15px 15px 0;float:left" src="' . $event["image"] . '">'; 
      $html .= '<b>' . languageService($event, 'name') . ' </b><br>';
      $html .=  languageService($event["location"], 'name') . '<br>' ;
      $html .= $event["startDate"][0] ;
      $html .= '</div >';
    }
   if ($urlEvents) { $html .= '<a href="' . $urlEvents . '">View all events</a>' ; }
   
    $html .= '</div>';
  # $html  .=  print_r($data["url"]);
    
    return $html;
  }

  function languageService($entity, $prop) {
   
    if ($entity[$prop . "Fr"]) { return $entity[$prop . "Fr"]; } 
    if ($entity[$prop . "En"]) { return $entity[$prop . "En"];}
    if ($entity[$prop . "Pref"]) { return $entity[$prop . "Pref"]; }
    if ($entity[0][$prop . "Fr"]) { return $entity[0][$prop . "Fr"]; } 
    if ($entity[0][$prop . "En"]) { return $entity[0][$prop . "En"];}
    if ($entity[0][$prop . "Pref"]) { return $entity[0][$prop . "Pref"]; }
  }

  function checkUrl($url) {
    if ($url == "") {
      return '' ;
    }
    if ( strpos($url,  "http") !== 0 ) {
      $url = 'http://' . $url ;
    }
    return $url ;
  }

  function generalType($types, $detectionStr) {
    $str = '' ;
    foreach ($types as $type) {
      if ( strpos($type['id'],  $detectionStr) !== false ) {
        $str .= $type['label'] . ", " ;
      }
    }
    return rtrim($str, ", ") ;
  }

  function linkExtraction($sameAs, $detectionStr) {
    $str = '' ;
    foreach ($sameAs as $link) {
      if  (gettype($link) == 'string') {
        if ( strpos($link,  $detectionStr) !== false ) {
          $str = $link ;
        }
      }
    }
    return $str ;
  }

}

add_action('init', 'artsdata_init');

?>
