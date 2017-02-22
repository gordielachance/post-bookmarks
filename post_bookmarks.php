<?php
/*
Plugin Name: Post Bookmarks
Description: Adds a new metabox to the editor, allowing you to attach a set of related links to any post
Plugin URI: https://github.com/gordielachance/post-bookmarks
Author: G.Breant
Author URI: https://profiles.wordpress.org/grosbouff/#content-plugins
Version: 2.0.9
License: GPL2
*/

class Post_Bookmarks {
    /** Version ***************************************************************/
    /**
    * @public string plugin version
    */
    public $version = '2.0.9';
    /**
    * @public string plugin DB version
    */
    public $db_version = '203';
    /** Paths *****************************************************************/
    public $file = '';
    /**
    * @public string Basename of the plugin directory
    */
    public $basename = '';
    /**
    * @public string Absolute path to the plugin directory
    */
    public $plugin_dir = '';
    
    /**
    * @var The one true Instance
    */
    private static $instance;

    static $meta_name_options = 'post_bkmarks-options';

    var $filter_links_text = null;
    
    public static function instance() {
        
            if ( ! isset( self::$instance ) ) {
                    self::$instance = new Post_Bookmarks;
                    self::$instance->setup_globals();
                    self::$instance->includes();
                    self::$instance->setup_actions();
            }
            return self::$instance;
    }
    /**
        * A dummy constructor to prevent bbPress from being loaded more than once.
        *
        * @since bbPress (r2464)
        * @see bbPress::instance()
        * @see bbpress();
        */
    private function __construct() { /* Do nothing here */ }
    
