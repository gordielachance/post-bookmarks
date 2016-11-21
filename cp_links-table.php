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
        
        $disabled = ( $link->link_id == 0);
        $checked = ( in_array($link->link_id,$post_links_ids) || $disabled ) ? true : false;
        
        
		?>
		<label class="screen-reader-text" for="cb-select-<?php echo $link->link_id; ?>"><?php echo sprintf( __( 'Select %s' ), $link->link_name ); ?></label>
		<input type="checkbox" name="custom_post_links[ids][]" id="cb-select-<?php echo $link->link_id; ?>" value="<?php echo esc_attr( $link->link_id ); ?>" <?php checked($checked, true );?> <?php disabled($disabled, true );?> />
		<?php
	}
    
    public function column_reorder($link){
        
        $disabled = ( $link->link_id == 0);
        
        $classes = array(
            'cp-links-link-draghandle'
        );
        
        if ($disabled) $classes[] = 'disabled';
        
        ?>
        <div<?php cp_links_classes($classes);?>>
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
        
        if ($link->link_id == 'new'){
            ?>
            <input type="text" name="custom_post_links[new][name][]" value="" />
            <?php
        }else{
            
            $edit_link = get_edit_bookmark_link( $link );
            $text = $link->link_name;

            printf( '<strong><a class="row-title" href="%s" aria-label="%s">%s</a></strong>',
                $edit_link,
                /* translators: %s: link name */
                esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $text ) ),
                $text
            );
        }
        
	}
    
	public function column_favicon( $link ) {
        return cp_links_output_favicon($link);
            
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
        if ($link->link_id == 'new'){
            ?>
            <input type="text" name="custom_post_links[new][url][]" value="" />
            <?php
        }else{
            $short_url = url_shorten( $link->link_url );
            printf('<a target="_blank" href="%s">%s</a>',$link->link_url,$short_url);
        }

            
	}
    
	public function column_target( $link ) {
        if ($link->link_id == 'new'){
            $option_target = cp_links()->get_options('default_target');
            ?>
            <input id="link_target_blank" type="checkbox" name="custom_post_links[new][target][]" value="_blank" <?php checked( $option_target, '_blank');?>/>
            <small><?php _e('<code>_blank</code> &mdash; new window or tab.'); ?></small>
            <?php
        }else{
            if($link->link_target){
                printf('<code>%s</code>',$link->link_target);

            }else{
                printf('<code>%s</code>','_none');
            }
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
