<?php
/*
Plugin Name: Post Bookmarks
Description: Adds a new metabox to the editor, allowing you to attach a set of related links to any post
Plugin URI: https://github.com/gordielachance/post-bookmarks
Author: G.Breant
Author URI: https://profiles.wordpress.org/grosbouff/#content-plugins
Version: 2.1.7
License: GPL2
*/

class Post_Bookmarks {
    /** Version ***************************************************************/
    /**
    * @public string plugin version
    */
    public $version = '2.1.7';
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

    static $settings_optionkey = 'post_bkmarks-options';
    static $link_ids_metakey = '_post_bkmarks_ids';

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
        $this->links_tab = ( isset($_REQUEST['pbkm_tab'] ) ) ? $_REQUEST['pbkm_tab'] : 'attached'; //links tab selected backend

        $this->options_default = array(
            'ignored_post_type'     => array('attachment','revision','nav_menu_item'),
            'display_links'         => 'after',
            'default_target'        => '_blank',
            'links_orderby'         => 'name',
            'ignore_target_local'   => 'on',
            'get_favicon'           => 'on'
        );
        $this->options = wp_parse_args(get_option( self::$settings_optionkey), $this->options_default);

        //search links
        $this->filter_links_text = ( isset($_REQUEST['pbkm_filter']) ) ? $_REQUEST['pbkm_filter'] : null; //existing links to attach to post
        
