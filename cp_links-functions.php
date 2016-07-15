<?php

/**
 * Get a value in a multidimensional array
 * http://stackoverflow.com/questions/1677099/how-to-use-a-string-as-an-array-index-path-to-retrieve-a-value
 * @param type $keys
 * @param type $array
 * @return type
 */
function cp_links_get_array_value($keys = null, $array){
    if (!$keys) return $array;
    
    $keys = (array)$keys;
    $first_key = $keys[0];
    if(count($keys) > 1) {
        if ( isset($array[$keys[0]]) ){
            return pinim_get_array_value(array_slice($keys, 1), $array[$keys[0]]);
        }
    }elseif (isset($array[$first_key])){
        return $array[$first_key];
    }
    
    return false;
}

function cp_links_get_domain($url){
  $pieces = parse_url($url);
  $domain = isset($pieces['host']) ? $pieces['host'] : '';
  if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
    return $regs['domain'];
  }
  return false;
}

function cp_links_get_existing_link_id($link_url,$link_name){
    
    //TO FIX TO CHECK, not working yet
    
    //TO FIX sanitize url and name ?
    
    $args = array( 'hide_invisible' => 0, 'hide_empty' => 0, 'category' => cp_links()->get_options('links_category') );
    $all_links = get_bookmarks( $args );
    
    foreach($all_links as $link){
        if ( ($link->link_url == $link_url) && ($link->link_name == $link_name) ) return $link->link_id;
    }

}

function cp_links_sort_using_ids_array($links,$sort_ids){
    //TO FIX to write
    return $links;
}
