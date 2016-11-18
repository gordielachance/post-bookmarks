<?php
/*
Plugin Name: Custom Post Links
Description: Adds a new metabox to the editor, allowing you to attach a set of related links to any post
Plugin URI: https://github.com/gordielachance/custom-post-links
Author: G.Breant
Author URI: https://profiles.wordpress.org/grosbouff/#content-plugins
Version: 2.0.6
License: GPL2
*/

class CP_Links {
    /** Version ***************************************************************/
    /**
    * @public string plugin version
    */
    public $version = '2.0.6';
    /**
    * @public string plugin DB version
    */
    public $db_version = '202';
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

    static $meta_name_options = 'cp_links_options';
    
    static $links_category_slug = 'cp-links';
    
    var $search_links_text = null;
    
    public static function instance() {
        
            if ( ! isset( self::$instance ) ) {
                    self::$instance = new CP_Links;
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
        $this->links_category_name = __('Post Links','cp_links');
        
        
        $this->options_default = array(
            'ignored_post_type'     => array('attachment','revision','nav_menu_item'),
            'links_category'        => null,
            'display_links'         => 'after',
            'default_target'        => '_blank',
            'ignore_target_local'   => 'on',
            'links_orderby'         => 'name',
            'get_favicon'           => 'on'
        );
        $this->options = wp_parse_args(get_option( self::$meta_name_options), $this->options_default);
        
        //parent category
        if ( $parent_cat = get_term_by( 'slug', self::$links_category_slug, 'link_category') ){
            $this->options['links_category'] = (int)$parent_cat->term_id;
        }
        
        //search links
        $this->search_links_text = ( isset($_GET['cp_links_search']) ) ? $_GET['cp_links_search'] : null; //existing links to attach to post
        
        //is a post update, do loose our search term
        if ( isset($_GET['message']) && ($_GET['message'] == '1') ){
            $this->search_links_text = null;
        }
        

        
    }
    function includes(){
        
        require $this->plugin_dir . 'cp_links-table.php';
        require $this->plugin_dir . 'cp_links-templates.php';
        require $this->plugin_dir . 'cp_links-functions.php';
        require $this->plugin_dir . 'cp_links-settings.php';
        require $this->plugin_dir . 'cp_links-ajax.php';
        
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
        
        add_filter('the_content', 'cp_links_output_links', 100, 2);
        
        add_filter('redirect_post_location',array($this, 'metabox_variables_redirect')); //redirect with searched links text - http://wordpress.stackexchange.com/a/52052/70449
        
        


    }
    
    function load_textdomain() {
        load_plugin_textdomain( 'cp_links', false, $this->plugin_dir . '/languages' );
    }
    
    function upgrade_notice(){

        $link = add_query_arg(array('cpl_do_import_old_links'=>true),admin_url('options-general.php?page=cpl_settings'));
        
    ?>
    <div class="notice notice-success is-dismissible">
        <p>
            <?php printf( 
        __('Click %s to import the links from the %s plugin. %s', 'custom-post-links' ),
        sprintf(__('<a href="%s">here</a>','custom-post-links'),$link),
        sprintf('<a href="https://github.com/daggerhart/custom-post-links" target="_blank">Custom Post Links 1.0 (daggerhart)</a>',$link),
        '<small>'.__("They will be moved to the new plugin's architecture.",'custom-post-links').'</small>'
        ); ?>
        </p>
    </div>
    <?php
    }
    

    
    function upgrade(){
        global $wpdb;
        

        //old plugin import
        $old_metas = cp_links_get_metas('_custom_post_links');
        if ($old_metas){
            add_action( 'admin_notices', array(&$this,'upgrade_notice'));

            if (isset($_GET['cpl_do_import_old_links'])){
                
                foreach((array)$old_metas as $meta){
                    
                    $old_links = maybe_unserialize($meta->meta_value);

                    $cp_links_ids = array();
                    
                    foreach((array)$old_links as $old_link){
                        
                        $linkdata = array(
                            'link_name'     => ( isset($old_link['title']) ) ? $old_link['title'] : null,
                            'link_url'      => ( isset($old_link['url']) ) ? $old_link['url'] : null,
                            'link_target'      => ( isset($old_link['new_window']) ) ? '_blank' : null
                        );
                        
                        //ignore target
                        /*
                        if( isset($linkdata['link_target']) && (cp_links()->get_options('ignore_target_local') == 'on') && cp_links_is_local_url($linkdata['link_url']) ){
                            unset($linkdata['link_target']);
                        }
                        */

                        if ( ($link_id = $this->insert_link($linkdata)) && !is_wp_error($link_id)){
                            $cp_links_ids[] = $link_id;
                        }
                        
                    }
                }
                
                if ( update_post_meta( $meta->post_id, '_custom_post_links_ids', array_unique($cp_links_ids) ) ){
                    delete_post_meta( $meta->post_id, '_custom_post_links'); //old plugin
                }
                
            }
            
        }
        

        $current_version = get_option("_cp_links-db_version");
        if ($current_version==$this->db_version) return false;
        if(!$current_version){ //not installed
            
            //add default category

            if ( !$parent_cat = get_term_by( 'slug', self::$links_category_slug, 'link_category') ){
                $cat_id = wp_insert_term( 
                    __('Custom Post Links','cp_links'), 
                    'link_category',
                     array(
                         'description'  =>__('Parent category for all the links added using the <em>Custom Post Links</em> plugin','cp_links'),
                         'slug'         => self::$links_category_slug
                     ) 
                );
            }
            
            
            /*
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
             */

            
        }else{

        }
        //update DB version
        update_option("_cp_links-db_version", $this->db_version );
    }
    
    function get_options($keys = null){
        return cp_links_get_array_value($keys,$this->options);
    }
    
    public function get_default_option($keys = null){
        return cp_links_get_array_value($keys,$this->options_default);
    }
    
    /* get post types that supports Custom Post Links */
    
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
        // css
        wp_register_style( 'cp_links_admin',  $this->plugin_url . '_inc/css/cp_links-admin.css',$this->version );
        // js
        wp_register_script( 'cp_links_admin', $this->plugin_url . '_inc/js/cp_links_admin.js', array('jquery-core', 'jquery-ui-core', 'jquery-ui-sortable'),$this->version);
    }
    

    
    /*
     * enqueue admin scripts
     * https://pippinsplugins.com/loading-scripts-correctly-in-the-wordpress-admin/
     */
    function enqueue_scripts_styles_admin( $hook ){

        $screen = get_current_screen();
        
        if( is_object( $screen ) ) {
            if( ( in_array($hook, array('post.php', 'post-new.php','edit.php') ) &&  in_array($screen->id, $this->allowed_post_types() ) ) || ( $screen->base == 'settings_page_cpl_settings') ) {
                wp_enqueue_script( 'cp_links_admin' );
                wp_enqueue_style( 'cp_links_admin' );
                
            }
        }
    }
    
