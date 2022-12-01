<?php

/*
Plugin Name: Artsdata Shortcodes
Version: 1.2.1
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

  /** Enqueuing Stylesheets and Scripts */
  function artsdata_enqueue_scripts() {
    global $post;
    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'artsdata_id') ) {
	wp_register_style( 'leaflet_css', 'https://unpkg.com/leaflet@1.9.2/dist/leaflet.css', array(), null );
		wp_enqueue_style( 'leaflet_css' );
	wp_register_script( 'leaflet_js', 'https://unpkg.com/leaflet@1.9.2/dist/leaflet.js', array(), null );
		wp_enqueue_script( 'leaflet_js' );
	/** Load plugin for Leaflet fullscreen controls **/
	wp_register_style( 'leaflet_fullscreen_css', plugin_dir_url( __FILE__ ) . 'css/Control.FullScreen.css', array(), null);
		wp_enqueue_style( 'leaflet_fullscreen_css' );
    wp_register_script('leaflet_fullscreen_js', plugin_dir_url( __FILE__ ) . 'js/Control.FullScreen.js', array(), null);
    	wp_enqueue_script( 'leaflet_fullscreen_js' );
    wp_register_style( 'artsdata-stylesheet',  plugin_dir_url( __FILE__ ) . 'css/style.css' );
        wp_enqueue_style( 'artsdata-stylesheet' );
    /** Artsdata script must be loaded in the footer after all Leaflet code **/
    wp_register_script('artsdata_script', plugin_dir_url( __FILE__ ) . 'js/artsdata.js', array(), null, true);
    	wp_enqueue_script( 'artsdata_script' );
    }
	function add_leaflet_cdn_attributes( $html, $handle ) {
	    if ( 'leaflet_css' === $handle ) {
	        return str_replace( "media='all'", "media='all' integrity='sha256-sA+zWATbFveLLNqWO2gtiw3HL/lh1giY/Inf1BJ0z14=' crossorigin=''", $html );
	    }
	    if ( 'leaflet_js' === $handle ) {
	        return str_replace( "media='all'", "media='all' integrity='sha256-o9N1jGDZrf5tS+Ft4gbIK7mYMipq9lqpVJ91xHSyKhg=' crossorigin=''", $html );
	    }
	    return $html;
	}
	add_filter( 'style_loader_tag', 'add_leaflet_cdn_attributes', 10, 2 );
  }
  add_action( 'wp_enqueue_scripts', 'artsdata_enqueue_scripts');

  function artsdata_admin() {
    delete_transient( 'artsdata_list_orgs_response_body' ) ;


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

    $body = get_transient( 'artsdata_list_orgs_response_body' );

    if ( false === $body ) {

        $response = wp_remote_get( 'http://api.artsdata.ca/organizations.jsonld?limit=200&source=' . $a['membership'] );
        if (200 !== wp_remote_retrieve_response_code($response)) {
            return;
        }
        $body  = wp_remote_retrieve_body( $response );
        set_transient( 'artsdata_list_orgs_response_body', $body, 1 * DAY_IN_SECONDS );
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
      $html .= '<li class="' . formatClassNames($org['additionalType']) . '"><a href="/' . $a['path'] . '/?uri=' . strval( $org['sameAs'][0]['id']) . '">' .  languageService($org, 'name')  . '</a> </li>';
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
      return "<p>" .  esc_html__( 'Missing Artsdata ID. Please return to the membership directory.', 'artsdata-shortcodes' ) . "</p>";
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

    $venues = $data["location"] ;


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
    $html .= '<a ' . dataMaintainer($rankedProperties, "url") . ' href="' . $url . '">' . $url . '</a>';
    $html .= '</p>';
    if ($organization_type) {
      $html .= '<div class="artsdata-category">';
      $html .= '<div class="artsdata-category-type"><p class="artsdata-organization-type">';
      $html .= esc_html__( 'Member Type:', 'artsdata-shortcodes' ) . '</p></div>';
      $html .= '<div class="artsdata-category-properties"><ul ' . dataMaintainer($rankedProperties, "additionalType") . '>' .  $organization_type  . '</ul>';
      $html .= '</div>';
      $html .= '</div>';
    }
    if ($presenter_type) {
      $html .= '<div class="artsdata-category">';
      $html .= '<div class="artsdata-category-type"><p class="artsdata-presenter-type">';
      $html .=  esc_html__( 'Presenter Type:', 'artsdata-shortcodes' ) . '</p></div>';
      $html .= '<div class="artsdata-category-properties"><ul ' . dataMaintainer($rankedProperties, "additionalType") . '>' . $presenter_type . '</ul>';
      $html .= '</div>';
      $html .= '</div>';
    }
    if ($disciplines) {
      $html .= '<div class="artsdata-category">';
      $html .= '<div class="artsdata-category-type"><p class="artsdata-disciplines">';
      $html .=  esc_html__( 'Disciplines:', 'artsdata-shortcodes' ) . '</p></div>';
      $html .= '<div class="artsdata-category-properties"><ul ' . dataMaintainer($rankedProperties, "additionalType") . '>' . $disciplines . '</ul>';
      $html .= '</div>';
      $html .= '</div>';
    }
    if ( $presentationFormat &&  $presentationFormat !== "empty") {
      $html .= '<div class="artsdata-category">';
      $html .= '<div class="artsdata-category-type"><p class="artsdata-presentation-format">';
      $html .= esc_html__( 'Presentation Format:', 'artsdata-shortcodes' ) . '</p></div>';
      $html .= '<div class="artsdata-category-properties"><ul ' . dataMaintainer($rankedProperties, "additionalType") . '>' . $presentationFormat . '</ul>';
      $html .= '</div>';
      $html .= '</div>';
    }
    if ($artsdataId) {
      $html .= '<div class="artsdata-artsdata-id">';
      $html .= '<p>' . esc_html__( 'Artsdata ID:', 'artsdata-shortcodes' ) .' <a href="' . $artsdataId . '">' . ltrim($artsdataId, "http://kg.artsdata.ca/resource/") . ' </a></p>';
      $html .= '</div>';
    }
    if ($wikidataId) {
      $html .= '<div class="artsdata-wikidata-id">';
      $html .= '<p>' . esc_html__( 'Wikidata ID:', 'artsdata-shortcodes' ) .' <a ' . dataMaintainer($rankedProperties, "identifier") . ' href="' .  $wikidataUrl . '">' . $wikidataId . ' </a></p>';
      $html .= '</div>';
    }
    $html .= '<div class="artsdata-social-media-row">';
    if ( $data["facebookId"]) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "http://www.wikidata.org/prop/direct/P2013") . ' class="social-media-icon" href="' . $facebook . '"> <img src="https://upload.wikimedia.org/wikipedia/commons/9/9b/Font_Awesome_5_brands_facebook-square.svg"></a> </div> '; }
    if ( $data["twitterUsername"]) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "http://www.wikidata.org/prop/direct/P2002") . ' class="social-media-icon" href="' . $twitter . '"><img  src="https://upload.wikimedia.org/wikipedia/commons/c/cf/Font_Awesome_5_brands_Twitter_square.svg"></a>  </div>'; }
    if ( $data["instagramUsername"]) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "http://www.wikidata.org/prop/direct/P2003") . 'class="social-media-icon"  href="' . $instagram . '"><img  src="https://upload.wikimedia.org/wikipedia/commons/1/18/Font_Awesome_5_brands_Instagram_square.svg"></a>  </div>'; }
    if ( $youtube) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $youtube . '"><img  src="https://commons.wikimedia.org/wiki/File:Font_Awesome_5_brands_youtube-square.svg"></a> </div>'; }
    if ( $wikipedia) { $html .= '<div class="artsdata-social-media-column"><a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $wikipedia . '"><img  src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fc/Wikipedia-logo_BW-hires.svg/240px-Wikipedia-logo_BW-hires.svg.png"></a>  </div>'; }
    $html .= '</div></div>';

    $html .= '<div class="artsdata-venue-detail">';
    if ($venues) {
      $html .= '<h4 class="artsdata-venues-title">' .  esc_html__( 'Venues', 'artsdata-shortcodes' ) . '</h4>';

      // example http://api.artsdata.ca/ranked/K10-440?format=json&frame=ranked_org
      foreach ($venues as $venue) {
        if ($venue["location"][0]["nameEn"]) { // skip venues without en names (TODO: add fr)
		  $html .= '<div class="artsdata-venue-wrapper">';
	          $html .= '<div class="artsdata-place">';


             	  $html .= '<div class="artsdata-place-map-wrapper">';
             	  	//
             	  	//
             	  	// FOREACH required so that this DIV's ID can be auto-incremented as a unique ID for each venue (i.e. map1, map2, map3)
             	  	// The same will need to be done in the plugin's JS file for outputting the coordinates unique to each ID
             	  	//
             	  	//
             	  	$html .= '<div id="map1" class="artsdata-place-map-entry"></div>';
             	  	//
             	  	//
             	  	// Coordinates not needed here, need to be loaded in plugin's JS file
             	  	// $html .= '<p class="artsdata-place-coordinates">' . $single_place["geoCoordinates"]["@value"] . '</p>';
             	  	//
             	  	//
             	  $html .= '</div>';
             	  $html .= '<div class="artsdata-place-entry">';
    	         	  $html .= '<div class="artsdata-place-details">';
    		            $single_place = $venue["location"][0] ;
    		            $html .= '<p class="artsdata-place-type">' . $single_place["additionalType"] . '</p>' ;
    		            $html .= '<h5 class="artsdata-place-name" ' . dataMaintainer($rankedProperties, "location") . '>' . $single_place["nameEn"] . '</h5>' ;
    		            $html .= '<p class="artsdata-place-address">' . $single_place["address"]["@value"] . '</p>' ;
    		            $html .= '<p class="artsdata-place-wikidata-id">' . 'Wikidata ID: ' . ' <a href="' . $single_place["id"] . '">' . $single_place["id"] . '</a></p>';
    		          $html .= '</div>';
    		          $html .= '<div class="artsdata-place-thumbnail">';
    		          	//
    		          	//
    		          	// IF statement required to display placeholder SVG when no primary venue thumbnail exists
    		          	// Not sure whether or not the IMG tag can be included in the same DIV that has the inline style for background-image
    		          	// The inline style is needed in order to crop the thumbnails to squares
    		          	//
    		          	//
    		            $html .= '<div class="artsdata-place-image" style="background-image: url(' . $single_place["image"] . ')" /></div>' ;
    		            // $html .= '<div class="artsdata-place-image"><img src="https://upload.wikimedia.org/wikipedia/commons/2/20/Font_Awesome_5_solid_building.svg"; class="placeholder" /></div>' ;
    		          $html .= '</div>';
    		            if (gettype($single_place["containsPlace"]) == 'array' ) {  // TODO: Frame containsPlace to be an array
    		              if ($single_place["containsPlace"][0]["nameEn"]) { // skip venues without names (TODO: add fr)
    				          $html .= '<div class="artsdata-place child">';
    			                foreach ($single_place["containsPlace"] as $room) {
    							$html .= '<div class="artsdata-place-entry child">';
    				              $html .= '<div class="artsdata-place-details child">';
    			                    $html .= '<p class="artsdata-place-type child">' . $room["additionalType"] . '</p>' ;
    			                    $html .= '<h6 class="artsdata-place-name">' . $room["nameEn"] . '</h6>';
    			                    $html .= '<p class="artsdata-place-wikidata-id">' . 'Wikidata ID: ' . ' <a href="' . $room["id"] . '">' . $room["id"] . '</a></p>';
    			                    $html .= '</div>';
    					            $html .= '<div class="artsdata-place-thumbnail child">';
    					              //
    					              //
    					              // IF statement needed to display this div only if a thumbnail exists
    					              //
    					              //
    					              $html .= '<div class="artsdata-place-image child" style="background-image: ' . $room["image"] . '"></div>' ; //need to have it pulled from AD
    					           $html .= '</div>';
    					           $html .= '</div>';
							  }
    						  $html .= '</div>';
    			          }
    			        }
    		          $html .= '</div>';


		      $html .= '</div>';
          $html .= '</div>';
  		  }
        }
    }
    $html .= '</div>';

    if ($event_data || $urlEvents ) {
	  $html .= '<div class="artsdata-events-detail">';
    $html .= '<h4 class="artsdata-upcoming-events-title">' .  esc_html__( 'Upcoming Events', 'artsdata-shortcodes' ) . '</h4>';
    $html .= '<div class="artsdata-events-entries">';
    foreach ($event_data as $event) {
      $html .= '<div class="artsdata-event">';
      $html .= '<a href="' . safeUrl($event["url"]) . '"><img src="' . safeUrl($event["image"]) . '"></a>';
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
     $maintainer = "title='" .  esc_html__( 'Data from Artsdata.ca sourced from', 'artsdata-shortcodes' )  . " ";
     foreach ($rankedProperties as $rankedProperty) {
       if ($rankedProperty["id"] == $prop ) {
        $maintainer .= $rankedProperty["isPartOfGraph"]["maintainer"];
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
        if ($type['label' . $lang]) {$str .= "<li>" . $type['label' . $lang] . "</li>" ;}
        elseif ($type['labelPref']) {$str .= "<li>" .$type['labelPref'] . "</li>" ;}
        elseif ($type['labelEn']) {$str .= "<li>" .$type['labelEn'] . "</li>" ;}
        elseif ($type['labelFr']) {$str .= "<li>" .$type['labelFr'] . "</li>" ;}
        elseif ($type['label']) {$str .= "<li>" .$type['label'] . "</li>" ;}
      }
    }
    return $str ;
  }

  function safeUrl($strIn) {
    if  (gettype($strIn) == 'string') {
      return $strIn ;
    } else {
      return $strIn['id'] ;
    }
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
