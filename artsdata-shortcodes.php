<?php

/*
Plugin Name: Artsdata Shortcodes
Version: 1.1.4
Description: Collection of shortcodes to display data from Artsdata.ca.
Author: Culture Creates
Author URI: https://culturecreates.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: artsdata-shortcodes
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


/**
 * [artsdata_admin] display admin button HTML code to reload data from sources.
 * @return string HTML Code
*/
add_shortcode('artsdata_admin', 'artsdata_admin');




function artsdata_init(){
  /** Load text domain for i18n **/
  $plugin_rel_path = basename( dirname( __FILE__ ) ) . '/languages'; /* Relative to WP_PLUGIN_DIR */
  load_plugin_textdomain( 'artsdata-shortcodes', false, $plugin_rel_path );

  /** Enqueuing the Stylesheet for Artsdata */
  function artsdata_enqueue_scripts() {
    global $post;
    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'artsdata_id') ) {
    wp_register_style( 'artsdata-stylesheet',  plugin_dir_url( __FILE__ ) . 'css/style.css' );
        wp_enqueue_style( 'artsdata-stylesheet' );
    }
  }
  add_action( 'wp_enqueue_scripts', 'artsdata_enqueue_scripts');

  function artsdata_admin() {
    delete_transient( 'artsdata_list_orgs_response_body_En' ) ;
    delete_transient( 'artsdata_list_orgs_response_body_Fr' ) ;

    $html = '<div class="artsdata-admin"><h2>Artsdata Admin</h2>' ;
    $html .= '<p>' ;
    $html .= 'Reload and transform data from the CAPACOA database. This will replace all existing data on Artsdata with new data. Allow 5 minutes for all transforms to complete and then refresh your webpage to see the update.' ;
    $html .= '<form action="https://huginn-staging.herokuapp.com/users/1/web_requests/141/capacoamembers" method="post">' ;
    $html .= '<input type="submit" value="Reload CAPACOA database">' ;
    $html .= '</form>' ;
    $html .= '</p>' ;
    $html .= '<p>' ;
    $html .= 'Reload data from Wikidata. Allow 1 minute for all transforms to complete and then refresh your webpage to see the update.' ;

    $html .= '<form action="https://huginn-staging.herokuapp.com/users/1/web_requests/136/capacoamembers" method="post">' ;
    $html .=  '<input type="submit" value="Load updates from Wikidata">' ;
    $html .= '</form>' ;
    $html .= '</p>' ;
    $html .= '</div>';
    return  $html;
  }

  function artsdata_list_orgs($atts) {
    # controller
    $a = shortcode_atts( array(
      'membership' => 'http://kg.artsdata.ca/culture-creates/huginn/capacoa-members',
      'path' => 'resource'
    ), $atts);

    if ( strpos($current_path,  "fr/") !== false ) {
      $lang = 'Fr' ;
    } else {
      $lang = 'En' ;
    }

    $body = get_transient( 'artsdata_list_orgs_response_body_' . $lang );

    if ( false === $body ) {

        $response = wp_remote_get( 'http://api.artsdata.ca/organizations.jsonld?limit=200&source=' . $a['membership'] );
        if (200 !== wp_remote_retrieve_response_code($response)) {
            return;
        }
        $body  = wp_remote_retrieve_body( $response );
        set_transient( 'artsdata_list_orgs_response_body_' . $lang, $body, 1 * DAYS_IN_SECONDS );
    }


    $j = json_decode( $body, true);
    $graph = $j['@graph'];
    usort($graph, function ($x, $y) {
      if (languageService($x, 'name')  === languageService($y, 'name') ) {
          return 0;
      }
      return languageService($x, 'name') < languageService($y, 'name') ? -1 : 1;
    });

    # view
    $html = '<div class="artsdata-orgs"><p><ul>';
    foreach ($graph as $org) {
      $html .= '<li class="' . formatClassNames($org['additionalType']) . '"><a href="/' . $a['path'] . '?uri=' . strval( $org['sameAs'][0]['id']) . '">' .  languageService($org, 'name')  . '</a> </li>' ;
    }
    $html .= '</ul></p></div>';

   // $html .=  print_r($graph);
    return  $html;
  }

  function formatClassNames($types) {
    $str = '' ;
    foreach ($types as $type) {
      $str .= ltrim($type, "https://capacoa.ca/vocabulary#") . " " ;
    }

    return rtrim($str, " ") ;
  }


  function artsdata_show_id() {
    if ($_GET['uri'] == null) {
      return "<p>" .  esc_html__( 'Missing Artsdata ID. Please return to the membership directory.', 'artsdata-shortcodes' ) . "</p>" ;
    }
    # Org details controller
    $api_url = "http://api.artsdata.ca/ranked/" . $_GET['uri'] . "?format=json&frame=ranked_org" ;
    $response = wp_remote_get(  $api_url );
    $body     = wp_remote_retrieve_body( $response );
    $j = json_decode( $body, true);
    $data = $j['data'][0];
    $name = languageService($data, 'name')  ;
    $logo = $data["logo"];
    $url = checkUrl($data["url"][0]);
    $locality = $data["address"]["addressLocality"];
    $region = $data["address"]["addressRegion"];
    $country = $data["address"]["addressCountry"];
    $organization_type = generalType( $data["additionalType"],"PrimaryActivity" ) ;
    $presenter_type =  generalType( $data["additionalType"],"PresenterType" ) ;
    $disciplines =  generalType( $data["additionalType"],"Genres" ) ;
    $presentationFormat =  generalType( $data["additionalType"],"PresentingFormat" ) ;
    $artsdataId =  $_GET['uri'];
    $wikidataId = $data["identifier"] ;
    $wikidataUrl = "http://www.wikidata.org/entity/" . $wikidataId ;
    $facebook = 'https://www.facebook.com/' . $data["facebookId"] ;
    $twitter = 'https://twitter.com/' . $data["twitterUsername"] ;
    $instagram = 'https://www.instagram.com/' . $data["instagramUsername"] ;
    $youtube = linkExtraction($data["sameAs"] , "youtube.com") ;
    $wikipedia = linkExtraction($data["sameAs"] , "wikipedia.org") ;

    $venue1Role = $data["location"][0]["roleName"] ;
    $venue1Name = languageService($data["location"][0]["location"], "name");
    $venue1Wikidata = $data["location"][0]["location"]["identifier"];
    $venue1WikidataUrl = "http://www.wikidata.org/entity/" . $venue1Wikidata ; 
    $venue2Role =  $data["location"][1]["roleName"];
    $venue2Name = languageService($data["location"][1]["location"], "name");
    $venue2Wikidata = $data["location"][1]["location"]["identifier"];
    $venue2WikidataUrl = "http://www.wikidata.org/entity/" . $venue2Wikidata ; 
    $urlEvents = checkUrl($data["url"][1]["url"][0]);
    $rankedProperties = $data["hasRankedProperties"];

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
    $html = '<div class="artsdata-org-detail">';
    $html .= '<h3 class="artsdata-heading" ' . dataMaintainer($rankedProperties, "name") . '>' . $name . '</h3>';
    $html .= '<p class="artsdata-address" ' . dataMaintainer($rankedProperties, "address") . '>';
    if ($locality) {
      $html .= $locality . ', ' . $region . ', ' . $country . '<br>';
    }
    $html .= '<a ' . dataMaintainer($rankedProperties, "url") . 'href="' . $url . '">' . $url . ' </a> ' ;
    $html .= '</p>';
    if ($organization_type) {
      $html .= '<p class="artsdata-organization-type">';
      $html .=  esc_html__( 'Organization Type:', 'artsdata-shortcodes' ) . ' <span ' . dataMaintainer($rankedProperties, "additionalType") . '>' .  $organization_type  . '</span>' ;
      $html .= '</p>';
    }
    if ($presenter_type) {
      $html .= '<p class="artsdata-presenter-type">';
      $html .=  esc_html__( 'Presenter Type:', 'artsdata-shortcodes' ) . ' <span ' . dataMaintainer($rankedProperties, "additionalType") . '>' . $presenter_type . '</span>' ;
      $html .= '</p>';
    }
    if ($disciplines) {
      $html .= '<p class="artsdata-disciplines">';
      $html .=  esc_html__( 'Disciplines:', 'artsdata-shortcodes' ) . ' <span ' . dataMaintainer($rankedProperties, "additionalType") . '>' . $disciplines . '</span>' ;
      $html .= '</p>';
    }
    if ( $presentationFormat &&  $presentationFormat !== "empty") {
    $html .= '<p class="artsdata-presentation-format">';
    $html .=  esc_html__( 'Presentation Format:', 'artsdata-shortcodes' ) . ' <span ' . dataMaintainer($rankedProperties, "additionalType") . '>' . $presentationFormat . '</span>' ;
    $html .= '</p>';
    }
    if ($artsdataId) {
      $html .= '<div class="artsdata-artsdata-id">';
      $html .= '<p>Artsdata ID: <a href="' . $artsdataId . '">' . ltrim($artsdataId, "http://kg.artsdata.ca/resource/") . ' </a></p>' ;
      $html .= '</div>';
    }
    if ($wikidataId) {
      $html .= '<div class="artsdata-wikidata-id">';
      $html .= '<p>Wikidata ID: <a ' . dataMaintainer($rankedProperties, "identifier") . ' href="' .  $wikidataUrl . '">' . $wikidataId . ' </a></p>' ;
      $html .= '</div>';
    }
    $html .= '<div class="artsdata-social-media-row">' ;
    if ( $data["facebookId"]) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "http://www.wikidata.org/prop/direct/P2013") . ' class="social-media-icon" href="' . $facebook . '"> <img  src="https://upload.wikimedia.org/wikipedia/commons/9/9b/Font_Awesome_5_brands_facebook-square.svg"></a> </div> '  ; }
    if ( $data["twitterUsername"]) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "http://www.wikidata.org/prop/direct/P2002") . 'class="social-media-icon"  href="' . $twitter . '"><img  src="https://upload.wikimedia.org/wikipedia/commons/c/cf/Font_Awesome_5_brands_Twitter_square.svg"></a>  </div>'  ; }
    if ( $data["instagramUsername"]) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "http://www.wikidata.org/prop/direct/P2003") . 'class="social-media-icon"  href="' . $instagram . '"><img  src="https://upload.wikimedia.org/wikipedia/commons/1/18/Font_Awesome_5_brands_Instagram_square.svg"></a>  </div>'  ; }
    if ( $youtube) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $youtube . '"><img  src="https://commons.wikimedia.org/wiki/File:Font_Awesome_5_brands_youtube-square.svg"></a> </div> '  ; }
    if ( $wikipedia) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $wikipedia . '"><img  src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fc/Wikipedia-logo_BW-hires.svg/240px-Wikipedia-logo_BW-hires.svg.png"></a>  </div>'  ; }
    $html .= '</div>';
    $html .= '<div class="artsdata-venue-detail">';
    if ($venue1Name || $venue2Name ) {
      $html .= '<h5 class="artsdata-venues-title">' .  esc_html__( 'Venues', 'artsdata-shortcodes' ) . '</h5>';
    }
    if ($venue1Name) {
      $html .= '<div class="artsdata-venue">' ;
     // if ($venue1Role) { $html .= $venue1Role . ':<br>' ; }

      $html .= '<p class="artsdata-venue-location" ' . dataMaintainer($rankedProperties, "location") . '>' . $venue1Name . '</p>' ;
      if ($venue1Wikidata) { 
        $html .= '<p class="artsdata-venue-wikidata">Wikidata ID: <a href="' .  $venue1WikidataUrl . '">' .  $venue1Wikidata . '</a></p>'  ; 
      }
      $html .= '</div>';
    }
    if ($venue2Name) {
      $html .= '<div class="artsdata-venue">';
     // if ($venue2Role) { $html .= $venue2Role . ':<br>' ; }
      $html .= '<p class="artsdata-venue-location" ' . dataMaintainer($rankedProperties, "location") . '>' . $venue2Name . '</p>' ;
      if ($venue2Wikidata) { 
        $html .= '<p class="artsdata-venue-wikidata">Wikidata ID: <a href="' .  $venue2WikidataUrl . '">' .  $venue2Wikidata . '</a></p>'  ; 
      }
      $html .= '</div>';
    }

    if ($event_data || $urlEvents ) {
	  $html .= '<div class="artsdata-events-detail">';
    $html .= '<h5 class="artsdata-upcoming-events-title">' .  esc_html__( 'Upcoming Events', 'artsdata-shortcodes' ) . '</h5>';
    $html .= '<div class="artsdata-events-entries">';
    foreach ($event_data as $event) {
      $html .= '<div class="artsdata-event">';
      $html .= '<a href="' . $event["url"] . '"><img src="' . $event["image"] . '"></a>';
      $html .= '<p class="artsdata-event-name">' . languageService($event, 'name') . '</p>';
      $html .= '<p class="artsdata-event-location">' .languageService($event["location"], 'name') . '</p>';
      $showTime =  new DateTime($event["startDate"][0]) ;
      $dateTimeFormatted = $showTime->format('Y-m-d g:ia');
      $html .= '<p class="artsdata-event-date">' . $dateTimeFormatted  . '</p>';
      $html .= '</div>';
    }
	  if ($urlEvents) { $html .= '<a href="' . $urlEvents . '">' .  esc_html__( 'View all events', 'artsdata-shortcodes' ) . '</a>'; }
    $html .= '</div>';
    $html .= '</div>';
    }

   // $html  .=  print_r( $rankedProperties);
    return $html;
  }

   function  dataMaintainer($rankedProperties, $prop) {
     $maintainer = "title='" .  esc_html__( 'Data from Artsdata.ca sourced from', 'artsdata-shortcodes' )  . " " ;
     foreach ($rankedProperties as $rankedProperty) {
       if ($rankedProperty["id"] == $prop ) {
        $maintainer .= $rankedProperty["isPartOfGraph"]["maintainer"] ;
       }
     }
     return $maintainer . "'" ;
  }

  function getLanguage() {
     # get current path
     global $wp;
     $current_path = add_query_arg( array(), $wp->request );
     if ( strpos($current_path,  "fr/") !== false ) {
      $lang = 'Fr' ;
    } else {
      $lang = 'En' ;
    }
    return $lang ;
  }


  function languageService($entity, $prop) {
    $lang = getLanguage() ;

    if ($entity[$prop . $lang]) { return $entity[$prop . $lang];}
    if ($entity[0][$prop . $lang]) { return $entity[0][$prop . $lang];}
    if ($entity[$prop . "Pref"]) { return $entity[$prop . "Pref"]; }
    if ($entity[0][$prop . "Pref"]) { return $entity[0][$prop . "Pref"]; }
    if ($entity[$prop . "Fr"]) { return $entity[$prop . "Fr"]; }
    if ($entity[$prop . "En"]) { return $entity[$prop . "En"]; }

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
    $lang = getLanguage() ;
    foreach ($types as $type) {
      if ( strpos($type['id'],  $detectionStr) !== false ) {
        if ($type['label' . $lang]) {$str .= $type['label' . $lang] . ", " ;}
        elseif ($type['labelPref']) {$str .= $type['labelPref'] . ", " ;}
        elseif ($type['labelEn']) {$str .= $type['labelEn'] . ", " ;}
        elseif ($type['labelFr']) {$str .= $type['labelFr'] . ", " ;}
        elseif ($type['label']) {$str .= $type['label'] . ", " ;}
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