    function enqueue_scripts_styles(){
        wp_register_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css',false,'4.3.0');
        wp_register_style( 'cp_links',  $this->plugin_url . '_inc/css/cp_links.css',false,$this->version );
        
        
        wp_enqueue_style( 'cp_links' );
        
    }
    
    function metabox_variables_redirect($location) {
        
        $form_data = ( isset($_POST['custom_post_links']) ) ? $_POST['custom_post_links'] : null;
        
        //search links
        $this->search_links_text = ( isset($form_data['search']) ) ? $form_data['search'] : null; //existing links to attach to post
        
        if ($this->search_links_text){
            $location = add_query_arg( 
                array(
                    'cp_links_search'   => $this->search_links_text
                ),
                $location 
            );
        }
        
        return $location;
    }
   
    
    function metabox_add(){
        $post_types = $this->allowed_post_types();
        
        $title = __('Custom Post Links','cp_links');

        foreach ( $post_types as $post_type ) {
            add_meta_box( 'custom-post-links', $title,array($this,'metabox_content'),$post_type, 'normal', 'high' );
        }
        
    }

    function metabox_content( $post ){

        $display_links = array();
        
        //attached links

        $display_links = array_merge((array)cp_links_get_for_post($post->ID),$display_links);
        
        $links_table = new CP_Links_List_Table();
        $links_table->items = $display_links;
        
        $links_search_table = new CP_Links_List_Table();
        
        if ($this->search_links_text){
            $search_links_args = array(
                'search'    => $this->search_links_text,
                //'category'  => cp_links()->get_options('links_category')
            );

            $links_search_table->items = get_bookmarks( $search_links_args );
        }

        
        
        //add link
        if ( current_user_can( 'manage_links' ) ){
            ?>
            <div class="cpl-metabox-section" id="add-link-section">
                <h4><?php _e('Add Link');?></h4>
                <table>
                    <?php $this->add_link_row();?>
                </table>
                <?php
                if ( current_user_can( 'manage_links' ) ) {
                    ?>
                    <p><input type="submit" id="cp_links_add_new" class="button" value="<?php _e("Add Link");?>"></input></p>
                    <?php
                }
                ?>
            </div>
            <?php
        }

        ?>
        <!--current links list-->
        <div class="cpl-metabox-section" id="list-links-section">
            <?php
                $links_table->prepare_items();
                $links_table->display();
            ?>
        </div>
        <!--search links-->
        <?php
            $search_section_classes=array('cpl-metabox-section');
            if($this->search_links_text){
                $search_section_classes[]='has-search-term';
            }
        ?>
        <div<?php cp_links_classes($search_section_classes);?> id="search-links-section">
            <h4><?php _e('Search for existing links','cp_links');?></h4>
            <div id="search-links-form">
                <label class="screen-reader-text" for="link-search-input"></label>
                <input type="search" id="link-search-input" name="custom_post_links[search]" value="<?php echo $this->search_links_text;?>">
                <input type="submit" id="search-submit" class="button" value="<?php _e('Search Links');?>">
            </div>
            <?php
                $links_search_table->prepare_items();
                $links_search_table->display();
            ?>
        </div>
        <?php 
      // Add an nonce field so we can check for it later.
      wp_nonce_field( 'custom_post_links_meta_box', 'custom_post_links_meta_box_nonce' );

    }
    
