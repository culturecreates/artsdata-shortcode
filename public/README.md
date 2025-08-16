Public folder required for Artsdata API
--------------------------

This folder **must** remain public and accessible by https protocol.

The `artsdata-shortcodes.php` code passes the Github raw url references to the master branch (https://raw.githubusercontent.com/culturecreates/artsdata-shortcode/refs/heads/master/public/...) of these SPARQLs and JSON-LD Frames to the Artsdata Query API. The Arsdata Query API fetches the urls to retrieve the SPARQL and Frame needed to complete the Artsdata query.  This avoids URL length limitations in GET requests. Both Github and Artsdata briefly cache these GET requests for increased performance. This pattern is often called URL Indirection or Reference Passing.

CAUTION: You may change the SPARQL to fit the needs of the shortcode, but keep in mind that Github serves the prodution files from the main branch in real time . If you need to commit a breaking change to these files you should create a **new** SPARQL file and Frame file (and update the PHP code) so that the current shortcode in production continues to work and you will have time to test and install the new plugin without creating any down time. Alternate techniques are to use a separate branch or to include the files with the plugin code on the Wordpress server and make them accessible to the public via https.