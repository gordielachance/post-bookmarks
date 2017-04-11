<?php

/**
 * Get a value in a multidimensional array
 * http://stackoverflow.com/questions/1677099/how-to-use-a-string-as-an-array-index-path-to-retrieve-a-value
 * @param type $keys
 * @param type $array
 * @return type
 */
function post_bkmarks_get_array_value($keys = null, $array){
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

function post_bkmarks_maybe_add_url_protocol($url){

    if ( $url && (!$protocol = parse_url($url, PHP_URL_SCHEME) ) ){
        $url = 'http://' . $url; //add default protocol
    }
    
    return $url;

}

function post_bkmarks_get_name_from_url($url){
    
    $url = post_bkmarks_maybe_add_url_protocol($url);
    if (filter_var($url, FILTER_VALIDATE_URL) === false) return;
    
    $name = null;

    //try to get page title
    if ( !$name = post_bkmarks_get_url_title($url) ){
        $name = post_bkmarks_get_url_domain($url);
    }

    return $name;
    
}

function post_bkmarks_get_url_domain($url){
    
    $url = post_bkmarks_maybe_add_url_protocol($url);
    if (filter_var($url, FILTER_VALIDATE_URL) === false) return;
    
      $pieces = parse_url($url);
      $domain = isset($pieces['host']) ? $pieces['host'] : '';
      if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
        return $regs['domain'];
      }
      return false;
}

function post_bkmarks_get_url_title($url){
    
    $url = post_bkmarks_maybe_add_url_protocol($url);
    if (filter_var($url, FILTER_VALIDATE_URL) === false) return;

    $response = wp_remote_get( $url );
    
    if ( $response && !is_wp_error($response) ){
        if ( $body = wp_remote_retrieve_body($response) ){
            $str = trim(preg_replace('/\s+/', ' ', $body)); // supports line breaks inside <title>
            preg_match("/\<title\>(.*)\<\/title\>/i",$body,$title); // ignore case
            return $title[1];
        }
    }

}

function post_bkmarks_get_existing_link_id($link_url,$link_name = null){
    global $wpdb;

    if ($link_name){
        $query = $wpdb->prepare( "SELECT * FROM $wpdb->links WHERE `link_url` = %s AND `link_name` = %s",esc_url_raw($link_url),sanitize_text_field($link_name) );
    }else{
        $query = $wpdb->prepare( "SELECT * FROM $wpdb->links WHERE `link_url` = %s",esc_url_raw($link_url) );
    }
    
    $r = $wpdb->get_row( $query );
    
    if ($r){
        post_bkmarks()->debug_log(array('link_id'=>$r->link_id,'link_url'=>$link_url,'link_name'=>$link_name),"post_bkmarks_get_existing_link_id()");
        return $r->link_id;
    }

}

function post_bkmarks_sort_using_ids_array($links,$sort_ids){
    
    if (!$links) return $links;
    $ordered = array();

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

function post_bkmarks_get_metas( $key, $fields = null, $type = null, $status = null ) {

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

function post_bkmarks_is_local_url($url){

    $is_local = strpos($url, home_url());

    return (bool)($is_local !== false);

    if ($is_local !== false) {
        return true;
    }

}

function post_bkmarks_sort_links_by_order($a, $b) {
    if ( !isset($a['link_order']) || !isset($b['link_order']) ) return 0;
    if((int)$a['link_order'] == (int)$b['link_order']){ return 0 ; }
    return ($a['link_order'] < $b['link_order']) ? -1 : 1;
}