    /*
    This should act like a line from the CP_Links_List_Table.
    We'll use jQuery to dynamically move it into the table.
    */
    
    function add_link_row(){
        $option_target = cp_links()->get_options('default_target');
        ?>
        <tr class="cp_links_new">
            <th scope="row" class="check-column"></th>
            <td class="reorder column-reorder has-row-actions column-primary" data-colname=""></td>
            <td class="column-favicon"></td>
            <td class="url column-url">
                <label><?php _e('URL');?></label>
                <input type="text" name="custom_post_links[new][url][]" value="" />
            </td>
            <td class="name column-name has-row-actions column-primary">
                <label><?php _e('Name');?></label>
                <input type="text" name="custom_post_links[new][name][]" value="" />
            </td>
            <td class="target column-target">
                <label><?php _e('Target');?></label>
                <input id="link_target_blank" type="checkbox" name="custom_post_links[new][target][]" value="_blank" <?php checked( $option_target, '_blank');?>/>
                <small><?php _e('<code>_blank</code> &mdash; new window or tab.'); ?></small>
            </td>
        </tr>
        <?php
    }


    /**
    * When the post is saved, saves our custom data.
    *
    * @param int $post_id The ID of the post being saved.
    */
    function metabox_save( $post_id ) {
        
        $form_data = ( isset($_POST['custom_post_links']) ) ? $_POST['custom_post_links'] : null;


        /*
        * We need to verify this came from our screen and with proper authorization,
        * because the save_post action can be triggered at other times.
        */

        // Check if our nonce is set.
        if ( ! isset( $_POST['custom_post_links_meta_box_nonce'] ) ) return;

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['custom_post_links_meta_box_nonce'], 'custom_post_links_meta_box' ) ) return;

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // Check the user's permissions.
        
        if ( ! current_user_can( 'manage_links' ) ) return;//user cannot edit links
        
        if ( isset( $_REQUEST['post_type'] ) ){
            
            if ( !in_array( $_REQUEST['post_type'], $this->allowed_post_types() ) ) return;//is not allowed post type
            
            if ( 'page' == $_REQUEST['post_type'] ){
                if ( !current_user_can( 'edit_page', $post_id ) ) return;//user cannot edit page
            }else{
                if ( !current_user_can( 'edit_post', $post_id ) ) return;//user cannot edit post
            }

        }


        /* OK, its safe for us to save the data now. */

        
        $cp_links_ids = ( isset($form_data['ids']) ) ? $form_data['ids'] : null; //existing links to attach to post
        
        $new_links_form = ( isset($form_data['new']) ) ? $form_data['new'] : null;
        $new_links_form_name = ( isset($new_links_form['name']) ) ? $new_links_form['name'] : array();
        $new_links_form_url = ( isset($new_links_form['url']) ) ? $new_links_form['url'] : array();
        $new_links_form_target = ( isset($new_links_form['target']) ) ? $new_links_form['target'] : array();
        $new_links = array();

        //combine form arrays
        //TO FIX seems that unchecked target boxes still gets a '_blank' value, why ?
        foreach ( $new_links_form_url as $key=>$url ) {
            if ($key == 0) continue; //ignore first item (cloned block)
            if (!$url) continue;
            $new_links[] = array( 
                'name' => $new_links_form_name[ $key ] , 
                'url' => $url, 
                'target' => $new_links_form_target[ $key ] 
            );
        }

        //new links
        foreach((array)$new_links as $key=>$new_link){
            
            $url = ( isset($new_link['url']) ) ? urldecode($new_link['url']) : null;
            $name = ( isset($new_link['name']) ) ? $new_link['name'] : null;
            
            $linkdata = array(
                'link_name'     => $name,
                'link_url'      => $url,
                'link_target'   => ( isset($new_link['target']) ) ? $new_link['target'] : null
            );
            
            //validate for the new link
            $linkdata = array_filter($linkdata);
            $link_id = $this->insert_link($linkdata);
            
            if ($link_id && !is_wp_error($link_id)){
                $cp_links_ids[] = $link_id;
            }
        }

        $cp_links_ids = array_unique((array)$cp_links_ids);
        
        update_post_meta( $post_id, '_custom_post_links_ids', $cp_links_ids );
        
        return $cp_links_ids;

    }
    
    function insert_link($new_link){

        //sanitize
        $linkdata = $this->get_blank_link($new_link);

        if ( !$linkdata['link_url']) return new WP_Error( 'missing_required',__('A name and url are required for each link','custom-post-links') );

        if ( !$linkdata['link_name'] ){
            $linkdata['link_name'] = cp_links_get_name_from_url($linkdata['link_url']);
        }
        
        
        //TO FIX check url is valid
        if ( !$link_id = cp_links_get_existing_link_id($linkdata['link_url'],$linkdata['link_name']) ){ //check the link does not exists yet
            if( !function_exists( 'wp_insert_link' ) ) include_once( ABSPATH . '/wp-admin/includes/bookmark.php' );
            $link_id = wp_insert_link( $linkdata, true ); //return id
        }
        
        return $link_id;
    }
    
    function get_blank_link($args = array()){
        $defaults = array(
            'link_id'       => 0, //link will not be editable or saved if this is null.  This can be useful when external plugins are appending links using the filter 'cp_links_get_for_post_pre'.
            'link_name'     => null,
            'link_url'      => null,
            'link_target'   => null,
            'link_category' => $this->get_options('links_category')
        );
        return wp_parse_args((array)$args,$defaults);
    }
    
}

function cp_links() {
	return CP_Links::instance();
}

cp_links();
















