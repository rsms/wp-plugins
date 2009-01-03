<?
/*
 * Plugin Name: Syntax Highlight
 * Version:     0.1.0
 * Plugin URI:  http://trac.hunch.se/rasmus/wiki/wp-plugins#SyntaxHighlight
 * Author:      Rasmus Andersson
 * Author URI:  http://hunch.se/
 * Description: Highlights structured data using <a href="http://pygments.org/">Pygments</a>.
*/

/**
 * @param int cache_ttl  Set to 0 to disable cache
 */
function &hu_syntax_highlight($content, $lang, $cache_ttl=30) {
  static $pat = '/usr/bin/pygmentize -l \'%s\' -f html -P cssclass=sourcecode';
  $cache_key = '';
  if(isset($_GET['preview']))
    $cache_ttl = 0;
  if($cache_ttl) {
    $cache_key = $lang . md5($content);
    $cached = wp_cache_get($cache_key, Request::$host);
    if($cached)
      return $cached;
  }
  $pipedesc = array(
     0 => array('pipe', 'r'), # in
     1 => array('pipe', 'w'), # out
     2 => array('pipe', 'w')  # err
  );
  $cmd = sprintf($pat, escapeshellcmd($lang));
  $ps = proc_open($cmd, $pipedesc, $pipes, '/tmp');
  if(!is_resource($ps))
    return 'ERROR: hu_syntax_highlight failed: is_resource(ps) == false';
  fwrite($pipes[0], $content);
  fclose($pipes[0]);
  $new_content = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  $errors = stream_get_contents($pipes[2]);
  fclose($pipes[2]);
  $return_value = proc_close($ps);
  if($return_value == 0) {
    if($cache_ttl)
      wp_cache_set($cache_key, $new_content, Request::$host, $cache_ttl);
    return $new_content;
  }
  else
    return nl2br(htmlentities($errors));
}

function &hu_syntax_highlight_filter($content='') {
  $start = 0;
  while(1) {
    if( ($start = strpos($content, ($start == 0 ? '{{{' : "\n{{{"), $start)) !== false) {
      if( ($end = strpos($content, "}}}", $start+5)) !== false) {
        $code = trim(substr($content, $start+4, $end-($start+4)));
        $lang = null;
        if( substr($code, 0, 2) == '#!' ) {
          $nl = strpos($code, "\n", 2);
          $lang = trim(substr($code, 2, $nl-2));
          $code = hu_syntax_highlight(ltrim(substr($code, $nl+1), "\r"), $lang);
        }
        else {
          $code = '<div class="sourcecode"><pre>'.htmlentities($code).'</pre></div>';
        }
        $code_wrapped = base64_encode($code);
        $content = substr($content, 0, $start)
          . sprintf("{{{!%010d%s",strlen($code_wrapped), $code_wrapped)
          . substr($content, $end+3);
        $start = $end;
      }
      else {
        break;
      }
    }
    else {
      break;
    }
  }
  return $content;
}

function &hu_syntax_highlight_filter_unwrap($content='') {
  $start = 0;
  $content_len = strlen($content);
  while(1) {
    if( ($start = strpos($content, "{{{!", $start)) !== false) {
      $len = intval(substr($content, $start+4, 10));
      $content = substr($content, 0, $start)
        . base64_decode(substr($content, $start+14, $len))
        . substr($content, $start+14+$len);
      $start = $start + 14;
      if($start > $content_len)
        break;
    }
    else {
      break;
    }
  }
  return $content;
}

add_filter('the_content', 'hu_syntax_highlight_filter', 0, 1);
add_filter('the_content', 'hu_syntax_highlight_filter_unwrap', 999, 1);

?>
