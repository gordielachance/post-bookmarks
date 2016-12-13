<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class CP_Links_List_Table extends WP_List_Table {
    
    var $current_link_idx = -1;
    var $links_per_page = 20;

    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $current_page = $this->get_pagenum();
        $total_items = count($this->items);

        // only necessary because we have sample data
        $this->items = array_slice((array)$this->items,(($current_page-1)*$this->links_per_page),$this->links_per_page);

        $this->set_pagination_args( array(
        'total_items' => $total_items,
        'per_page'    => $this->links_per_page
        ) );
        $this->items = $this->items;

    }
    
	/**
	 * Generate the tbody element for the list table.
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function display_rows_or_placeholder() {
        
        //append blank row
        if ( current_user_can( 'manage_links' ) ){
            $blank_link = (object)cp_links()->sanitize_link(array('default_checked' => true,'row_classes' => 'cp-links-row-new cp-links-row-edit'));
            $this->single_row($blank_link);
        }
        
		parent::display_rows_or_placeholder();
	}
    
    //override parent function so we can add class to our rows
	public function single_row( $item ) {
		printf( '<tr class="%s" data-link-id="%s">',$item->row_classes,$item->link_id );
		$this->single_row_columns( $item );
		echo '</tr>';
	}
    
    /**
     * Generate the table navigation above or below the table
     *
     * @since 3.1.0
     * @access protected
     *
     * @param string $which
     */
    protected function display_tablenav( $which ) {

        // REMOVED NONCE -- INTERFERING WITH SAVING POSTS ON METABOXES
        // Add better detection if this class is used on meta box or not.
        /*
        if ( 'top' == $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
        }
        */

        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <div class="alignleft actions bulkactions">
                <?php //$this->bulk_actions( $which ); ?>
            </div>
            <?php
            $this->extra_tablenav( $which );
            $this->pagination( $which );
            ?>

            <br class="clear"/>
        </div>
    <?php
    }
    
    function extra_tablenav($which){
    ?>
            <div class="alignleft actions">
                <?php
                if ( 'top' === $which && !is_singular() ) {
                    //add link
                    if ( current_user_can( 'manage_links' ) ){   
                        ?>
                        <a id="cp-links-add-link" href="link-add.php" class="button"><?php echo esc_html_x('Add New', 'link'); ?></a>
                        <?php
                    }
                }
                ?>
            </div>
    <?php
    }
    
	/**
	 * Display the list of views available on this table.
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function views() {
		$views = $this->get_views();

		if ( empty( $views ) )
			return;

		echo "<ul class='subsubsub'>\n";
		foreach ( $views as $class => $view ) {
			$views[ $class ] = "\t<li class='$class'>$view";
		}
		echo implode( " |</li>\n", $views ) . "</li>\n";
		echo "</ul>";
	}
    
    function get_views() {
        global $post;
        
        $link_attached = $link_library = null;
        $link_attached_count = $link_library_count = 0;
        $link_attached_classes = $link_library_classes = array();
        
        if ( !cp_links()->links_tab ) $link_attached_classes[] = 'current';
        $link_attached_count = count( cp_links_get_for_post($post->ID) );
        
        $link_attached = sprintf(
            __('<a href="%1$s"%2$s>%3$s <span class="count">(<span class="imported-count">%4$s</span>)</span></a>'),
            get_edit_post_link(),
            cp_links_get_classes_attr($link_attached_classes),
            __('Attached','cp-links'),
            $link_attached_count
        );
        
        if ( cp_links()->links_tab == 'library' ) $link_library_classes[] = 'current';
        $link_library_count = count( get_bookmarks( array('limit'=>-1) ) );
        
        if ($link_library_count){
            $link_library = sprintf(
                __('<a href="%1$s"%2$s>%3$s <span class="count">(<span class="imported-count">%4$s</span>)</span></a>'),
                add_query_arg(array('cpl_tab'=>'library'),get_edit_post_link()),
                cp_links_get_classes_attr($link_library_classes),
                __('Links library','cp-links'),
                $link_library_count
            );
        }

		$links = array(
            'attached'      => $link_attached,
            'library'       => $link_library
        );
        
        //allow plugins to filter this
        $links = apply_filters('cp_links_get_table_tabs',$links);
        
        $links = array_filter($links);
        
        return $links;
    }
    
    function get_tab_links(){
        global $post;
        $links = array();
        switch (cp_links()->links_tab){
            case 'library':
                $links = get_bookmarks( array('limit'=>-1) );
            break;
            default: //attached links
                $links = cp_links_get_for_post($post->ID);
            break;
        }
        $links = apply_filters('cp_links_get_table_tab_links',$links);
        
        foreach ((array)$links as $key=>$link){
            $links[$key] = (object)cp_links()->sanitize_link($link);
        }
        
        //filter results
        if ( $search = strtolower(cp_links()->filter_links_text) ){
            
            foreach ($links as $key=>$link){
                
                $in_name    = strpos(strtolower($link->link_name), $search);
                $in_url     = strpos(strtolower($link->link_url), $search);
                
                if ( ($in_name === false) && ($in_url === false) ){
                    unset($links[$key]);
                }
                
            }

        }
        
        return $links;
    }
    
    /**
	 * Displays the search box.
	 *
	 * @since 4.6.0
	 * @access public
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 */
	public function search_box( $text, $input_id ) {
        /*
		if ( !cp_links()->filter_links_text && ! $this->has_items() ) {
			return;
		}
        */

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( cp_links()->links_tab ) {
			echo '<input type="hidden" name="cpl_tab" value="' . esc_attr( cp_links()->links_tab ) . '" />';
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" class="wp-filter-search" name="cpl_filter" value="<?php echo cp_links()->filter_links_text; ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}

    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array();

        $actions['delete'] = __('Move to Trash');
        
        return $actions;
    }

    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }
        
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

                return $input_el . sprintf('<div %s><i class="fa fa-arrows-v" aria-hidden="true"></i></div>',cp_links_get_classes_attr($classes));
                
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
                
                return sprintf( '<p%s>%s</p>',cp_links_get_classes_attr($display_classes),$display_el ) . sprintf( '<span%s>%s</span>',cp_links_get_classes_attr($edit_classes),$edit_el );

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
                
                return sprintf( '<span%s>%s</span>',cp_links_get_classes_attr($display_classes),$display_el ) . sprintf( '<span%s>%s</span>',cp_links_get_classes_attr($edit_classes),$edit_el );
                
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
                return sprintf( '<span%s>%s</span>',cp_links_get_classes_attr($display_classes),$display_el ) . sprintf( '<span%s>%s</span>',cp_links_get_classes_attr($edit_classes),$edit_el );
                
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

        $edit_link = get_edit_bookmark_link( $link );
        $actions['edit'] = '<a href="' . $edit_link . '">' . __('Edit') . '</a>';
        if ( $link->link_id ){
            $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url("link.php?action=delete&amp;link_id=$link->link_id", 'delete-bookmark_' . $link->link_id) . "' onclick=\"if ( confirm( '" . esc_js(sprintf(__("You are about to delete this link '%s'\n  'Cancel' to stop, 'OK' to delete."), $link->link_name)) . "' ) ) { return true;}return false;\">" . __('Delete') . "</a>";
        }

		return $this->row_actions( $actions );
	}
    
}