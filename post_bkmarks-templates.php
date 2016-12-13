<?php

function post_bkmarks_classes($classes){
    echo post_bkmarks_get_classes_attr($classes);
}

function post_bkmarks_get_classes_attr($classes){
    if (empty($classes)) return;
    return' class="'.esc_attr( implode(' ',$classes) ).'"';
}

function post_bkmarks_get_links_ids_for_post($post_id = null){
    global $post;
    if (!$post_id) $post_id = $post->ID;
    return get_post_meta( $post_id, '_custom_post_links_ids', true );
}

function post_bkmarks_get_for_post($post_id = null,$args= array()){
    global $post;
    if (!$post_id) $post_id = $post->ID;
    if (!$post_id) return;
    
    $post_links = array();
    $defaults = array(
        'orderby'   => post_bkmarks()->get_options('links_orderby'),
        'order'     => 'ASC'
    );
    
    $orderby_allowed = array('name','custom');
    $orderby = ( isset($args['orderby']) && in_array($args['orderby'],$orderby_allowed) ) ? $args['orderby'] : null;
    
    $args = wp_parse_args($args,$defaults);
    
    if ($link_ids = post_bkmarks_get_links_ids_for_post($post_id)){

        $args['include'] = implode(',',$link_ids);
        $links = get_bookmarks( $args );

        //We could use the 'include' arg with get_bookmarks(); but it override some other ones (eg.category).  So let's rather filter links now.
        foreach($links as $link){
            if ( !in_array($link->link_id,$link_ids) ) continue;
            $post_links[] = $link;
            
        }

        if ($orderby == 'custom'){
            $links = post_bkmarks_sort_using_ids_array($post_links,$link_ids); //TO FIX should be a filter ?
        }
    }
    
    //allow plugins to filter this
    $post_links = apply_filters('post_bkmarks_get_for_post_pre',$post_links,$post_id,$orderby);
    
    //sanitize links
    foreach ((array)$post_links as $key=>$link){
        $post_links[$key] = (object)post_bkmarks()->sanitize_link($link);
    }

    return $post_links;
    
}

/*
 * the_content filter to append custom post links to the post content
 */
function post_bkmarks_output_links( $content ){
    global $post;
    if ( !in_array( $post->post_type, post_bkmarks()->allowed_post_types() ) ) return $content;
    
    $option = post_bkmarks()->get_options('display_links');
    $links = post_bkmarks_output_for_post($post->ID);
    
    switch($option){
        case 'before':
            $content = $links."\n".$content;
        break;
        case 'after':
            $content.= "\n".$links;
        break;
    }

    return $content;
}

/*
 * Template a single link
 */

function post_bkmarks_get_favicon($url){

    $favicon = null;
    
    //get domain url
    if ( $domain = post_bkmarks_get_url_domain($url) && (post_bkmarks()->get_options('get_favicon')=='on') ){
        //favicon
        $favicon = sprintf('https://www.google.com/s2/favicons?domain=%s',$url);
        $favicon_style = sprintf(' style="background-image:url(\'%s\')"',$favicon);
        $favicon = sprintf('<span class="post-bkmarks-favicon" %s></span>',$favicon_style);
    }
    
    return $favicon;
}

function post_bkmarks_output_single_link($link){
    
    $favicon_style = null;
    $domain = post_bkmarks_get_url_domain($link->link_url);

    $link_classes_arr = array('post-bkmarks');
    $link_classes_arr = apply_filters('post_bkmarks_single_link_classes',$link_classes_arr,$link);
    $link_classes = post_bkmarks_get_classes_attr($link_classes_arr);
    $link_target_str=null;
    
    $favicon = post_bkmarks_get_favicon($link->link_url);

    if($link->link_target) {
        if ( (post_bkmarks()->get_options('ignore_target_local')=='on') && post_bkmarks_is_local_url($link->link_url) ){
            //nix
        }else{
            $link_target_str = sprintf(' target="%s"',esc_attr($link->link_target) );
        }
        
    }

    $output = sprintf('<li id="%1s" %2s data-cp-link-domain="%3s"><i class="fa fa-link" aria-hidden="true"></i><a href="%4$s"%5$s>%6$s%7$s</a></li>',
                      'cp-link-'.$link->link_id,
                      $link_classes,
                      esc_attr($domain),
                      esc_url($link->link_url),
                      $link_target_str,
                      $favicon,
                      esc_html($link->link_name)
     );
    return apply_filters('post_bkmarks_output_single_link',$output,$link);
    
}

/*
 * Generate the output for links on this post
 * Would be better to use core function '_walk_bookmarks( $post_bkmarks )' here, but it is too limited.
 */
function post_bkmarks_output_for_post($post_id = null){
    global $post;
    
    if (!$post_id) $post_id = $post->ID;
    if (!$post_id) return false;

    $links_html = null;
    $title_el = null;
    $blogroll = array();
    
    if ( $post_bkmarks = post_bkmarks_get_for_post($post_id) ){

        foreach ((array)$post_bkmarks as $link){
            $blogroll[] = post_bkmarks_output_single_link($link);
            
        }

        $blogroll_str = implode("\n",$blogroll);

        
        if ($blogroll_str) {

            $links_html = sprintf('<div class="post-bookmarks">%1s<ul>%2s</ul>',$title_el,$blogroll_str);
        }

    }
    
    return $links_html;

}