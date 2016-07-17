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
    return get_post_meta( $post_id, '_custom_post_links', true );
}

function cp_links_get_for_post($post_id = null,$order='user'){
    global $post;
    if (!$post_id) $post_id = $post->ID;
    
    $cp_links_ids = cp_links_get_links_ids_for_post($post_id);
    
    $args = array( 
        'include' => implode(',',$cp_links_ids)
    );

    $links = get_bookmarks( $args );
    
    if ($order == 'user'){
        $links = cp_links_sort_using_ids_array($links,$cp_links_ids);
    }
    

    return $links;
    
}

/*
 * the_content filter to append custom post links to the post content
 */
function cp_links_output_links( $content ){
  global $post;
  if ( in_array( $post->post_type, cp_links()->allowed_post_types() ) ){
    $show = get_post_meta($post->ID, '_custom_post_links_output', true);
    if (!empty($show) && $show != '_none_'){
      $links = cp_links_get_links_output($post->ID);
      if ($links){
        if ($show == 'above'){
          $content = $links."\n".$content;
        }
        else if ($show == 'below'){
          $content.= "\n".$links;
        }
      }
    }
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
    
    $output = sprintf('<li id="%1s" %2s data-cp-link-domain="%3s"><a href="%4$s">%5$s</a></li>','cp-link-'.$link->link_id,$link_classes,$domain,$link->link_url,$link->link_name);
    return apply_filters('cp_links_output_single_link',$output,$link);
    
}

/*
 * Generate the output for links on this post
 */
function cp_links_get_links_output($post_id){
    
    $links_html = null;
    $title_el = null;
    $blogroll = null;
    
    if ( $cp_links = cp_links_get_for_post($post_id) ){
        
        foreach ((array)$cp_links as $link){
            $blogroll .= cp_links_output_single_link($link);
        }
        
        if ($blogroll) {
            
            //title
            if ( $title = get_post_meta( $post_id, '_custom_post_links_title', true) ){
                $title_el = sprintf('<h3>%s</h3>',$title);
            }

            $links_html = sprintf('<div class="custom-post-links">%1s<ul>%2s</ul>',$title_el,$blogroll);
        }

    }
    
    return $links_html;

}