    function setup_globals() {
        /** Paths *************************************************************/
        $this->file       = __FILE__;
        $this->basename   = plugin_basename( $this->file );
        $this->plugin_dir = plugin_dir_path( $this->file );
        $this->plugin_url = plugin_dir_url ( $this->file );
        $this->links_tab = ( isset($_REQUEST['pbkm_tab'] ) ) ? $_REQUEST['pbkm_tab'] : null; //links tab selected backend

        $this->options_default = array(
            'ignored_post_type'     => array('attachment','revision','nav_menu_item'),
            'display_links'         => 'after',
            'default_target'        => '_blank',
            'links_orderby'         => 'name',
            'ignore_target_local'   => 'on',
            'get_favicon'           => 'on',
            'hide_from_bookmarks'   => 'on'
        );
        $this->options = wp_parse_args(get_option( self::$meta_name_options), $this->options_default);

        //search links
        $this->filter_links_text = ( isset($_REQUEST['pbkm_filter']) ) ? $_REQUEST['pbkm_filter'] : null; //existing links to attach to post
        
        //is a post update, do loose our search term
        if ( isset($_GET['message']) && ($_GET['message'] == '1') ){
            $this->filter_links_text = null;
        }
        

        
    }
    function includes(){
        
        require $this->plugin_dir . 'post_bkmarks-table.php';
        require $this->plugin_dir . 'post_bkmarks-templates.php';
        require $this->plugin_dir . 'post_bkmarks-functions.php';
        require $this->plugin_dir . 'post_bkmarks-settings.php';
        require $this->plugin_dir . 'post_bkmarks-ajax.php';
        
    }
    function setup_actions(){  

        add_filter( 'pre_option_link_manager_enabled', '__return_true' ); //enable Link Manager plugin

        add_action( 'plugins_loaded', array($this, 'upgrade'));
        
        add_action( 'admin_init', array($this,'load_textdomain'));
        add_action( 'admin_init', array( $this, 'register_scripts_styles_admin' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles_admin' ) );
        
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
        
        add_action( 'add_meta_boxes', array($this, 'metabox_add'));
        add_action( 'save_post', array(&$this,'metabox_save'));
        
        add_filter('the_content', 'post_bkmarks_output_links', 100, 2);
        
        add_filter( 'get_bookmarks', array(&$this,'filter_bookmarks'),10,2);
        add_filter( 'get_bookmarks', array(&$this,'exclude_from_bookmarks'),10,2);
        
        
        add_filter('redirect_post_location',array($this, 'metabox_variables_redirect')); //redirect with searched links text - http://wordpress.stackexchange.com/a/52052/70449
        
        


    }
    
    function load_textdomain() {
        load_plugin_textdomain( 'post-bkmarks', false, $this->plugin_dir . '/languages' );
    }

    function upgrade(){
        global $wpdb;
        
        $current_version = get_option("_post_bkmarks-db_version");
        if ($current_version==$this->db_version) return false;
        
        if(!$current_version){ //not installed
            
            if ( $has_old_plugin = get_option('cp_links_options') ){
                
                //rename options
                $update_options = $wpdb->prepare( 
                    "UPDATE `".$wpdb->prefix . "options` SET option_name = '%s' WHERE option_name = '%s'",
                    self::$meta_name_options,
                    'cp_links_options'
                );
                $wpdb->query($update_options);

                //rename category
                $update_category = $wpdb->prepare( 
                    "UPDATE `".$wpdb->prefix . "terms` SET name = '%s', slug = '%s' WHERE slug = '%s'",
                    __('Post Bookmarks','post-bkmarks'),
                    'post-bookmarks',
                    'cp-links'
                );
                $wpdb->query($update_category);
                
                //rename post metas
                $update_posts = $wpdb->prepare( 
                    "UPDATE `".$wpdb->prefix . "postmeta` SET meta_key = '%s' WHERE meta_key = '%s'",
                    '_post_bkmarks_ids',
                    '_custom_post_links_ids'
                );
                $wpdb->query($update_posts);
            }
            


            /*
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
             */

            
        }
        
        //update DB version
        update_option("_post_bkmarks-db_version", $this->db_version );
    }
    
    function get_options($keys = null){
        return post_bkmarks_get_array_value($keys,$this->options);
    }
    
    public function get_default_option($keys = null){
        return post_bkmarks_get_array_value($keys,$this->options_default);
    }
    
    /*
    Get our links category (based on slug) or create it if it does not exists
    */
    
    public function get_links_category(){
        
        $cat_id = null;
        $cat_slug = 'post-bookmarks';
        
        if ( $cat = get_term_by( 'slug', $cat_slug, 'link_category') ){
            $cat_id = $cat->term_id;
        }else{
            $cat_id = wp_insert_term( 
                __('Post Bookmarks','post-bkmarks'), 
                'link_category',
                 array(
                     'description'  => sprintf(__('Parent category for all links created by the %s plugin.','post-bkmarks'),'<a href="'.admin_url('options-general.php?page=pbkm_settings').'" target="blank">'.__('Post Bookmarks','post-bkmarks').'</a>'),
                     'slug'         => $cat_slug
                 ) 
            );
        }
        return $cat_id;
    }

    
    /* get post types that supports Post Bookmarks */
    
    public function allowed_post_types(){
        
        $allowed = array();
        
        $post_types = get_post_types();
        
        $disabled_default = (array)self::get_default_option('ignored_post_type');
        $disabled_option = (array)self::get_options('ignored_post_type');
        
        $disabled = wp_parse_args($disabled_option,$disabled_default);
        $disabled = array_unique($disabled);
        
        $allowed = array();
        foreach ((array)$post_types as $post_type){
            if (in_array($post_type,$disabled)) continue;
            $allowed[] = $post_type;
        }
        return $allowed;
    }

    function register_scripts_styles_admin(){
        
        // CSS
        
        wp_register_style( 'post-bkmarks-admin',  $this->plugin_url . '_inc/css/post_bkmarks-admin.css',$this->version );
        
        // JS
        
        // uri.js (https://github.com/medialize/URI.js)
        wp_register_script( 'uri', $this->plugin_url . '_inc/js/URI.min.js', null, '1.18.3');
        wp_register_script( 'jquery-uri', $this->plugin_url . '_inc/js/jquery.URI.min.js', array('uri'), '1.18.3');
        
        wp_register_script( 'post-bkmarks-admin', $this->plugin_url . '_inc/js/post_bkmarks_admin.js', array('jquery-core', 'jquery-ui-core', 'jquery-ui-sortable','jquery-uri'),$this->version);
    }
    

    
    /*
     * enqueue admin scripts
     * https://pippinsplugins.com/loading-scripts-correctly-in-the-wordpress-admin/
     */
    function enqueue_scripts_styles_admin( $hook ){

        $screen = get_current_screen();
        
        if( is_object( $screen ) ) {
            if( ( in_array($hook, array('post.php', 'post-new.php','edit.php') ) &&  in_array($screen->id, $this->allowed_post_types() ) ) || ( $screen->base == 'settings_page_pbkm_settings') ) {
                wp_enqueue_script( 'post-bkmarks-admin' );
                wp_enqueue_style( 'post-bkmarks-admin' );
                
            }
        }
    }
    
    function enqueue_scripts_styles(){
        wp_register_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css',false,'4.3.0');
        wp_register_style( 'post-bkmarks',  $this->plugin_url . '_inc/css/post_bkmarks.css',false,$this->version );
        
        
        wp_enqueue_style( 'post-bkmarks' );
        
    }

    function exclude_from_bookmarks($bookmarks,$r){
        $hide_from_bookmarks = ( post_bkmarks()->get_options('hide_from_bookmarks') == "on" ) ? true : false;
        if (!$hide_from_bookmarks) return $bookmarks;
        
        remove_filter( 'get_bookmarks', array(&$this,'exclude_from_bookmarks'),10,2); //unhook to avoid infinite loop

        $r['post_bkmarks'] = false;
        $bookmarks = get_bookmarks($r);
        
        add_filter( 'get_bookmarks', array(&$this,'exclude_from_bookmarks'),10,2); //rehook

        return $bookmarks;
        
    }
    
    function filter_bookmarks($bookmarks,$r){

        if ( isset($r['post_bkmarks']) ){
            
            $do_include = (bool)$r['post_bkmarks'];
            $pbkm_category = $this->get_links_category();
            $args_categories = array();

            // category already set, abord
            if ( isset($r['category']) ){
                if ( $args_categories = explode(',',$r['category']) ){
                    if (in_array($pbkm_category,$args_categories)) return $bookmarks; //already there, abord
                }
            }
            
            switch( $do_include ){

                case true: //include only our links

                    $args_categories[] = $pbkm_category;
                    $r['category'] = implode(',',$args_categories);

                break;

                case false: //exclude our links

                    //re-run query.
                    //there is no 'exclude_category' parameter, so exclude all links IDs
                    if ( $pbkm_links = get_bookmarks( array('post_bkmarks' => true) ) ){
                        $pbkm_links_ids = array();
                        foreach((array)$pbkm_links as $link){
                            $pbkm_links_ids[] = $link->link_id;
                        }

                        $r['exclude'] = implode(',',$pbkm_links_ids);
                    }

                break;
            }

            unset($r['post_bkmarks']);
            $bookmarks = get_bookmarks($r);

        }

        return $bookmarks;
    }
    
    function metabox_variables_redirect($location) {

        if ($this->filter_links_text){
            $location = add_query_arg( 
                array(
                    'pbkm_filter'   => $this->filter_links_text
                ),
                $location 
            );
        }
        
        if ($this->links_tab){
            $location = add_query_arg( 
                array(
                    'pbkm_tab'   => $this->links_tab
                ),
                $location 
            );
        }
        
        return $location;
    }
   
    
    function metabox_add(){
        $post_types = $this->allowed_post_types();
        
        $title = __('Post Bookmarks','post-bkmarks');

        foreach ( $post_types as $post_type ) {
            add_meta_box( 'post-bookmarks', $title,array($this,'metabox_content'),$post_type, 'normal', 'high' );
        }
        
    }

    function metabox_content( $post ){

        //attached links
        
        $links_table = new Post_Bookmarks_List_Table();
        $links_table->items = $links_table->get_tab_links();
        ?>
        <!--current links list-->
        <div class="pbkm-metabox-section" id="list-links-section">
            <?php
                $links_table->prepare_items();
                $links_table->search_box( __( 'Filter links', 'post-bkmarks' ), 'pbkm_filter' );
                $links_table->append_blank_row();
                $links_table->views();
                $links_table->display();
            ?>
        </div>
        <!--search links-->
        <?php

      // Add an nonce field so we can check for it later.
      wp_nonce_field( 'post_bkmarks_meta_box', 'post_bkmarks_meta_box_nonce' );

    }

    /**
    * When the post is saved, saves our custom data.
    *
    * @param int $post_id The ID of the post being saved.
    */
    function metabox_save( $post_id ) {

        /*
        * We need to verify this came from our screen and with proper authorization,
        * because the save_post action can be triggered at other times.
        */

        // Check if our nonce is set.
        if ( ! isset( $_POST['post_bkmarks_meta_box_nonce'] ) ) return;

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['post_bkmarks_meta_box_nonce'], 'post_bkmarks_meta_box' ) ) return;

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // Check the user's permissions.
        
        if ( ! current_user_can( 'manage_links' ) ) return;//user cannot edit links
        
        if ( isset( $_REQUEST['post_type'] ) ){
            
            if ( !in_array( $_REQUEST['post_type'], $this->allowed_post_types() ) ) return;//is not allowed post type
            
            //TO FIX TO CHECK
            if ( 'page' == $_REQUEST['post_type'] ){
                if ( !current_user_can( 'edit_page', $post_id ) ) return;//user cannot edit page
            }else{
                if ( !current_user_can( 'edit_post', $post_id ) ) return;//user cannot edit post
            }

        }
        
        /* OK, its safe for us to save the data now. */
        $form_data = ( isset($_POST['post_bkmarks']) ) ? $_POST['post_bkmarks'] : null;
        $link_ids = array();

        $form_data_links = (isset($form_data['links'])) ? $form_data['links'] : array();
        $form_data_links = stripslashes_deep($form_data_links); //strip slashes for $_POST args if any
        
        /* get links IDs from database */
        $link_ids = get_post_meta( $post_id, '_post_bkmarks_ids', true );

        foreach($form_data_links as $form_data_link){
            
            if ( !isset($form_data_link['selected']) ) continue;
            
            $link_id = null;
            $link_data = $this->sanitize_link($form_data_link);

            $link_data = apply_filters('post_bkmarks_before_save_data',$link_data,$form_data_link);

            //existing links
            if ($link_id = $link_data['link_id']){

                //get stored bookmark
                $bookmark = get_bookmark($link_id,ARRAY_A);

                //compare keys that are shared by both arrays
                $link_data_reduced = array_intersect_key($link_data, $bookmark); //keep only keys shared in both arrays - values are from the first one
                $bookmark_reduced = array_intersect_key($bookmark, $link_data);

                /*
                print_r($link_data_reduced);
                print_r('<br/>VS<br/>');
                print_r($bookmark_reduced);
                die();
                */

                //update link only if data has been changed
                if ($link_data_reduced != $bookmark_reduced){
                    wp_update_link( $link_data );
                }

            }else{ //create new link
                $link_id = $this->insert_link($link_data);
            }

            //add to IDs list
            if ($link_id && !is_wp_error($link_id)){
                $link_ids[] = $link_id;
            }
        }
        
        $link_ids = array_unique((array)$link_ids);
        update_post_meta( $post_id, '_post_bkmarks_ids', $link_ids );
        return $link_ids;

    }
    
