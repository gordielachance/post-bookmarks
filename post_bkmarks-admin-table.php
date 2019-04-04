<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Post_Bookmarks_List_Table extends WP_List_Table {
    var $current_link_idx = -1;
    var $links_per_page = -1;
    var $post_link_ids = array(); //IDs of links attached to this post

    function prepare_items() {
        global $post;
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $current_page = $this->get_pagenum();
        $total_items = count($this->items);

        if ($this->links_per_page > 0){
            $this->items = array_slice((array)$this->items,(($current_page-1)*$this->links_per_page),$this->links_per_page);
        }

        $this->set_pagination_args( array(
        'total_items' => $total_items,
        'per_page'    => $this->links_per_page
        ) );
        $this->items = $this->items;
        
        $this->post_link_ids = (array)Post_Bookmarks::get_post_link_ids($post->ID);

    }

	/**
	 * Generate the tbody element for the list table.
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function display_rows_or_placeholder() {
        global $post;
        //append blank row
        if ( current_user_can( 'edit_post' , $post->ID ) ){ 
            $blank_link = (object)post_bkmarks()->sanitize_link(array('row_classes' => array('metabox-table-row-new','metabox-table-row-edit')));
            $this->single_row($blank_link);
        }
        
		parent::display_rows_or_placeholder();
	}
    
    /*
    override parent function so we can add attributes, etc.
    */
	public function single_row( $item ) {
        
		printf( '<tr %s>',post_bkmarks_get_classes_attr($item->row_classes) );
		$this->single_row_columns( $item );
		echo '</tr>';
        
        $this->current_link_idx ++;
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
                <?php $this->bulk_actions( $which ); ?>
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
        global $post;
    ?>
            <div class="alignleft actions">
                <?php
                if ( 'top' === $which && !is_singular() ) {
                    
                    //add link
                    if ( current_user_can( 'edit_post' , $post->ID ) ){ 
                        ?>
                        <a href="link-add.php" class="row-add-button button"><?php echo esc_html_x('Add Row', 'link', 'post-bkmarks'); ?></a>
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
        
        $link_attached = $link_library = null;
        $link_attached_count = $link_library_count = 0;
        $link_attached_classes = $link_library_classes = array();
        
        if ( post_bkmarks()->links_tab == 'attached' ) $link_attached_classes[] = 'current';
        $link_attached_count = count( Post_Bookmarks::get_tab_links('attached') );
        
        $link_attached = sprintf(
            __('<a href="%1$s"%2$s>%3$s <span class="count">(<span class="imported-count">%4$s</span>)</span></a>'),
            get_edit_post_link(),
            post_bkmarks_get_classes_attr($link_attached_classes),
            __('Attached','post-bkmarks'),
            $link_attached_count
        );
        
        if ( post_bkmarks()->links_tab == 'library' ) $link_library_classes[] = 'current';
        $link_library_count = count( Post_Bookmarks::get_tab_links('library') );
        
        if ($link_library_count){
            $link_library = sprintf(
                __('<a href="%1$s"%2$s>%3$s <span class="count">(<span class="imported-count">%4$s</span>)</span></a>'),
                add_query_arg(array('pbkm_tab'=>'library'),get_edit_post_link()),
                post_bkmarks_get_classes_attr($link_library_classes),
                __('Links library','post-bkmarks'),
                $link_library_count
            );
        }

		$links = array(
            'attached'      => $link_attached,
            'library'       => $link_library
        );
        
        //allow plugins to filter this
        $links = apply_filters('post_bkmarks_get_table_tabs',$links);
        
        $links = array_filter($links);
        
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
		if ( !post_bkmarks()->filter_links_text && ! $this->has_items() ) {
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
		if ( post_bkmarks()->links_tab != 'attached' ) {
			echo '<input type="hidden" name="pbkm_tab" value="' . esc_attr( post_bkmarks()->links_tab ) . '" />';
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" class="wp-filter-search" name="pbkm_filter" value="<?php echo post_bkmarks()->filter_links_text; ?>" />
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
        global $post;
        
        $actions = array();

        if ( current_user_can( 'edit_post' , $post->ID ) ){ 
            $actions['attach'] = __('Attach','post-bkmarks');
            $actions['remove'] = __('Remove','post-bkmarks');
        }
        
        if ( current_user_can( 'manage_links' ) ){ 
            $actions['save'] = __('Save','post-bkmarks');
            $actions['delete'] = __('Delete','post-bkmarks');
        }

        return apply_filters('post_bkmarks_get_bulk_actions',$actions);
    }
    
	/**
	 * Display the bulk actions dropdown.
	 * Instanciated because we need a different name for the 'select' form elements (default is 'action' & 'action2') or it will interfer with WP.
	 */
	function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();
			/**
			 * Filters the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 3.5.0
			 *
			 * @param array $actions An array of the available bulk actions.
			 */
			$this->_actions = apply_filters( "post_bkmarks_bulk_actions-{$this->screen->id}", $this->_actions );
			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) )
			return;

		echo '<label for="post-bkmarks-bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
		echo '<select name="post-bkmarks-action' . $two . '" id="post-bkmarks-bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __( 'Bulk Actions' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

			echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
		}

		echo "</select>\n";

		submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "post-bkmarks-doaction$two" ) );
		echo "\n";
	}

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'reorder'       => '',
            'favicon'       => '',
            'url'           => __('URL'),
            'name'          => __('Name'),
            'category'          => __('Categories') . sprintf(' <small><a href="%s">+</a></small>',admin_url('edit-tags.php?taxonomy=link_category')),
            'target'        => __('Target','post-bkmarks'),
            'action'        => __('Action','post-bkmarks')
        );

        if ( post_bkmarks()->get_options('links_orderby') != 'custom' ){
            unset($columns['reorder']);
        }

        return apply_filters('post_bkmarks_list_table_columns',$columns); //allow plugins to filter the columns
    }
    /*
    function get_sortable_columns(){
        return array();
    }
    */
    
    public function get_field_name( $slug ) {
        return sprintf('post_bkmarks[links][%d][%s]',$this->current_link_idx,$slug);
    }
    
    
	/**
	 * Handles the checkbox column output.
	 *
     * This function SHOULD be overriden but we want to use column_defaut() as it is more handy, so use a trick here.
	 */
	public function column_cb( $item ) {
        return $this->column_default( $item, 'cb');
	}

    /**
     * Handles the columns output.
     */
    function column_default( $item, $column_name ){
        
        $classes = array('metabox-table-cell-toggle');
        $display_classes = array_merge( $classes,array('metabox-table-cell-display') );
        $edit_classes = array_merge( $classes,array('metabox-table-cell-edit') );
        switch($column_name){
                
            case 'cb':
                $post_links_ids = Post_Bookmarks::get_post_link_ids();

                $input_cb = sprintf( '<input type="checkbox" name="%s" value="on"/>',
                                    $this->get_field_name('selected')
                                   );
                $input_id = sprintf( '<input type="hidden" name="%s" value="%s"/>',
                                    $this->get_field_name('link_id'),
                                    $item->link_id
                                   );

                return $input_cb . $input_id;
            break;
                
            case 'reorder':

                $classes = array(
                    'metabox-table-row-draghandle'
                );

                $output = sprintf( '<input type="hidden" name="%s" value="%s"/>',
                                    $this->get_field_name('link_order'),
                                    $this->current_link_idx
                                   );
                
                if ( ( post_bkmarks()->links_tab == 'attached' ) && in_array($item->link_id,$this->post_link_ids) ){
                    $output.= sprintf('<div %s><i class="fa fa-arrows-v" aria-hidden="true"></i></div>',post_bkmarks_get_classes_attr($classes));
                }

                return $output;
                
            break;
                
            case 'favicon':
                return Post_Bookmarks::get_favicon($item->link_url);
            break;
                
            case 'name':
                
                $name = ($item->link_name) ? $item->link_name : null;

                //edit
                $edit_el = sprintf('<input type="text" name="%s" value="%s" />',
                                   $this->get_field_name('link_name'),
                                   $name
                                  );
                
                //display

                $edit_link = get_edit_bookmark_link( $item );
                $display_classes[] = 'ellipsis';
                
                $display_el = sprintf( '<strong><a class="row-title" href="%s" aria-label="%s">%s</a></strong>',
                    $edit_link,
                    /* translators: %s: link name */
                    esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $name ) ),
                    $name
                );
                
                return 
                    sprintf( '<p%s>%s</p>',post_bkmarks_get_classes_attr($display_classes),$display_el ) . //display
                    sprintf( '<span%s>%s</span>',post_bkmarks_get_classes_attr($edit_classes),$edit_el ); //edit

            break;

            case 'url':
                
                $url = ($item->link_url) ? esc_url($item->link_url) : null;

                //edit
                $edit_el = sprintf('<input type="text" name="%s" value="%s" />',
                                   $this->get_field_name('link_url'),
                                   $url
                                  );

                //display
                $short_url = url_shorten( $item->link_url );
                $display_el = sprintf('<a target="_blank" href="%s">%s</a>',$item->link_url,$short_url);
                
                return 
                    sprintf( '<span%s>%s</span>',post_bkmarks_get_classes_attr($display_classes),$display_el ) . //display
                    sprintf( '<span%s>%s</span>',post_bkmarks_get_classes_attr($edit_classes),$edit_el ); //edit
                
            break;
                
            case 'category': //based on core function ion wp_link_category_checklist()
                $default = post_bkmarks()->get_links_category();

                $checked_categories = array();

                if ( $item->link_id ) {
                    $checked_categories = wp_get_link_cats( $item->link_id );
                } else {
                    $checked_categories[] = $default;
                }

                $categories = get_terms( 'link_category', array( 'orderby' => 'name', 'hide_empty' => 0 ) );

                if ( empty( $categories ) )
                    return;
                
                $cats_display = array();
                $cats_edit = array();

                foreach ( $categories as $category ) {
                    $cat_id = $category->term_id;

                    /** This filter is documented in wp-includes/category-template.php */
                    $name = esc_html( apply_filters( 'the_category', $category->name ) );
                    
                    $is_checked = in_array( $cat_id, $checked_categories );
                    $is_default_cat = ($cat_id == $default);
                    
                    if ($is_checked && !$is_default_cat){
                        $cats_display[] = $name;
                    }

                    $cats_edit[]= sprintf('<li id="link-category-%s"><label for="in-link-category-%s" class="selectit"><input value="%s" type="checkbox" name="%s[]" id="in-link-category-%s" %s %s />%s</label></li>',
                        $cat_id,
                        $cat_id,
                        $cat_id,
                        $this->get_field_name( 'link_category' ),
                        $cat_id,
                        checked( $is_checked, true, false),
                        disabled( $is_default_cat, true, false),
                        $name
                    );
                }
                
                $display_el = implode(", ",$cats_display);
                $edit_el = sprintf('<ul>%s</ul>',implode("\n",$cats_edit));
                
                return 
                    sprintf( '<span%s>%s</span>',post_bkmarks_get_classes_attr($display_classes),$display_el ) . //display
                    sprintf( '<span%s>%s</span>',post_bkmarks_get_classes_attr($edit_classes),$edit_el ); //edit
                
                
                
            break;
                
            case 'target':
                
                //TO FIX
                $target = ($item->link_target) ? $item->link_target : '_none';
                $option_target = post_bkmarks()->get_options('default_target');

                //edit
                $edit_el = sprintf('<input id="link_target_blank" type="checkbox" name="%s" value="_blank" %s/><small>%s</small>',
                                   $this->get_field_name('link_target'),
                                   checked( $option_target, '_blank',false),
                                   '<code>_blank</code>'
                                  );

                //display
                $display_el = sprintf('<code>%s</code>',$target);
                return sprintf( '<span%s>%s</span>',post_bkmarks_get_classes_attr($display_classes),$display_el ) . sprintf( '<span%s>%s</span>',post_bkmarks_get_classes_attr($edit_classes),$edit_el );
                
            break;
                
            case 'action':
                //will be handled by handle_row_actions()
            break;

            default:
                $output = null;
                return apply_filters('post_bkmarks_list_table_column_content',$output,$item,$column_name); //allow plugins to filter the content
            break;

        }
        
    }

    
	/**
	 * Generates and displays row action links.
	 *
	 * @since 4.3.0
	 * @access protected
	 *
	 * @param object $item        Link being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string Row action output for links.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {

		if ( 'action' !== $column_name ) {
			return '';
		}
        
        $actions = array();
        
        $is_attached = in_array($item->link_id,$this->post_link_ids);
        
        //attach
        $attach_url = add_query_arg(array('post-bkmarks-action'=>'attach','link_id'=>$item->link_id),get_edit_post_link());
        $attach_url = wp_nonce_url($attach_url,'post_bkmarks_link','post_bkmarks_link_nonce');
        $actions['attach'] = sprintf('<a class="%s" href="%s">%s</a>','post-bkmarks-row-action-attach',$attach_url,__('Attach','post-bkmarks'));

        if ( $item->link_id ){
            
            if ( $is_attached ){
                
                //unset attach link
                unset($actions['attach']);
                
                //remove
                $remove_url = add_query_arg(array('post-bkmarks-action'=>'remove','link_id'=>$item->link_id),get_edit_post_link());
                $remove_url = wp_nonce_url($remove_url,'post_bkmarks_link','post_bkmarks_link_nonce');
                $actions['remove'] = sprintf('<a class="%s" href="%s">%s</a>','post-bkmarks-row-action-remove',$remove_url,__('Remove','post-bkmarks'));
            }
            
            //edit
            $actions['edit'] = sprintf('<a class="%s" href="%s">%s</a>','post-bkmarks-row-action-edit',get_edit_bookmark_link( $item ),__('Edit'));
            
        }
 
        if ( $item->link_id ){
            
            //save
            $actions['save'] = sprintf('<a class="%s" href="%s">%s</a>','post-bkmarks-row-action-save','#',__('Save'));
            
            //delete
            $delete_url = add_query_arg(array('post-bkmarks-action'=>'delete','link_id'=>$item->link_id),get_edit_post_link());
            $delete_url = wp_nonce_url($delete_url,'post_bkmarks_link','post_bkmarks_link_nonce');
            $actions['delete'] = sprintf('<a class="%s" href="%s">%s</a>','post-bkmarks-row-action-delete',$delete_url,__('Delete'));
        }

		return $this->row_actions( $actions, true );
	}
    
}