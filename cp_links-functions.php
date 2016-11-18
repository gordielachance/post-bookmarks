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

function cp_links_get_name_from_url($url){
    
    $name = null;

    //try to get page title
    if ( !$name = cp_links_get_url_title($url) ){
        $name = cp_links_get_url_domain($url);
    }

    return $name;
    
}

function cp_links_get_url_domain($url){
  $pieces = parse_url($url);
  $domain = isset($pieces['host']) ? $pieces['host'] : '';
  if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
    return $regs['domain'];
  }
  return false;
}

function cp_links_get_url_title($url){

    $response = wp_remote_get( $url );
    
    if ( $response && !is_wp_error($response) ){
        if ( $body = wp_remote_retrieve_body($response) ){
            $str = trim(preg_replace('/\s+/', ' ', $body)); // supports line breaks inside <title>
            preg_match("/\<title\>(.*)\<\/title\>/i",$body,$title); // ignore case
            return $title[1];
        }
    }

}


function cp_links_get_existing_link_id($link_url,$link_name){

    global $wpdb;
    
    $query = sprintf('SELECT * FROM %s WHERE link_url = "%s" AND link_name = "%s"',$wpdb->links,$link_url,$link_name);
    
    $r = $wpdb->get_row( $query );
    
    if ($r){
        return $r->link_id;
    }

}

function cp_links_sort_using_ids_array($links,$sort_ids){
    
    if (!$links) return $links;

    foreach ((array)$sort_ids as $id){ //correct order
        //select link
        foreach ((array)$links as $key=>$link){
            if ($link->link_id != $id) continue;
            $ordered[] = $link;
            unset($links[$key]);
        }
        
    }
    return $ordered;
    
}

function cp_links_get_metas( $key, $fields = null, $type = null, $status = null ) {

    global $wpdb;
    
    if (!$fields){
        $fields_str = '*';
    }else{
        $fields = (array)$fields; //force array
        $fields_str = implode(', ',$fields);
    }
    

    
    $query = array(
        $wpdb->prepare( 'SELECT %1$s FROM %2$s m LEFT JOIN %3$s p ON p.ID = post_id WHERE m.meta_key = "%4$s"',$fields_str,$wpdb->postmeta,$wpdb->posts,$key)
    );
                       
    
    if ($type){
        $query[] = $wpdb->prepare( "AND p.post_type = '%s'",$type );
    }
    
    if ($status){
        $query[] = $wpdb->prepare( "AND p.post_status = '%s'" ,$status );
    }
    
    if ( count($fields) == 1 ){
        $r = $wpdb->get_col( implode(" ",$query) );
    }else{
        $r = $wpdb->get_results( implode(" ",$query) );
    }

    

    return $r;
}

function cp_links_is_local_url($url){

    $is_local = strpos($url, home_url());

    return (bool)($is_local !== false);

    if ($is_local !== false) {
        return true;
    }


}