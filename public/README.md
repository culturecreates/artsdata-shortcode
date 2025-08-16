Public folder required for Artsdata API
--------------------------

This folder **must** remain public and accessible by https protocol.

The `artsdata-shortcodes.php` code passes the Github raw urls of these SPARQLs and JSON-LD Frames to the Artsdata Query API. Upon receiving them as parameters, the Arsdata API  calls the urls and uses them to query Artsdata.  This is to avoid having very long parameters in the Artsdata Query API. The PHP code uses the Github raw url format. Both Github and Artsdata briefly cache these GET requests for increased performance. This approach is often called URL Indirection or Reference Passing.

CAUTION: You may change the SPARQL to fit the needs of the shortcode, but keep in mind that Github contains the prodution files used by the shortcode running in the CAPACOA Wordpress. If you make a breaking change to these files you should add a **new** SPARQL or Frame file (and update the PHP code) so that the current shortcode in production continues to work and you will have time to test and install the new plugin without creating any down time.
