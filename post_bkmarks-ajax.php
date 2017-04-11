<?php

function post_bkmarks_ajax_refresh_url(){
    $result = array(
        'message'   => null,
        'success'   => false,
        'input'     => $_POST
    );

    $url = ( isset($_POST['url']) ) ? $_POST['url'] : null;
    $url = post_bkmarks_maybe_add_url_protocol($url);
    
    if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
        $result['message'] = $url . 'is not a valid url';
    }else{
        $result['success'] = true;
        
        //guess the link name if none provided
        if ( $new_name = post_bkmarks_get_name_from_url($url) ){
            $result['name'] = $new_name;
        }
        
        //favicon
        $result['favicon'] = post_bkmarks_get_favicon($url);
        
    }

    header('Content-type: application/json');
    echo json_encode($result);
    die();
}

function post_bkmarks_ajax_row_action(){
    $result = array(
        'message'   => null,
        'success'   => false,
        'input'     => $_POST
    );
    
    $result['action']   = $action = ( isset($_POST['row_action']) ) ? $_POST['row_action'] : null;
    $result['post_id']  = $post_id = ( isset($_POST['post_id']) ) ? $_POST['post_id'] : null;
    $result['link_input']  = $link = ( isset($_POST['ajax_link']) ) ? $_POST['ajax_link'] : null;

    if ($action && $post_id && $link){
        $links = (array)$link;
        if ( $success = post_bkmarks()->do_single_post_bookmark_action($post_id,$link,$action) ){
            $result['success'] = true;
            
            switch($action){
                case 'save':
                    $link_id = $success;
                    $new_links = get_bookmarks(array('include'=>$link_id));
                    
                    //populate global post (required in Post_Bookmarks_List_Table)
                    global $post;
                    $post = get_post($post_id);

                    $links_table = new Post_Bookmarks_List_Table();
                    $links_table->items = $new_links;
                    $links_table->prepare_items();
                    
                    ob_start();
                    $item = end($links_table->items);
                    $links_table->single_row_columns( $item );
                    $result['html'] = ob_get_clean();

                break;
            }

        }
    }

    header('Content-type: application/json');
    echo json_encode($result);
    die(); 

}

add_action('wp_ajax_post_bkmarks_refresh_url','post_bkmarks_ajax_refresh_url');
add_action('wp_ajax_post_bkmarks_row_action','post_bkmarks_ajax_row_action');