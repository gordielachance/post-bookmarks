<?php

function ajax_cp_links_refresh_url(){
    $result = array(
        'message'   => null,
        'success'   => false,
        'input'     => $_POST
    );

    $url = ( isset($_POST['url']) ) ? $_POST['url'] : null;
    $url = cp_links_maybe_add_url_protocol($url);
    
    if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
        $result['message'] = $url . 'is not a valid url';
    }else{
        $result['success'] = true;
        
        //guess the link name if none provided
        if ( $new_name = cp_links_get_name_from_url($url) ){
            $result['name'] = $new_name;
        }
        
        //favicon
        $result['favicon'] = cp_links_get_favicon($url);
        
    }

    header('Content-type: application/json');
    echo json_encode($result);
    die();
}

add_action('wp_ajax_cp_links_refresh_url','ajax_cp_links_refresh_url');