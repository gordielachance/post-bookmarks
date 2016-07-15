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
            'reorder'        => '',
            'name'      => __('Name'),
            'url'       => __('URL'),
            'target'       => __('Target')
        );
        return $columns;
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
        
        $link_is_attached = false;
        
        $post_links_ids = cp_links_get_links_ids_for_post();
        
        $checked = ( in_array($link->link_id,$post_links_ids) ) ? true : false;
        
        
		?>
		<label class="screen-reader-text" for="cb-select-<?php echo $link->link_id; ?>"><?php echo sprintf( __( 'Select %s' ), $link->link_name ); ?></label>
		<input type="checkbox" name="custom_post_links[ids][]" id="cb-select-<?php echo $link->link_id; ?>" value="<?php echo esc_attr( $link->link_id ); ?>" <?php checked($checked, true );?> />
		<?php
	}
    
    public function column_reorder($link){
        ?>
        <div class="cp-links-link-draghandle">
            <i class="fa fa-arrows-v" aria-hidden="true"></i>
        </div>
        <?php
    }

	/**
	 * Handles the link name column output.
	 *
	 * @since 4.3.0
	 * @access public
	 *
	 * @param object $link The current link object.
	 */
	public function column_name( $link ) {

        $edit_link = get_edit_bookmark_link( $link );
        printf( '<strong><a class="row-title" href="%s" aria-label="%s">%s</a></strong>',
            $edit_link,
            /* translators: %s: link name */
            esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $link->link_name ) ),
            $link->link_name
        );
        
	}

	/**
	 * Handles the link URL column output.
	 *
	 * @since 4.3.0
	 * @access public
	 *
	 * @param object $link The current link object.
	 */
	public function column_url( $link ) {

        $short_url = url_shorten( $link->link_url );
        echo "<a href='$link->link_url'>$short_url</a>";
            
	}
    
	public function column_target( $link ) {

        if($link->link_target){
            printf('<code>%s</code>',$link->link_target);
            
        }else{
            printf('<code>%s</code>','_none');
        }
        

	}
}

/*
if(!class_exists('WP_Links_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-links-list-table.php' );
}

class CP_Links_List_Table2 extends WP_Links_List_Table {

	public function prepare_items() {
		global $cat_id, $s, $orderby, $order;

		wp_reset_vars( array( 'action', 'cat_id', 'link_id', 'orderby', 'order', 's' ) );

		$args = array( 'hide_invisible' => 0, 'hide_empty' => 0 );

		if ( 'all' != $cat_id )
			$args['category'] = $cat_id;
		if ( !empty( $s ) )
			$args['search'] = $s;
		if ( !empty( $orderby ) )
			$args['orderby'] = $orderby;
		if ( !empty( $order ) )
			$args['order'] = $order;
        
        $args = array();
		$this->items = get_bookmarks( $args );
        
        print_R($this->items);
        
        $this->items = null;
        

	}
    
    function column_default($link, $column_name){
        switch($column_name){
            default:
                return print_r($link,true); //Show the whole array for troubleshooting purposes
        }
    }
    
}
*/