        //is a post update, do loose our search term
        if ( isset($_GET['message']) && ($_GET['message'] == '1') ){
            $this->filter_links_text = null;
        }
        

        
    }
    function includes(){
        
        require_once( $this->plugin_dir . 'post_bkmarks-admin-table.php' );
        require_once( $this->plugin_dir . 'post_bkmarks-templates.php' );
        require_once( $this->plugin_dir . 'post_bkmarks-functions.php' );
        require_once( $this->plugin_dir . 'post_bkmarks-settings.php' );
        require_once( $this->plugin_dir . 'post_bkmarks-ajax.php' );
        
    }
    function setup_actions(){  

        add_filter( 'pre_option_link_manager_enabled', '__return_true' ); //enable Link Manager plugin

        add_action( 'plugins_loaded', array($this, 'upgrade'));
        
        add_action( 'admin_init', array($this,'load_textdomain'));
        add_action( 'admin_init', array( $this, 'register_scripts_styles_admin' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles_admin' ) );
        
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
        
        add_action( 'add_meta_boxes', array($this, 'metabox_add'));
        add_action( 'save_post', array($this,'save_bulk_action'));

        add_filter( 'the_content', array($this,'add_links_to_post_content'), 99, 2);
        
        add_filter( 'get_bookmarks', array($this,'exclude_posts_bookmarks'),10,2);
        add_filter( 'get_bookmarks', array($this,'filter_bookmarks_for_post'),10,2);
        
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
                    self::$settings_optionkey,
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
                    self::$link_ids_metakey,
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
        
        /* 
        URI.js
        https://github.com/medialize/URI.js
        required to check / validate / work with URIs within jQuery
        */
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
                
                //localize vars
                $localize_vars=array(
                    'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                    'debug'     => (WP_DEBUG)
                );
                wp_localize_script('post-bkmarks-admin','post_bkmarks_L10n', $localize_vars);
                
                wp_enqueue_script( 'post-bkmarks-admin' );
                wp_enqueue_style( 'post-bkmarks-admin' );
                
            }
        }
    }
    
    function enqueue_scripts_styles(){
        wp_register_style( 'post-bkmarks',  $this->plugin_url . '_inc/css/post_bkmarks.css',false,$this->version );
        wp_enqueue_style( 'post-bkmarks' );
        
    }

    /**
    Exclude all posts bookmarks from the bookmarks query
    **/
    
    function exclude_posts_bookmarks($bookmarks,$r){

        if ( !isset($r['post_bkmarks_exclude']) ) return $bookmarks;

        $pbkm_category = $this->get_links_category();
        
        //there is no 'exclude_category' parameter, so exclude all links IDs from the post bookmarks category

        if ( $pbkm_links = get_bookmarks( array('category' => $pbkm_category) ) ){
            $pbkm_links_ids = array();
            foreach((array)$pbkm_links as $link){
                $pbkm_links_ids[] = $link->link_id;
            }

            $r['exclude'] = implode(',',$pbkm_links_ids);
            unset($r['post_bkmarks_exclude']); //avoid infinite loop
            $bookmarks = get_bookmarks($r);
            
        }
        
        return $bookmarks;


    }
    
    /**
    Filter the bookmarks attached to a post
    **/
    
    function filter_bookmarks_for_post($bookmarks,$r){

        if ( !isset($r['post_bkmarks_for_post']) ) return $bookmarks;
        
        $post_bookmarks = array();
        $post_id = $r['post_bkmarks_for_post'];
        
        if ( !$pbkm_links_ids = self::get_post_link_ids($post_id) ) return array();
        
        unset($r['post_bkmarks_for_post']); //avoid infinite loop
        $bookmarks = get_bookmarks($r);
        
        /*
        https://codex.wordpress.org/Function_Reference/get_bookmarks
        If the include string is used, the category, category_name, and exclude parameters are ignored
        So filter the links AFTER the query.
        */
        
        foreach((array)$bookmarks as $bookmark){
            if ( !in_array($bookmark->link_id,$pbkm_links_ids) ) continue;
            $post_bookmarks[] = $bookmark;
        }

        //sort custom
        
        if ($r['orderby'] == 'custom'){
            $link_ids = self::get_post_link_ids($post_id);
            $post_bookmarks = post_bkmarks_sort_using_ids_array($post_bookmarks,$link_ids);
        }

        return $post_bookmarks;
        
    }
    
    /*
     * the_content filter to append custom post links to the post content
     */
    function add_links_to_post_content( $content ){
        global $post;

        if ( !in_array( $post->post_type, $this->allowed_post_types() ) ) return $content;

        $option = $this->get_options('display_links');
        $links = self::get_link_list($post->ID);
        $title = sprintf('<h3 class="post-bkmarks-section-title">%s</h3>',__('Related links','post-bkmarks'));
        
        $append = sprintf('<section class="post-bkmarks-section">%s</section>',$title.$links);

        switch($option){
            case 'before':
                $content = $append."\n".$content;
            break;
            case 'after':
                $content.= "\n".$append;
            break;
        }

        return $content;
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
        
        if ($this->links_tab != 'attached'){
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
            add_meta_box( 'post-bookmarks', $title,array($this,'metabox_content'),$post_type, 'normal', 'low' );
        }
        
    }

    function metabox_content( $post ){

        //attached links
        $links_table = new Post_Bookmarks_List_Table();
        
        $links_table->items = self::get_tab_links();
        $links_table->prepare_items();

        $classes = array('metabox-table-tab','post-bookmarks-output-admin');
        $classes[] = sprintf('metabox-table-tab-%s',$this->links_tab);

        ?>
        <!--current links list-->
        <div id="post-bkmarks-list" <?php post_bkmarks_classes_attr($classes);?> data-post-bkmarks-post-id="<?php echo $post->ID;?>">
            <?php
                settings_errors('post_bkmarks');

                $links_table->search_box( __( 'Filter links', 'post-bkmarks' ), 'pbkm_filter' );
                $links_table->append_blank_row();
                $links_table->views();
                $links_table->display();
            ?>
        </div>
        <!--search links-->
        <?php
        // Add an nonce field so we can check for it later.
        wp_nonce_field( 'post_bkmarks_bulk', 'post_bkmarks_bulk_nonce' );
    }

    /**
    * When the post is saved, saves our custom data.
    *
    * @param int $post_id The ID of the post being saved.
    */

    function save_bulk_action( $post_id ) {

        /*
        * We need to verify this came from our screen and with proper authorization,
        * because the save_post action can be triggered at other times.
        */

        // Check if our nonce is set.
        if ( ! isset( $_POST['post_bkmarks_bulk_nonce'] ) ) return;

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['post_bkmarks_bulk_nonce'], 'post_bkmarks_bulk' ) ) return;

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // get links data from form
        $form_data = ( isset($_POST['post_bkmarks']) ) ? $_POST['post_bkmarks'] : null;
        $form_links = (isset($form_data['links'])) ? $form_data['links'] : array();
        if ( empty($form_links) ) return;
        
        $bulk_action = $this->metabox_table_get_current_action();
        if (!$bulk_action) return;
        
        //strip slashes for $_POST args if any
        $form_links = stripslashes_deep($form_links); 

        //keep only the checked links
        $checked_links = array_filter(
            $form_links,
            function ($link){
            return ( isset($link['selected']) );
            }
        );
        
        //Links order
        usort($form_links, "post_bkmarks_sort_links_by_order");

        if (!$checked_links) return;
        
        //do it !
        foreach( $checked_links as $link ){
            $this->do_single_post_bookmark_action($post_id,$link,$bulk_action);
        }

    }
    //TO FIX TO REMOVE
    function update_links_order($post_id,$form_links){
        $post_link_db_ids = self::get_post_link_ids($post_id);
        $post_link_ids = array();
        foreach((array)$form_links as $link){
            if (!$link['link_id']) continue;
            if (!in_array($link['link_id'],$post_link_db_ids)) continue;
            $post_link_ids[] = $link['link_id'];
        }
        
        if ($post_link_ids != $post_link_db_ids){
            update_post_meta( $post_id, self::$link_ids_metakey, $post_link_ids );
        }
    }
    
    function do_single_post_bookmark_action($post_id,$link,$action){

        //check the posts exists
        $post_type = get_post_type($post_id);
        if (!$post_type) return;

        if ( !in_array( $post_type, $this->allowed_post_types() ) ) return;//is not allowed post type
        
        $this->debug_log(array('post_id'=>$post_id,'link'=>json_encode($link),'action'=>$action),"do_single_post_bookmark_action()");
        
        $link = $this->sanitize_link($link);
        
        // get existing links IDs for post
        $post_link_ids = (array)self::get_post_link_ids($post_id);
        
        switch($action){
                
            case 'attach':
                
                if ( !current_user_can( 'edit_post' , $post_id ) ) return false;
                
                $link_id = ( isset($link['link_id']) ) ? $link['link_id'] : null;
                
                if ( !$link_id ) { //link does not exists, save it first
                    $link_id = $this->do_single_post_bookmark_action($post_id,$link,'save');
                }
                
                if ( !$link_id ) return false;
                if ( in_array($link_id,$post_link_ids) ) return true;
                
                $post_link_ids[] = $link_id;
                if ( update_post_meta( $post_id, self::$link_ids_metakey, $post_link_ids ) ){
                    return $link_id;
                }

            break;

            case 'remove':
                
                if ( !current_user_can( 'edit_post' , $post_id ) ) return false;
                if ( !isset($link['link_id']) ) return false;
                if ( !in_array($link['link_id'],$post_link_ids) ) return true;

                //TO FIX : remove 'post bookmarks' link category if it only belongs to this post
                
                $post_link_ids = array_diff( $post_link_ids, array($link['link_id']) );
                return update_post_meta( $post_id, self::$link_ids_metakey, $post_link_ids );
                
            case 'save':
                
                if ( !current_user_can( 'manage_links' ) ) return false;
                return $this->save_link($link,$post_id); //returns an ID

            break;
                
            case 'delete':
                
                if ( !current_user_can( 'manage_links' ) ) return false;
                
                if ( $link['link_id']){
                    if ( wp_delete_link($link['link_id']) ){
                        $post_link_ids = array_diff( (array)$post_link_ids, array($link['link_id']) );
                        return update_post_meta( $post_id, self::$link_ids_metakey, $post_link_ids );
                    }
                }else{
                    return true;
                }

            break;
        }
        
        return false;
        
    }
    
	/**
	 * Get the current action selected from the bulk actions dropdown.
	 *
	 * @return string|false The action name or False if no action was selected
	 */
	public function metabox_table_get_current_action() {
        //TO FIX TO CHECK
		if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
			return false;

		if ( isset( $_REQUEST['post-bkmarks-action'] ) && -1 != $_REQUEST['post-bkmarks-action'] )
			return $_REQUEST['post-bkmarks-action'];

		if ( isset( $_REQUEST['post-bkmarks-action2'] ) && -1 != $_REQUEST['post-bkmarks-action2'] )
			return $_REQUEST['post-bkmarks-action2'];

		return false;
	}
    

    function save_link($link,$post_id){

        //sanitize
        $link = $this->sanitize_link($link);

        if ( !$link['link_url']) return new WP_Error( 'missing_required',__('A name and url are required for each link','post-bookmarks') );

        //force name
        if ( !$link['link_name'] ){
            $link['link_name'] = post_bkmarks_get_name_from_url($link['link_url']);
        }

        if (!$link['link_id']){ //new link
            
            //check this bookmark (url + name) does not exists already
            if ( $link_id = post_bkmarks_get_existing_link_id($link['link_url'],$link['link_name']) ){
                $link['link_id'] = $link_id;
                
                //will update the link
                return $this->save_link($link,$post_id); 
                
            }else{
                if( !function_exists( 'wp_insert_link' ) ) include_once( ABSPATH . '/wp-admin/includes/bookmark.php' );
                $link = apply_filters('post_bkmarks_add_link_pre',$link);
                $link_id = wp_insert_link( $link, true ); //return id
                $this->debug_log(array('post_id'=>$post_id,'link_id'=>$link_id,'link'=>json_encode($link)),"save_link() : inserted link");
                
            }
            
        }else{ //update link
            $link = apply_filters('post_bkmarks_update_link_pre',$link);
            $link_id = wp_update_link( $link );
            $this->debug_log(array('post_id'=>$post_id,'link_id'=>$link_id,'link'=>json_encode($link)),"save_link() : updated link");
            
        }

        return $link_id;
    }
    
    function sanitize_link($args = array()){
        $defaults = array(
            'link_id'       => 0,
            'link_url'      => null,
            'link_name'     => null,
            'link_target'   => null,
            'link_category' => $this->get_links_category(),
            'row_classes'   => array(), //classes for the row
        );

        $args = wp_parse_args((array)$args,$defaults);
        
        //validating, sanitizing
        $args['link_id'] = intval( $args['link_id'] );
        $args['link_url'] = esc_url($args['link_url']);
        $args['link_name'] = sanitize_text_field($args['link_name']);
        $args['link_target'] = sanitize_text_field($args['link_target']);
        
        $args['link_category'] = (array)$args['link_category'];
        foreach($args['link_category'] as $key=>$cat){
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
    
    public function debug_log($message,$title = null) {

        if (WP_DEBUG_LOG !== true) return false;

        $prefix = '[post_bkmarks] ';
        if($title) $prefix.=$title.': ';

        if (is_array($message) || is_object($message)) {
            error_log($prefix.print_r($message, true));
        } else {
            error_log($prefix.$message);
        }
    }
    /*
     * Generate the list of links for this post
     */
    static function get_link_list($post_id = null, $args = null){
        global $post;

        if (!$post_id) $post_id = $post->ID;
        if (!$post_id) return false;

        $links_html = null;
        $title_el = null;
        $blogroll = array();

        if ( $post_bkmarks = self::get_post_links($post_id,$args) ){

            foreach ((array)$post_bkmarks as $link){

                $link_html = self::get_link_html($link);
                $link_html = sprintf('<li><i class="fa fa-link" aria-hidden="true"></i>%s</li>',$link_html);
                $blogroll[] = $link_html;

            }

            $blogroll_str = implode("\n",$blogroll);


            if ($blogroll_str) {

                $list_html = sprintf('<ul class="post-bookmarks-list">%2s</ul>',$blogroll_str);
                $links_html = sprintf('<div class="post-bookmarks-output-list" data-post-bkmarks-post-id="%s">%s%s</div>',$post_id,$title_el,$list_html);
            }

        }

        return $links_html;

    }

    /*
     * Get the links attached to a post.
     * $args should be an array with the same parameters you would set while using the native get_bookmarks() function.
     * https://codex.wordpress.org/Function_Reference/get_bookmarks
     */

    static function get_post_links($post_id = null, $args = null){
        if (!$post_id) $post_id = $post->ID;
        if (!$post_id) return false;

        $post_args = array(
            'post_bkmarks_for_post'=>$post_id,
            'orderby' => post_bkmarks()->get_options('links_orderby')
        );

        if ($args){
            $post_args = wp_parse_args($post_args,$args); //priority to the post args
        }

        return get_bookmarks($post_args);

    }

    /*
     * Get the list of link IDs attached to a post.
     */

    static function get_post_link_ids($post_id = null){
        global $post;
        if (!$post_id) $post_id = $post->ID;
        if ( $meta = get_post_meta( $post_id, self::$link_ids_metakey, true ) ){
            return array_unique((array)$meta);
        }
        return false;
    }

    /*
     * Template a single link
     */

    static function get_favicon($url){

        $favicon = null;

        //get domain url
        if ( $domain = post_bkmarks_get_url_domain($url) && (post_bkmarks()->get_options('get_favicon')=='on') ){
            //favicon
            $favicon = sprintf('https://www.google.com/s2/favicons?domain=%s',$url);
            $favicon_style = sprintf(' style="background-image:url(\'%s\')"',$favicon);
            $favicon = sprintf('<span class="post-bkmarks-favicon" %s></span>',$favicon_style);
        }

        return $favicon;
    }

    /*
     * Generate the single link output
     */
    static function get_link_html($link){

        $link_classes_arr = array(
            'post-bkmark',
            'post-bkmark-' . $link->link_id,
        );
        $link_classes_arr = apply_filters('post_bkmarks_single_link_classes',$link_classes_arr,$link);
        $link_classes = post_bkmarks_get_classes_attr($link_classes_arr);

        $link_target_str=null;
        $favicon = self::get_favicon($link->link_url);
        $domain = post_bkmarks_get_url_domain($link->link_url);

        if($link->link_target) {
            if ( (post_bkmarks()->get_options('ignore_target_local')=='on') && post_bkmarks_is_local_url($link->link_url) ){
                //nix
            }else{
                $link_target_str = sprintf('target="%s"',esc_attr($link->link_target) );
            }

        }

        $link_html = sprintf('<a %s href="%s" %s data-cp-link-domain="%s">%s%s</a>',
            $link_classes,
            esc_url($link->link_url),
            $link_target_str,
            esc_attr($domain),
            $favicon,
            esc_html($link->link_name)
         );

        return apply_filters('post_bkmarks_single_link_html',$link_html,$link);

    }

    static function get_tab_links($tab = null){
        global $post;
        $links = array();

        //current tab
        if (!$tab) $tab = post_bkmarks()->links_tab;

        $args = array();

        //search filter
        if ( $search = strtolower(post_bkmarks()->filter_links_text) ){
            $args['search'] = $search;
        }

        $args = apply_filters('post_bkmarks_tab_links_args',$args,$tab,$post->ID);

        //attached to the post
        if ($tab == 'library'){
            $links = get_bookmarks( $args );
        }else{
            $links = self::get_post_links($post->ID,$args);
        }

        $links = apply_filters('self::get_tab_links',$links,$tab,$post->ID);

        //sanitize links
        foreach ($links as $key=>$link){
            $links[$key] = (object)post_bkmarks()->sanitize_link($link);
        }

        return $links;
    }

}

function post_bkmarks() {
	return Post_Bookmarks::instance();
}

post_bkmarks();