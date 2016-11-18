<?php

function ajax_cp_links_get_url_title(){
    $result = array(
        'message'   => null,
        'success'   => false
    );
    
    if ( isset($_POST['url'] ) && ($name = cp_links_get_name_from_url($_POST['url']) ) ){
        $result['name'] = $name;
        $result['success'] = true;
    }
    
    header('Content-type: application/json');
    echo json_encode($result);
    die();
}

add_action('wp_ajax_cp_links_get_url_title','ajax_cp_links_get_url_title');