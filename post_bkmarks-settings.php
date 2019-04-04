<?php

class Post_Bookmarks_Settings {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
	}

	function admin_menu() {
		add_options_page(
			__('Post Bookmarks','post-bkmarks'),
			__('Post Bookmarks','post-bkmarks'),
			'manage_options',
			'pbkm_settings',
			array(
				$this,
				'settings_page'
			)
		);
	}
    
    function settings_sanitize( $input ){
        $new_input = array();

        if( isset( $input['reset_options'] ) ){
            
            $new_input = post_bkmarks()->options_default;
            
        }else{ //sanitize values

            //display links
            $display_links_allowed = array('before','after','manual');
            if ( isset ($input['display_links']) && in_array($input['display_links'],$display_links_allowed) ){
                $new_input['display_links'] = $input['display_links'];
            }
            
            //default target
            $target_allowed = array('_blank','_self');
            if ( isset ($input['default_target']) && in_array($input['default_target'],$target_allowed) ){
                $new_input['default_target'] = $input['default_target'];
            }
            
            //ignore_target_local
            $new_input['ignore_target_local'] = ( isset($input['ignore_target_local']) ) ? 'on' : 'off';

            //orderby
            $orderby_allowed = array('name','custom');
            if ( isset ($input['links_orderby']) && in_array($input['links_orderby'],$orderby_allowed) ){
                $new_input['links_orderby'] = $input['links_orderby'];
            }
            
            //get favicon
            $new_input['get_favicon'] = ( isset($input['get_favicon']) ) ? "on" : "off";

        }

        $new_input = array_filter($new_input);

        return $new_input;
        
        
    }

    function settings_init(){

        register_setting(
            'post_bkmarks_option_group', // Option group
            Post_Bookmarks::$settings_optionkey, // Option name
            array( $this, 'settings_sanitize' ) // Sanitize
         );
        
        add_settings_section(
            'settings_general', // ID
            __('General','post-bkmarks'), // Title
            array( $this, 'post_bkmarks_settings_general_desc' ), // Callback
            'post_bkmarks-settings-page' // Page
        );
        
        add_settings_field(
            'display_links', 
            __('Display links','post-bkmarks'), 
            array( $this, 'display_links_field_callback' ), 
            'post_bkmarks-settings-page', // Page
            'settings_general' //section
        );
        
        add_settings_field(
            'default_target', 
            __('Default target','post-bkmarks'), 
            array( $this, 'default_target_field_callback' ), 
            'post_bkmarks-settings-page', // Page
            'settings_general' //section
        );
        
        add_settings_field(
            'links_orderby', 
            __('Order links by','post-bkmarks'), 
            array( $this, 'links_orderby_field_callback' ), 
            'post_bkmarks-settings-page', // Page
            'settings_general' //section
        );
        
        add_settings_field(
            'get_favicon', 
            __('Get favicon','post-bkmarks'), 
            array( $this, 'links_get_favicon_callback' ), 
            'post_bkmarks-settings-page', // Page
            'settings_general' //section
        );
        
        add_settings_section(
            'settings_system', // ID
            __('System','post-bkmarks'), // Title
            array( $this, 'post_bkmarks_settings_system_desc' ), // Callback
            'post_bkmarks-settings-page' // Page
        );

        add_settings_field(
            'reset_options', 
            __('Reset Options','post-bkmarks'), 
            array( $this, 'reset_options_callback' ), 
            'post_bkmarks-settings-page', // Page
            'settings_system'//section
        );

    }
    
    function post_bkmarks_settings_general_desc(){
        
    }
    
    function display_links_field_callback(){
        $option = post_bkmarks()->get_options('display_links');

        printf(
            '<p><input type="radio" name="%1$s[display_links]" value="before" %2$s /> %3$s</p>',
            Post_Bookmarks::$settings_optionkey,
            checked( $option, 'before', false ),
            __('Before content','post-bkmarks')
        );
        
        printf(
            '<p><input type="radio" name="%1$s[display_links]" value="after" %2$s /> %3$s</p>',
            Post_Bookmarks::$settings_optionkey,
            checked( $option, 'after', false ),
            __('After content','post-bkmarks')
        );
        
        printf(
            '<p><input type="radio" name="%1$s[display_links]" value="manual" %2$s /> %3$s</p>',
            Post_Bookmarks::$settings_optionkey,
            checked( $option, 'manual', false ),
            __('Manual','post-bkmarks').' <small>— '.sprintf(__('Use the function %s in your theme templates','post-bkmarks'),'<code>Post_Bookmarks::get_link_list()</code>').'</small>'
        );
        
        
    }
    
    function links_orderby_field_callback(){
        $option = post_bkmarks()->get_options('links_orderby');

        printf(
            '<p><input type="radio" name="%1$s[links_orderby]" value="name" %2$s /> %3$s</p>',
            Post_Bookmarks::$settings_optionkey,
            checked( $option, 'name', false ),
            __('Name')
        );

        printf(
            '<p><input type="radio" name="%1$s[links_orderby]" value="custom" %2$s /> %3$s</p>',
            Post_Bookmarks::$settings_optionkey,
            checked( $option, 'custom', false ),
            __('Custom order','post-bkmarks').' <small>— '.sprintf(__('Use the %s icon while managing custom links for a post to reorder them','post-bkmarks'),'<i class="fa fa-arrows-v"></i>').'</small>'
        );
        
        
    }
    
    function links_get_favicon_callback(){
        $option = post_bkmarks()->get_options('get_favicon');
        printf(
            '<p><input type="checkbox" name="%1$s[get_favicon]" value="on" %2$s /> %3$s</p>',
            Post_Bookmarks::$settings_optionkey,
            checked( $option, 'on', false ),
            __("Load links favicons using the Google API",'post-bkmarks')
        );
    }
    
    function default_target_field_callback(){
        $option = post_bkmarks()->get_options('default_target');
        $option_ignore = post_bkmarks()->get_options('ignore_target_local');

        printf(
            '<p><input type="radio" name="%1$s[default_target]" value="_blank" %2$s /> %3$s</p>',
            Post_Bookmarks::$settings_optionkey,
            checked( $option, '_blank', false ),
            '<code>_blank</code>'
        );
        
        printf(
            '<p><input type="radio" name="%1$s[default_target]" value="_self" %2$s /> %3$s</p>',
            Post_Bookmarks::$settings_optionkey,
            checked( $option, '_self', false ),
            '<code>_self</code>'
        );
        
        printf(
            '<p><input type="checkbox" name="%1$s[ignore_target_local]" value="on" %2$s /> %3$s</p>',
            Post_Bookmarks::$settings_optionkey,
            checked( $option_ignore, 'on', false ),
            __('Ignore target for local links','post-bkmarks')
        );

        
    }
    
    function post_bkmarks_settings_system_desc(){
        
    }

    function reset_options_callback(){
        printf(
            '<input type="checkbox" name="%1$s[reset_options]" value="on"/> %2$s',
            Post_Bookmarks::$settings_optionkey,
            __("Reset options to their default values.","post_bkmarks")
        );
    }
    

	function  settings_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('Post Bookmarks Settings','post-bkmarks');?></h2>  
            
            <?php

            settings_errors('post_bkmarks_option_group');

            ?>
            <form method="post" action="options.php">
                <?php

                // This prints out all hidden setting fields
                settings_fields( 'post_bkmarks_option_group' );   
                do_settings_sections( 'post_bkmarks-settings-page' );
                submit_button();

                ?>
            </form>

        </div>
        <?php
	}
}

new Post_Bookmarks_Settings;