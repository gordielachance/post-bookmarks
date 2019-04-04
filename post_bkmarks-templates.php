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