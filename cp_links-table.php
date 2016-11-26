<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class CP_Links_List_Table extends WP_List_Table {

    function display_tablenav($which){
        
    }
    
    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
    }
    /*
    function display_tablenav($which){
        
    }
    */
    
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'reorder'       => '',
            'favicon'       => '',
            'url'           => __('URL'),
            'name'          => __('Name'),
            'target'        => __('Target')
        );
        
        return apply_filters('cp_links_list_table_columns',$columns); //allow plugins to filter the columns
    }
    /*
    function get_sortable_columns(){
        return array();
    }
    */
    
    
	/**
	 * Handles the checkbox column output.
	 *
	 * @since 4.3.0
	 * @access public
	 *
	 * @param object $link The current link object.
	 */
	public function column_cb( $link ) {
        $post_links_ids = cp_links_get_links_ids_for_post();
        $disabled = ( $link->link_id == 0);
        $checked = ( in_array($link->link_id,$post_links_ids) || $disabled ) ? true : false;

        $label = sprintf( __( 'Select %s' ), $link->link_name );
        $label_el = sprintf('<label class="screen-reader-text" for="cb-select-%s">%s</label>',$link->link_id,$label);
        $input_el = sprintf( '<input type="checkbox" name="custom_post_links[ids][]" id="cb-select-%s" value="%s" %s %s />',$link->link_id,esc_attr( $link->link_id ),checked($checked, true,false),disabled($disabled, true,false ) );

        return $label_el . $input_el;
	}

    /**
     * Handles the columns output.
     */
    function column_default( $link, $column_name ){

        switch($column_name){
                
            case 'reorder':
                $disabled = ( $link->link_id == 0);

                $classes = array(
                    'cp-links-link-draghandle'
                );

                if ($disabled) $classes[] = 'disabled';

                return sprintf('<div %s><i class="fa fa-arrows-v" aria-hidden="true"></i></div>',cp_links_get_classes($classes));
                
            break;
                
            case 'favicon':
                return cp_links_get_favicon($link->link_url);
            break;
                
            case 'name':
                if ($link->link_id == 'new'){

                    return '<input type="text" name="custom_post_links[new][name][]" value="" />';

                }else{

                    $edit_link = get_edit_bookmark_link( $link );
                    $text = $link->link_name;

                    return sprintf( '<strong><a class="row-title" href="%s" aria-label="%s">%s</a></strong>',
                        $edit_link,
                        /* translators: %s: link name */
                        esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $text ) ),
                        $text
                    );
                }
            break;

            case 'url':
                if ($link->link_id == 'new'){
                    return '<input type="text" name="custom_post_links[new][url][]" value="" />';
                }else{
                    $short_url = url_shorten( $link->link_url );
                    return sprintf('<a target="_blank" href="%s">%s</a>',$link->link_url,$short_url);
                }
            break;
                
            case 'target':
                if ($link->link_id == 'new'){
                    $option_target = cp_links()->get_options('default_target');
                    return sprintf('<input id="link_target_blank" type="checkbox" name="custom_post_links[new][target][]" value="_blank" %s/><small>%s</small>',checked( $option_target, '_blank',false),__('<code>_blank</code> &mdash; new window or tab.','cp_links'));
                }else{
                    if($link->link_target){
                        return sprintf('<code>%s</code>',$link->link_target);

                    }else{
                        return sprintf('<code>%s</code>','_none');
                    }
                }
            break;
                
            default:
                $output = null;
                return apply_filters('cp_links_list_table_column_content',$output,$link,$column_name); //allow plugins to filter the content
            break;

        }
        
    }

    
	/**
	 * Generates and displays row action links.
	 *
	 * @since 4.3.0
	 * @access protected
	 *
	 * @param object $link        Link being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string Row action output for links.
	 */
	protected function handle_row_actions( $link, $column_name, $primary ) {
		if ( 'name' !== $column_name ) {
			return '';
		}

		$edit_link = get_edit_bookmark_link( $link );

		$actions = array();
		$actions['edit'] = '<a href="' . $edit_link . '">' . __('Edit') . '</a>';
		$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url("link.php?action=delete&amp;link_id=$link->link_id", 'delete-bookmark_' . $link->link_id) . "' onclick=\"if ( confirm( '" . esc_js(sprintf(__("You are about to delete this link '%s'\n  'Cancel' to stop, 'OK' to delete."), $link->link_name)) . "' ) ) { return true;}return false;\">" . __('Delete') . "</a>";
		return $this->row_actions( $actions );
	}
    
}