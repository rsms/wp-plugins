<?php
/*
Plugin Name: Write in Monospace
Plugin URI: http://trac.hunch.se/rasmus/wp-plugins/#WriteInMonospace
Description: Sets the style of the text area to use monospace font for writing new posts.
Author: Rasmus Andersson
Version: 0.1
Author URI: http://hunch.se/
*/

function hu_wim_css() {
  echo '<style type="text/css">/* "Write in Monospace" plugin */
  #editorcontainer > textarea {
    font-family: monospace;
    font-size: 9pt;
  }
  </style>';
}

add_action('admin_head', 'hu_wim_css');

?>
