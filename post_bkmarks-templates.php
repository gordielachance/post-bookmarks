<?php

/*
 * Get the links attached to a post.
 * $args should be an array with the same parameters you would set while using the native get_bookmarks() function.
 * https://codex.wordpress.org/Function_Reference/get_bookmarks
 */

function post_bkmarks_get_post_links($post_id = null, $args = null){
    if (!$post_id) $post_id = $post->ID;
    if (!$post_id) return false;
    
    $post_args = array(
        'post_bkmarks_for_post'=>$post_id,
        'orderby' => post_bkmarks()->get_options('links_orderby')
    );
    
    if ($args){
        $post_args = wp_parse_args($post_args,$args); //priority to the post args
    }
    
    return get_bookmarks($post_args);
    
}

/*
 * Get the list of link IDs attached to a post.
 */

function post_bkmarks_get_links_ids_for_post($post_id = null){
    global $post;
    if (!$post_id) $post_id = $post->ID;
    if ( $meta = get_post_meta( $post_id, Post_Bookmarks::$link_ids_metakey, true ) ){
        return array_unique((array)$meta);
    }
    return false;
}


function post_bkmarks_classes_attr($classes){
    echo post_bkmarks_get_classes_attr($classes);
}

function post_bkmarks_get_classes_attr($classes){
    if (empty($classes)) return;

    foreach ((array)$classes as $key=>$class){
        $classes[$key] = sanitize_title($class);
    }
    
    return' class="'.esc_attr( implode(' ',$classes) ).'"';
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

/*
 * Generate the list of links for this post
 */
function post_bkmarks_links_list($post_id = null, $args = null){
    global $post;

    if (!$post_id) $post_id = $post->ID;
    if (!$post_id) return false;

    $links_html = null;
    $title_el = null;
    $blogroll = array();

    if ( $post_bkmarks = post_bkmarks_get_post_links($post_id,$args) ){

        foreach ((array)$post_bkmarks as $link){
            
            $link_html = post_bkmarks_get_single_link_html($link);
            $link_html = sprintf('<li><i class="fa fa-link" aria-hidden="true"></i>%s</li>',$link_html);
            $blogroll[] = $link_html;
            
        }

        $blogroll_str = implode("\n",$blogroll);

        
        if ($blogroll_str) {

            $links_html = sprintf('<div class="post-bookmarks-output-list" data-post-bkmarks-post-id="%s">%1s<ul class="post-bookmarks-list">%2s</ul>',$post_id,$title_el,$blogroll_str);
        }

    }
    
    return $links_html;

}

/*
 * Generate the single link output
 */
function post_bkmarks_get_single_link_html($link){
    
    $link_classes_arr = array(
        'post-bkmark',
        'post-bkmark-' . $link->link_id,
    );
    $link_classes_arr = apply_filters('post_bkmarks_single_link_classes',$link_classes_arr,$link);
    $link_classes = post_bkmarks_get_classes_attr($link_classes_arr);
    
    $link_target_str=null;
    $favicon = post_bkmarks_get_favicon($link->link_url);
    $domain = post_bkmarks_get_url_domain($link->link_url);
    
    if($link->link_target) {
        if ( (post_bkmarks()->get_options('ignore_target_local')=='on') && post_bkmarks_is_local_url($link->link_url) ){
            //nix
        }else{
            $link_target_str = sprintf('target="%s"',esc_attr($link->link_target) );
        }
        
    }
    
    $link_html = sprintf('<a %s href="%s" %s data-cp-link-domain="%s">%s%s</a>',
        $link_classes,
        esc_url($link->link_url),
        $link_target_str,
        esc_attr($domain),
        $favicon,
        esc_html($link->link_name)
     );
    
    return apply_filters('post_bkmarks_single_link_html',$link_html,$link);
    
}

function post_bkmarks_get_tab_links($tab = null){
    global $post;
    $links = array();

    //current tab
    if (!$tab) $tab = post_bkmarks()->links_tab;
    
    $args = array();
    
    //search filter
    if ( $search = strtolower(post_bkmarks()->filter_links_text) ){
        $args['search'] = $search;
    }
    
    $args = apply_filters('post_bkmarks_tab_links_args',$args,$tab,$post->ID);

    //attached to the post
    if ($tab == 'library'){
        $links = get_bookmarks( $args );
    }else{
        $links = post_bkmarks_get_post_links($post->ID,$args);
    }

    $links = apply_filters('post_bkmarks_get_tab_links',$links,$tab,$post->ID);

    //sanitize links
    foreach ($links as $key=>$link){
        $links[$key] = (object)post_bkmarks()->sanitize_link($link);
    }

    return $links;
}