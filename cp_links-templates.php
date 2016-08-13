<?php

function cp_links_classes($classes){
    echo cp_links_get_classes($classes);
}

function cp_links_get_classes($classes){
    if (empty($classes)) return;
    return' class="'.implode(' ',$classes).'"';
}

function cp_links_get_links_ids_for_post($post_id = null){
    global $post;
    if (!$post_id) $post_id = $post->ID;
    return get_post_meta( $post_id, '_custom_post_links_ids', true );
}

function cp_links_get_for_post($post_id = null,$orderby= null){
    global $post;
    if (!$post_id) $post_id = $post->ID;
    if (!$post_id) return;
    
    $cp_links_ids = cp_links_get_links_ids_for_post($post_id);
    if (!$cp_links_ids) return;
    
    $orderby_allowed = array('name','custom');
    if ( $orderby && !in_array($orderby,$orderby_allowed) ) $orderby = null;
    if (!$orderby) $orderby = cp_links()->get_options('links_orderby');

    $args = array( 
        'include' => implode(',',$cp_links_ids)
    );
    
    if ( $orderby && ($orderby!='custom') ){
        $args['orderby'] = $orderby;
        $args['order'] = 'ASC';
    }

    $links = get_bookmarks( $args );

    if ($orderby == 'custom'){
        $links = cp_links_sort_using_ids_array($links,$cp_links_ids);
    }

    return $links;
    
}

/*
 * the_content filter to append custom post links to the post content
 */
function cp_links_output_links( $content ){
    global $post;
    if ( !in_array( $post->post_type, cp_links()->allowed_post_types() ) ) return $content;
    
    $option = cp_links()->get_options('display_links');
    $links = cp_links_output_for_post($post->ID);
    
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
function cp_links_output_single_link($link){
    //get domain url
    $domain = cp_links_get_domain($link->link_url);
    
    $link_classes_arr = array('cp-links');
    $link_classes_arr = apply_filters('cp_links_single_link_classes',$link_classes_arr,$link);
    $link_classes = cp_links_get_classes($link_classes_arr);
    $link_target_str=null;
    
    if($link->link_target) {
        if ( cp_links()->get_options('ignore_target_local') && cp_links_is_local_url($link->link_url) ){
            //nix
        }else{
            $link_target_str = sprintf(' target="%s"',$link->link_target);
        }
        
    }

    $output = sprintf('<li id="%1s" %2s data-cp-link-domain="%3s"><a href="%4$s"%5$s>%6$s</a></li>','cp-link-'.$link->link_id,$link_classes,$domain,$link->link_url,$link_target_str,$link->link_name);
    return apply_filters('cp_links_output_single_link',$output,$link);
    
}

/*
 * Generate the output for links on this post
 */
function cp_links_output_for_post($post_id = null){
    global $post;
    
    if (!$post_id) $post_id = $post->ID;
    if (!$post_id) return false;

    $links_html = null;
    $title_el = null;
    $blogroll = array();
    
    if ( $cp_links = cp_links_get_for_post($post_id) ){
        
        foreach ((array)$cp_links as $link){
            $blogroll[] = cp_links_output_single_link($link);
        }
        
        $blogroll_str = implode("\n",$blogroll);
        
        if ($blogroll_str) {

            $links_html = sprintf('<div class="custom-post-links">%1s<ul>%2s</ul>',$title_el,$blogroll_str);
        }

    }
    
    return $links_html;

}