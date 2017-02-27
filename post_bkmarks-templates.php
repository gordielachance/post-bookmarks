<?php

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

function post_bkmarks_get_links_ids_for_post($post_id = null){
    global $post;
    if (!$post_id) $post_id = $post->ID;
    return get_post_meta( $post_id, '_post_bkmarks_ids', true );
}

function post_bkmarks_get_tab_links($tab = null){
    global $post;
    $links = array();

    //current tab
    if (!$tab) $tab = post_bkmarks()->links_tab;

    $args = array();
    $args = apply_filters('post_bkmarks_tab_links_args',$args,$tab,$post->ID);

    if ($tab == 'attached'){
        $args['post_bkmarks_for_post'] = $post->ID;
        $args['orderby'] = post_bkmarks()->get_options('links_orderby');
    }
    
    //search filter
    if ( $search = strtolower(post_bkmarks()->filter_links_text) ){
        $args['search'] = $search;
    }
    
    $links = get_bookmarks( $args );
    $links = apply_filters('post_bkmarks_get_tab_links',$links,$tab,$post->ID);

    //sanitize links
    foreach ($links as $key=>$link){
        $links[$key] = (object)post_bkmarks()->sanitize_link($link);
    }

    //if this is not the attached tab, remove the attached links (check by link ID and link URL)
    if ($tab != 'attached'){
        
        //attached links
        $attached_ids = array();
        $attached_urls = array();
        if ( $attached_links = get_bookmarks( array('post_bkmarks_for_post' => $post->ID) ) ){
            foreach ($attached_links as $attached_link){
                $attached_ids[] = $attached_link->link_id;
                $attached_urls[] = $attached_link->link_url;
            }
        }

        $links = array_filter(
            $links,
            function ($link) use ($attached_ids,$attached_urls) {
                return ( (!in_array($link->link_id,$attached_ids)) && (!in_array($link->link_url,$attached_urls)) );
            }
        );

    }

    return $links;
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
    
    $args = array(
        'post_bkmarks_for_post'=>$post_id,
        'orderby' => post_bkmarks()->get_options('links_orderby')
    );
    
    if ( $post_bkmarks = get_bookmarks($args) ){

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