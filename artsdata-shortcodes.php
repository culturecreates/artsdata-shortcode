<?php

/*
Plugin Name: Artsdata Shortcodes
Version: 2.0.5
Description: Collection of shortcodes to display data from Artsdata.ca.
Changelog: Maintain MemberType_ind and MemberType_organization CSS classes for layout.
Author: Culture Creates
Author URI: https://culturecreates.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: artsdata-shortcodes
*/

/**
 * [artsdata_orgs] returns the HTML code for a list of organizations.
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
      wp_register_style( 'artsdata-stylesheet',  plugin_dir_url( __FILE__ ) . 'css/style.css?v=20230314' );
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
    $html .= 'This page clears the transient cache for the list of member organizations. Click the button below to call delete_transient(\'artsdata_list_orgs_response_body\'). By default the cache is set to refresh every 24 hours.' ;
    $html .= '<form action="#" method="post">' ;
    $html .= '<input type="submit" value="Clear list of members in Wordpress cache">' ;
    $html .= '</form>' ;
    $html .= '</p>' ;
    $html .= '<p>' ;
    $html .= 'For documentation on this shortcode, please visit the <a href="https://github.com/culturecreates/artsdata-shortcode" target="_blank">Artsdata Shortcode documentation</a> on GitHub.' ;
    $html .= '</p>' ;
    $html .= '</div>';
    return  $html;
  }

  function artsdata_list_orgs($atts) {
    # controller for list
    $a = shortcode_atts( array(
      'path' => 'resource'
    ), $atts);

    $body = get_transient( 'artsdata_list_orgs_response_body' );

    if ( false === $body ) {
      $base_github = "https://raw.githubusercontent.com/culturecreates/artsdata-shortcode/refs/heads/master/" ;
      $sparql_path = $base_github . "public/sparql/members.sparql" ;
      $frame_path = $base_github . "public/frame/member.jsonld" ;
      $response = wp_remote_get( 'http://api.artsdata.ca/query?format=jsonld&sparql=' . $sparql_path . '&frame=' . $frame_path );
      if (200 !== wp_remote_retrieve_response_code($response)) {
          return;
      }
      $body  = wp_remote_retrieve_body( $response );
      set_transient( 'artsdata_list_orgs_response_body', $body, 1 * DAY_IN_SECONDS ); # documented in function artsdata_admin()
    }


    $j = json_decode( $body, true);
    $graph = $j['@graph'];
    usort($graph, function ($x, $y) {
      if (languageService($x, 'name')  === languageService($y, 'name') ) {
          return 0;
      }
      return languageService($x, 'name') < languageService($y, 'name') ? -1 : 1;
    });

    # view for list
    $html = '<div class="artsdata-orgs"><p><ul>';
    foreach ($graph as $org) {
      $html .= '<li class="' . formatClassNames($org['additionalType'], $org) . '"><a href="/' . $a['path'] . '/?uri=' . strval( isset($org['sameAs'][0]['id']) ? $org['sameAs'][0]['id'] : '') . '">' .  languageService($org, 'name')  . '</a> </li>';
    }
    $html .= '</ul></p></div>';

   // $html .=  print_r($graph);
    return  $html;
  }

  function formatClassNames($types, $org) {
    $str = '' ;
    foreach ($types as $type) {
      $str .= ltrim($type, "https://capacoa.ca/vocabulary#") . " " ;
    }
    if ($org["@type"] == "Person" ) { 
      $str .= "MemberType_ind" ; 
    } else {
      $str .= "MemberType_organization" ; 
    }
    
    return $str;
  }


  function artsdata_show_id() {
    if ($_GET['uri'] == null) {
      return "<p>" .  esc_html__( 'Missing Artsdata ID. Please return to the membership directory.', 'artsdata-shortcodes' ) . "</p>";
    }
    # Member details controller
    # test organization   http://api.artsdata.ca/query?adid=K14-29&sparql=capacoa/member_detail2&frame=capacoa/member2&format=json
    # test person http://api.artsdata.ca/query?adid=K14-141&sparql=capacoa/member_detail2&frame=capacoa/member2&format=json
    $base_github = "https://raw.githubusercontent.com/culturecreates/artsdata-shortcode/refs/heads/master/" ;
    $sparql_path = $base_github . "public/sparql/member_detail.sparql" ;
    $frame_path = $base_github . "public/frame/member.jsonld" ;
    $api_url = "http://api.artsdata.ca/query?adid=" . ltrim($_GET['uri'], "http://kg.artsdata.ca/resource/") . "&format=json&sparql=" . $sparql_path . "&frame=" . $frame_path;
    $response = wp_remote_get(  $api_url );
    $body     = wp_remote_retrieve_body( $response );
    $j = json_decode( $body, true);
    $data = $j['data'][0];

    $name = languageService($data, 'name')  ;
    $entity_type = isset($data["@type"]) ? $data["@type"] : null;
    $logo = isset($data["logo"]) ? $data["logo"] : null;
    $url = checkUrl($data["url"][0]);
    $locality = isset($data["address"]["addressLocality"]) ? $data["address"]["addressLocality"] : null;
    $region = isset($data["address"]["addressRegion"]) ? $data["address"]["addressRegion"] : null;
    $country = isset($data["address"]["addressCountry"]) ? $data["address"]["addressCountry"] : null;
    $organization_type = generalType( $data["additionalType"],"PrimaryActivity" ) ;
    $presenter_type =  generalType( $data["additionalType"],"PresenterType" ) ;
    $disciplines =  generalType( $data["additionalType"],"Genres" ) ;
    $presentationFormat =  generalType( $data["additionalType"],"PresentingFormat" ) ;
    $occupation =  generalType( $data["additionalType"],"PresentingFormat" ) ;
    $artsdataId =  $_GET['uri'];
    $wikidataId = isset($data["identifier"]) ? $data["identifier"] : null;
    $wikidataUrl = "http://www.wikidata.org/entity/" . $wikidataId ;
    $facebook = 'https://www.facebook.com/' . (isset($data["facebookId"]) ? $data["facebookId"] : '');
    $twitter = 'https://twitter.com/' . (isset($data["twitterUsername"]) ? $data["twitterUsername"] : '');
    $instagram = 'https://www.instagram.com/' . (isset($data["instagramUsername"]) ? $data["instagramUsername"] : '');
    $youtube = linkExtraction($data["sameAs"] , "youtube.com") ;
    $wikipedia = linkExtraction($data["sameAs"] , "wikipedia.org") ;
    $video_embed =  isset($data["video"]) ? $data["video"] : null;
    $bio = languageService($data, 'bio');
    $occupation = isset($data["hasOccupation"]) ? $data["hasOccupation"] : null;
    $member_image = isset($data["image"]) ? $data["image"] : null;
    $venues = isset($data["location"]) ? $data["location"] : null;
    $urlEvents = checkUrl(isset($data["url"][1]["url"][0]) ? $data["url"][1]["url"][0] : null);
    $rankedProperties = isset($data["hasRankedProperties"]) ? $data["hasRankedProperties"] : null;
    $naics_validated = null;
    $naics_inferred = null;

    # Events Controller
    $api_path = "http://api.artsdata.ca/events.json" ;
    $api_frame =  '?frame=event_location' ;
    $api_query = '&predicate=schema:organizer&object=' . $_GET['uri'] ;
    $event_api_url = $api_path .  $api_frame . $api_query ;
    $event_response = wp_remote_get( $event_api_url ) ;
    $event_body     = wp_remote_retrieve_body( $event_response );
    $event_j = json_decode( $event_body, true);
    $event_data = $event_j['data'];

    # Member View
    $html = '<div class="artsdata-org-detail">';
    $html .= '<div class="artsdata-org-header"><div class="artsdata-org-profile"><h3 class="artsdata-heading" ' . dataMaintainer($rankedProperties, "name") . '>' . $name . '</h3>';
    if ($locality) {
      $html .= '<p class="artsdata-address" ' . dataMaintainer($rankedProperties, "address") . '>' . $locality . ', ' . $region . ', ' . $country . '</p>';
    }
    if ($url) {
    $html .= '<p class="artsdata-website" ' . dataMaintainer($rankedProperties, "url") . '><a href="' . $url . '">' . $url . '</a></p>';
    }
    $html .='</div>';
	  //
    // profile image is only displayed if a wiki image / logo image is available, else hide div #profile-image-wrap
    // profile image anchor URL should pull in the source wiki page URL
	  //

    if ($member_image) {
      $html .= '<div id="profile-image-wrap" class="artsdata-org-profile-image"><a href="' .  $member_image . '" target="_blank" title="' .  esc_html__( 'Image from Wikimedia Commons. Click on the image to view photo credits.', 'artsdata-shortcodes' ) . '"><img src="' . $single_place["image"] . '"><img src="' .  $member_image . '" class="artsdata-profile-image-blank" alt="' .  esc_html__( 'Image of', 'artsdata-shortcodes' ) . ' ' . $name . '"></a></div>';
    }

    $html .= '</div>';
    $html .= '<div class="artsdata-external-links">';
    $html .= '<div class="artsdata-links-wrapper">';
    $html .= '<p class="artsdata-artsdata-id">' . esc_html__( 'Artsdata ID:', 'artsdata-shortcodes' ) .' <a class="artsdata-link-id-value" href="' . $artsdataId . '">' . ltrim($artsdataId, "http://kg.artsdata.ca/resource/") . ' </a></p>';
    if ($wikidataId) {
      $html .= '<p class="artsdata-wikidata-id">' . esc_html__( 'Wikidata ID:', 'artsdata-shortcodes' ) .' <a class="artsdata-link-id-value"  ' . dataMaintainer($rankedProperties, "identifier") . ' href="' .  $wikidataUrl . '">' . $wikidataId . ' </a></p>';
    }
    $html .= '</div>';
    $html .= '<div class="artsdata-socials-wrapper">';
    if ( isset($data["facebookId"]) ) { $html .= '<a ' . dataMaintainer($rankedProperties, "http://www.wikidata.org/prop/direct/P2013") . ' class="social-media-icon" href="' . $facebook . '"><i class="fab fa-facebook"></i></a>'; }
    if ( isset($data["twitterUsername"]) ) { $html .= '<a ' . dataMaintainer($rankedProperties, "http://www.wikidata.org/prop/direct/P2002") . ' class="social-media-icon" href="' . $twitter . '"><i class="fab fa-twitter"></i></a>'; }
    if ( isset($data["instagramUsername"]) ) { $html .= '<a ' . dataMaintainer($rankedProperties, "http://www.wikidata.org/prop/direct/P2003") . 'class="social-media-icon"  href="' . $instagram . '"><i class="fab fa-instagram"></i></a>'; }
    if ( isset($youtube) ) { $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $youtube . '"><i class="fab fa-youtube"></i></a>'; }
    if ( isset($wikipedia) ) { $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $wikipedia . '"><i class="fab fa-wikipedia-w"></i></a>'; }
    // $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $tiktok . '"><i class="fab fa-tiktok"></i></a>';
    // $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $linkedin . '"><i class="fab fa-linkedin"></i></a>';
    // $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $vimeo . '"><i class="fab fa-vimeo-v"></i></a>';
    // $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $bandcamp . '"><i class="fab fa-bandcamp"></i></a>';
    // $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $soundcloud . '"><i class="fab fa-soundcloud"></i></a>';
    // $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $spotify . '"><i class="fab fa-spotify"></i></a>';
    // $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $applemusic . '"><i class="fab fa-apple"></i></a>';
    // $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $amazonmusic . '"><i class="fab fa-amazon"></i></a>';
    // $html .= '<a ' . dataMaintainer($rankedProperties, "sameAs") . 'class="social-media-icon" href="' . $deezer . '"><i class="fab fa-deezer"></i></a>';
    $html .= '</div>';
    $html .= '</div>';
    if ($organization_type ) {
      $html .= '<div class="artsdata-category">';
      $html .= '<div class="artsdata-category-type"><p class="artsdata-organization-type">';
      $html .= esc_html__( 'Member Type:', 'artsdata-shortcodes' ) . '</p></div>';
      $html .= '<div class="artsdata-category-properties"><ul ' . dataMaintainer($rankedProperties, "additionalType") . '>' ;
      $html .=   $organization_type ;  
      $html .=   '</ul>';
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

    if ( $occupation &&  $occupation !== "empty") {
      $html .= '<div class="artsdata-category">';
      $html .= '<div class="artsdata-category-type"><p class="artsdata-presentation-format">';
      $html .= esc_html__( 'Occupation:', 'artsdata-shortcodes' ) . '</p></div>';
      $html .= '<div class="artsdata-category-properties"><ul ' . dataMaintainer($rankedProperties, "hasOccupation") . '>' . multiLingualList($occupation) . '</ul>';
      $html .= '</div>';
      $html .= '</div>';
    }

    if ($disciplines) {
      $html .= '<div class="artsdata-category">';
      $html .= '<div class="artsdata-category-type"><p class="artsdata-disciplines">';
      if ($entity_type == 'Organization') {
        $html .=  esc_html__( 'Disciplines:', 'artsdata-shortcodes' ) . '</p></div>';
      } else {
        $html .=  esc_html__( 'Artistic Focus:', 'artsdata-shortcodes' ) . '</p></div>';
      }
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

	//
    // NAICS code only displayed for organizations only, not individuals
    // if no validated NAICS code exists then change 'validated' to 'inferred', else hide entire category
	//
    if ( ($naics_validated && $naics_validated !== "empty") || ($naics_inferred && $naics_inferred !== "empty") ) {
      $html .= '<div class="artsdata-category">';
      $html .= '<div class="artsdata-category-type"><p class="artsdata-naics-code">';
      if ($naics_validated) {
        $html .= esc_html__( 'NAICS (validated):', 'artsdata-shortcodes' ) . '</p></div>';
        $html .= '<div class="artsdata-category-properties"><ul ' . dataMaintainer($rankedProperties, "naics") . '><li>' . $naics_validated . '</li></ul>';
      } else {
        $html .= esc_html__( 'NAICS (inferred):', 'artsdata-shortcodes' ) . '</p></div>';
        $html .= '<div class="artsdata-category-properties"><ul ' . dataMaintainer($rankedProperties, "naics") . '><li>' . $naics_inferred . '</li></ul>';
      }
      $html .= '</div>';
      $html .= '</div>';
    }

    $html .= '</div>';

	// Show video if available for all member types, not only for INDIVIDUAL members
  if ($video_embed) {
     $html .= '<div class="artsdata-member-video">';
     $html .= '<iframe width="100%" height="auto" src="' . $video_embed . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
     $html .= '</div>';
    }

  // Show bio if available for any member types, not only for INDIVIDUAL LIFETIME members
  if ($bio) {
    $html .= '<div class="artsdata-member-bio">';
    $html .= '<h4 class="artsdata-biography-title">';
    $html .= esc_html__( 'About:', 'artsdata-shortcodes' ) . '</h4>';
    $html .= '<p>' . $bio . '</p>';
    $html .= '</div>';
  }

    $html .= '<div class="artsdata-venue-detail">';
    if (isset($venues[0]["location"][0]["rdfsLabelEn"])) {
      $html .= '<h4 class="artsdata-venues-title">' .  esc_html__( 'Venues', 'artsdata-shortcodes' ) . '</h4>';
      foreach ($venues as $venue) {
        if (isset($venue["location"][0]["rdfsLabelEn"])) {
          $html .= '<div class="artsdata-venue-wrapper">';
          $html .= '<div class="artsdata-place">';
             	  $html .= '<div class="artsdata-place-map-wrapper">';
             	  	//
             	  	// FOREACH required so that this DIV's ID can be auto-incremented as a unique ID for each venue (i.e. map1, map2, map3)
             	  	// The same will need to be done in the plugin's JS file for outputting the coordinates unique to each ID
             	  	// The nested DIV .artsdata-map-image will need to be conditionally visible if no map exists
             	  	//
             	  $html .= '<div id="' . $venue["location"][0]["id"] . '" class="artsdata-place-map-entry"><div class="artsdata-map-image" style="background-image: url(' . plugin_dir_url( __FILE__ ) . 'images/bkg-grid.svg)"><p class="artsdata-map-text">' .  esc_html__( 'No map data available.', 'artsdata-shortcodes' ) . '</p></div></div>';

             	  $html .= '</div>';
             	  $html .= '<div class="artsdata-place-entry">';
    	         	  $html .= '<div class="artsdata-place-details">';
    		            $single_place = $venue["location"][0] ;
    		            $html .= '<p class="artsdata-place-type">' . concatMultiLingualList($single_place["additionalType"]) . '</p>' ;
    		            $html .= '<h5 class="artsdata-place-name" ' . dataMaintainer($rankedProperties, "location") . '>' . languageService($single_place, 'rdfsLabel') . '</h5>' ;
    		            $html .= '<p class="artsdata-place-address">' . (isset($single_place["address"]["@value"]) ? $single_place["address"]["@value"] : '') . '</p>' ;
    		            if (isset($single_place["id"])) { $html .= '<p class="artsdata-place-wikidata-id">' . esc_html__( 'Wikidata ID:', 'artsdata-shortcodes' )  . ' <a href="' . $single_place["id"] . '">' . trim($single_place["id"], "http://www.wikidata.org/entity/")  . '</a></p>'; }
    		          $html .= '</div>';
    		          $html .= '<div class="artsdata-place-thumbnail">';

					  	//
					    // venue photo anchor URL should pull in the source wiki page URL
					    //
    		         if ( isset($single_place["image"])) { $html .= '<div class="artsdata-place-image"><a href="' . $single_place["creditedTo"]["id"] . '" target="_blank" title="' .  esc_html__( 'Image from Wikimedia Commons. Click on the image to view photo credits.', 'artsdata-shortcodes' ) . '"><img src="' . $single_place["image"] . '" class="venue-photo" alt="' .  esc_html__( 'Image of', 'artsdata-shortcodes' ) . ' ' . languageService($single_place, 'rdfsLabel') . '"></a></div>';}
                 else {$html .= '<div class="artsdata-place-icon"><a href="https://capacoa.ca/en/member/membership-faq/#image" target="_blank"><img src="' . plugin_dir_url( __FILE__ ) . 'images/icon-building.svg)" class="placeholder" title="No free-use image could be found in Wikidata or Wikimedia Commons for this venue" /></a></div>' ;}


    		          $html .= '</div>';
    		            if (gettype($single_place["containsPlace"]) == 'array' ) {  // TODO: Frame containsPlace to be an array
    		              if (isset($single_place["containsPlace"][0]["rdfsLabelEn"])) { // skip venues without names (TODO: add fr)
    				          $html .= '<div class="artsdata-place child">';
    			                foreach ($single_place["containsPlace"] as $room) {
    							$html .= '<div class="artsdata-place-entry child">';
    				              $html .= '<div class="artsdata-place-details child">';
    			                    $html .= '<p class="artsdata-place-type child">' .  concatMultiLingualList($room["additionalType"]) . '</p>' ;
    			                    $html .= '<h6 class="artsdata-place-name">' . languageService($room, 'rdfsLabel') . '</h6>';
    			                    if (isset($room["id"])) { $html .= '<p class="artsdata-place-wikidata-id">' . esc_html__( 'Wikidata ID:', 'artsdata-shortcodes' )  . ' <a href="' . $room["id"] . '">' . trim($room["id"], "http://www.wikidata.org/entity") . '</a></p>'; }
    			                    $html .= '</div>';
    					            $html .= '<div class="artsdata-place-thumbnail child">';
    					              //
    					              // IF statement needed to display this div only if a thumbnail exists
    					              //
    					              if (isset($room["image"])) {$html .= '<div class="artsdata-place-image child" style="background-image: ' . $room["image"] . '"></div>' ;} //need to have it pulled from AD
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
      $html .= '<div class="artsdata-event-photo"><a href="' . safeUrl($event["url"]) . '"><img src="' . safeUrl($event["image"]) . '"></a></div>';
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

    foreach ($venues as $venue) {
      $pattern = '{Point\((.+) (.+)\)}';
      preg_match($pattern,  $venue["location"][0]["geoCoordinates"]["@value"], $matches);
      $html .= "
        <script>
          var map1 = L.map('" . $venue["location"][0]["id"] . "', {
            center: [" . $matches[2] . ", " .  $matches[1] . "],
            zoom: 15,
            zoomControl: false,
            fullscreenControl: true,
            attributionControl: false
          });
          L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '&copy;  <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors'
          }).addTo(map1);
          L.marker([" . $matches[2] . ", " .  $matches[1] . "]).addTo(map1).setOpacity(0.85);
        </script>";
    }

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
      if (isset($entity[$prop . $lang])) { return $entity[$prop . $lang];}
      if (isset($entity[0][$prop . $lang])) { return $entity[0][$prop . $lang];}
      if (isset($entity[$prop . "Pref"])) { return $entity[$prop . "Pref"]; }
      if (isset($entity[0][$prop . "Pref"])) { return $entity[0][$prop . "Pref"]; }
      if (isset($entity[$prop . "Fr"])) { return $entity[$prop . "Fr"]; }
      if (isset($entity[$prop . "En"])) { return $entity[$prop . "En"]; }
  }

  function checkUrl($url) {
    if ($url == "" ) {
      return '' ;
    }
    $url =  safeUrl($url);
    if (strpos($url,  "http") !== 0 ) {
      $url = 'http://' . $url ;
    }
    return $url ;
  }

  function generalType($types, $detectionStr) {
    $str = '' ;
    $lang = getLanguage() ;
    foreach ($types as $type) {
      if (isset($type['id']) && strpos($type['id'],  $detectionStr) !== false ) {
        if ($type['label' . $lang]) {$str .= "<li>" . $type['label' . $lang] . "</li>" ;}
        elseif ($type['labelPref']) {$str .= "<li>" .$type['labelPref'] . "</li>" ;}
        elseif ($type['labelEn']) {$str .= "<li>" .$type['labelEn'] . "</li>" ;}
        elseif ($type['labelFr']) {$str .= "<li>" .$type['labelFr'] . "</li>" ;}
        elseif ($type['label']) {$str .= "<li>" .$type['label'] . "</li>" ;}
      }
    }
    return $str ;
  }

  function multiLingualList($list) {
    $str = '' ;
    $lang = getLanguage() ;
    foreach ($list as $listItem) {
     $str .= "<li>" .  languageService($listItem,'rdfsLabel') . "</li>" ;
    }
    return $str ;
  }

  function concatMultiLingualList($list) {
    $str = '' ;
    $lang = getLanguage() ;

    if ( isset($list['id']) ) { // not an array of entities
      $str .= languageService($list, 'rdfsLabel') ;
    } else {
      $str .= languageService($list[0], 'rdfsLabel') ;
      foreach (array_slice($list, 1) as $listItem) {
        $str .= ", " . languageService($listItem, 'rdfsLabel') ;
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
