<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class CP_Links_List_Table extends WP_List_Table {
    
    var $current_link_idx = -1;
    var $links_per_page = 1000;

    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $current_page = $this->get_pagenum();
        $total_items = count($this->items);

        // only ncessary because we have sample data
        $this->items = array_slice((array)$this->items,(($current_page-1)*$this->links_per_page),$this->links_per_page);

        $this->set_pagination_args( array(
        'total_items' => $total_items,
        'per_page'    => $this->links_per_page
        ) );
        $this->items = $this->items;
        
        //add blank link
        if ( current_user_can( 'manage_links' ) ){
            $blank_link = (object)cp_links()->sanitize_link(array('default_checked' => true,'row_classes' => 'cp-links-row-new cp-links-row-edit'));
            array_unshift($this->items, $blank_link); //prepend empty row
        }
        
    }
    
    //override parent function so we can add class to our rows
	public function single_row( $item ) {
		printf( '<tr class="%s" data-link-id="%s">',$item->row_classes,$item->link_id );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

    function display_tablenav($which){
        
    }

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'reorder'       => '',
            'favicon'       => '',
            'url'           => __('URL'),
            'name'          => __('Name'),
            'category'          => __('Categories') . sprintf(' <small><a href="%s">+</a></small>',admin_url('edit-tags.php?taxonomy=link_category')),
            'target'        => __('Target')
        );
        
        return apply_filters('cp_links_list_table_columns',$columns); //allow plugins to filter the columns
    }
    /*
    function get_sortable_columns(){
        return array();
    }
    */
    
    public function get_field_name( $slug ) {
        return sprintf('custom_post_links[links][%d][%s]',$this->current_link_idx,$slug);
    }
    
    
	/**
	 * Handles the checkbox column output.
	 *
     * This function SHOULD be overriden but we want to use column_defaut() as it is more handy, so use a trick here.
	 */
	public function column_cb( $link ) {
        $this->current_link_idx += 1;
        return $this->column_default( $link, 'cb');
	}

    /**
     * Handles the columns output.
     */
    function column_default( $link, $column_name ){
        
        $classes = array('cp-links-data');
        $display_classes = array_merge( $classes,array('cp-links-data-display') );
        $edit_classes = array_merge( $classes,array('cp-links-data-edit') );
        switch($column_name){
                
            case 'cb':
                $post_links_ids = cp_links_get_links_ids_for_post();
                $checked = ( in_array($link->link_id,$post_links_ids) || $link->default_checked ) ? true : false;

                $input_cb = sprintf( '<input type="checkbox" name="%s" value="on" %s />',
                                    $this->get_field_name('enabled'),
                                    checked($checked, true,false) 
                                   );
                $input_id = sprintf( '<input type="hidden" name="%s" value="%s"/>',
                                    $this->get_field_name('link_id'),
                                    $link->link_id
                                   );

                return $input_cb . $input_id;
            break;
                
            case 'reorder':

                $classes = array(
                    'cp-links-link-draghandle'
                );

                $input_el = sprintf( '<input type="hidden" name="%s" value="%s"/>',
                                    $this->get_field_name('order'),
                                    $this->current_link_idx
                                   );

                return $input_el . sprintf('<div %s><i class="fa fa-arrows-v" aria-hidden="true"></i></div>',cp_links_get_classes($classes));
                
            break;
                
            case 'favicon':
                return cp_links_get_favicon($link->link_url);
            break;
                
            case 'name':
                
                $name = ($link->link_name) ? $link->link_name : null;

                //edit
                $edit_el = sprintf('<input type="text" name="%s" value="%s" />',
                                   $this->get_field_name('link_name'),
                                   $name
                                  );
                
                //display

                $edit_link = get_edit_bookmark_link( $link );
                $display_classes[] = 'ellipsis';
                
                $display_el = sprintf( '<strong><a class="row-title" href="%s" aria-label="%s">%s</a></strong>',
                    $edit_link,
                    /* translators: %s: link name */
                    esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $name ) ),
                    $name
                );
                
                return sprintf( '<p%s>%s</p>',cp_links_get_classes($display_classes),$display_el ) . sprintf( '<span%s>%s</span>',cp_links_get_classes($edit_classes),$edit_el );

            break;

            case 'url':
                
                $url = ($link->link_url) ? esc_url($link->link_url) : null;

                //edit
                $edit_el = sprintf('<input type="text" name="%s" value="%s" />',
                                   $this->get_field_name('link_url'),
                                   $url
                                  );

                //display
                $short_url = url_shorten( $link->link_url );
                $display_el = sprintf('<a target="_blank" href="%s">%s</a>',$link->link_url,$short_url);
                
                return sprintf( '<span%s>%s</span>',cp_links_get_classes($display_classes),$display_el ) . sprintf( '<span%s>%s</span>',cp_links_get_classes($edit_classes),$edit_el );
                
            break;
                
            case 'category': //based on core function ion wp_link_category_checklist()
                $default = cp_links()->get_options('links_category');

                $checked_categories = array();

                if ( $link->link_id ) {
                    $checked_categories = wp_get_link_cats( $link->link_id );
                } else {
                    $checked_categories[] = $default;
                }

                $categories = get_terms( 'link_category', array( 'orderby' => 'name', 'hide_empty' => 0 ) );

                if ( empty( $categories ) )
                    return;
                
                $cats_str = null;

                foreach ( $categories as $category ) {
                    $cat_id = $category->term_id;

                    /** This filter is documented in wp-includes/category-template.php */
                    $name = esc_html( apply_filters( 'the_category', $category->name ) );

                    $cats_str.= sprintf('<li id="link-category-%s"><label for="in-link-category-%s" class="selectit"><input value="%s" type="checkbox" name="%s[]" id="in-link-category-%s" %s %s />%s</label></li>',
                           $cat_id,
                           $cat_id,
                           $cat_id,
                           $this->get_field_name( 'link_category' ),
                           $cat_id,
                           checked( in_array( $cat_id, $checked_categories ), true, false),
                           disabled( $cat_id, $default, false),
                           $name
                    );
                }
                
                return sprintf('<ul>%s</ul>',$cats_str);
                
            break;
                
            case 'target':
                
                $target = ($link->link_target) ? $link->link_target : '_none';

                $option_target = cp_links()->get_options('default_target');

                //edit
                $edit_el = sprintf('<input id="link_target_blank" type="checkbox" name="%s" value="_blank" %s/><small>%s</small>',
                                   $this->get_field_name('link_target'),
                                   checked( $option_target, '_blank',false),
                                   __('<code>_blank</code> &mdash; new window or tab.','cp_links')
                                  );

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