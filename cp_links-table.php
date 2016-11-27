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
    
    //override parent function so we can add class to our rows
	public function single_row( $item ) {
		printf( '<tr class="%s" data-link-id="%s">',$item->row_classes,$item->link_id );
		$this->single_row_columns( $item );
		echo '</tr>';
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
        $checked = ( in_array($link->link_id,$post_links_ids) || $link->default_checked ) ? true : false;

        $label = sprintf( __( 'Select %s' ), $link->link_name );
        $label_el = sprintf('<label class="screen-reader-text" for="cb-select-%s">%s</label>',$link->link_id,$label);
        $input_el = sprintf( '<input type="checkbox" name="custom_post_links[ids][]" id="cb-select-%s" value="%s" %s />',$link->link_id,esc_attr( $link->link_id ),checked($checked, true,false) );

        return $label_el . $input_el;
	}

    /**
     * Handles the columns output.
     */
    function column_default( $link, $column_name ){
        
        $classes = array('cp-links-data');
        $display_classes = array_merge( $classes,array('cp-links-data-display') );
        $edit_classes = array_merge( $classes,array('cp-links-data-edit') );

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
                
                $name = ($link->link_name) ? $link->link_name : null;

                //edit
                $edit_el = sprintf('<input type="text" name="custom_post_links[new][name][]" value="%s" />',$name);
                
                //display

                $edit_link = get_edit_bookmark_link( $link );

                $display_el = sprintf( '<p%s><strong><a class="row-title" href="%s" aria-label="%s">%s</a></strong></p>',
                    cp_links_get_classes($display_classes),
                    $edit_link,
                    /* translators: %s: link name */
                    esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $name ) ),
                    $name
                );
                
                return sprintf( '<span%s>%s</span>',cp_links_get_classes($display_classes),$display_el ) . sprintf( '<span%s>%s</span>',cp_links_get_classes($edit_classes),$edit_el );

            break;

            case 'url':
                
                $url = ($link->link_url) ? $link->link_url : null;

                //edit
                $edit_el = sprintf('<input type="text" name="custom_post_links[new][url][]" value="%s" />',$url);

                //display
                $short_url = url_shorten( $link->link_url );
                $display_el = sprintf('<a target="_blank" href="%s">%s</a>',$link->link_url,$short_url);
                
                return sprintf( '<span%s>%s</span>',cp_links_get_classes($display_classes),$display_el ) . sprintf( '<span%s>%s</span>',cp_links_get_classes($edit_classes),$edit_el );
                
            break;
                
            case 'target':
                
                $target = ($link->link_target) ? $link->link_target : '_none';

                $option_target = cp_links()->get_options('default_target');

                //edit
                $edit_el = sprintf('<input id="link_target_blank" type="checkbox" name="custom_post_links[new][target][]" value="_blank" %s/><small>%s</small>',checked( $option_target, '_blank',false),__('<code>_blank</code> &mdash; new window or tab.','cp_links'));

                //display
                $display_el = sprintf('<code>%s</code>',$target);
                return sprintf( '<span%s>%s</span>',cp_links_get_classes($display_classes),$display_el ) . sprintf( '<span%s>%s</span>',cp_links_get_classes($edit_classes),$edit_el );
                
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
		if ( 'url' !== $column_name ) {
			return '';
		}
        
        $actions = array();

        if ( $link->link_id ){
            $edit_link = get_edit_bookmark_link( $link );
            $actions['edit'] = '<a href="' . $edit_link . '">' . __('Edit') . '</a>';
            $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url("link.php?action=delete&amp;link_id=$link->link_id", 'delete-bookmark_' . $link->link_id) . "' onclick=\"if ( confirm( '" . esc_js(sprintf(__("You are about to delete this link '%s'\n  'Cancel' to stop, 'OK' to delete."), $link->link_name)) . "' ) ) { return true;}return false;\">" . __('Delete') . "</a>";
        }

		return $this->row_actions( $actions );
	}
    
}