    function insert_link($new_link){

        //sanitize
        $linkdata = $this->sanitize_link($new_link);

        if ( !$linkdata['link_url']) return new WP_Error( 'missing_required',__('A name and url are required for each link','post-bookmarks') );

        //force name
        if ( !$linkdata['link_name'] ){
            $linkdata['link_name'] = post_bkmarks_get_name_from_url($linkdata['link_url']);
        }

        //TO FIX check url is valid
        if ( !$link_id = post_bkmarks_get_existing_link_id($linkdata['link_url'],$linkdata['link_name']) ){ //check the link does not exists yet
            if( !function_exists( 'wp_insert_link' ) ) include_once( ABSPATH . '/wp-admin/includes/bookmark.php' );
            
            $linkdata = apply_filters('post_bkmarks_insert_link_pre',$linkdata);

            $link_id = wp_insert_link( $linkdata, true ); //return id
        }
        
        return $link_id;
    }
    
    function sanitize_link($args = array()){
        $defaults = array(
            'link_id'       => 0,
            'link_url'      => null,
            'link_name'     => null,
            'link_target'   => null,
            'link_category' => (array)$this->get_links_category(),
            'row_checked'   => false,
            'row_classes'   => null, //class for the row, in the links table. eg. 'post-bkmarks-row-edit post-bkmarks-row-new post-bkmarks-row-suggest'
        );

        $args = wp_parse_args((array)$args,$defaults);
        
        //validating, sanitizing
        $args['link_id'] = intval( $args['link_id'] );
        $args['link_url'] = sanitize_text_field($args['link_url']);
        $args['link_name'] = sanitize_text_field($args['link_name']);
        $args['link_target'] = sanitize_text_field($args['link_target']);
        
        foreach((array)$args['link_category'] as $key=>$cat){
            $args['link_category'][$key] = intval($cat);
        }
        //force default category
        $default_category = $this->get_links_category();
        if ( !in_array($default_category,$args['link_category']) ){
            $args['link_category'][] = $default_category;
        }
        $args['link_category'] = array_filter($args['link_category']);

        return $args;
    }
    
}

function post_bkmarks() {
	return Post_Bookmarks::instance();
}

post_bkmarks();
















