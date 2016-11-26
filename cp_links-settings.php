<?php

class CP_Links_Settings {

	function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
	}

	function admin_menu() {
		add_options_page(
			__('Custom Post Links','cp_links'),
			__('Custom Post Links','cp_links'),
			'manage_options',
			'cpl_settings',
			array(
				$this,
				'settings_page'
			)
		);
	}
    
    function settings_sanitize( $input ){
        $new_input = array();

        if( isset( $input['reset_options'] ) ){
            
            $new_input = cp_links()->options_default;
            
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
            
            //hide from bookmarks
            $new_input['hide_from_bookmarks'] = ( isset($input['hide_from_bookmarks']) ) ? "on" : "off";

        }
        

        $new_input = array_filter($new_input);

        return $new_input;
        
        
    }

    function settings_init(){

        register_setting(
            'cp_links_option_group', // Option group
            cp_links::$meta_name_options, // Option name
            array( $this, 'settings_sanitize' ) // Sanitize
         );
        
        add_settings_section(
            'settings_general', // ID
            __('General','cp_links'), // Title
            array( $this, 'cp_links_settings_general_desc' ), // Callback
            'cp_links-settings-page' // Page
        );
        
        add_settings_field(
            'display_links', 
            __('Display links','cp_links'), 
            array( $this, 'display_links_field_callback' ), 
            'cp_links-settings-page', // Page
            'settings_general' //section
        );
        
        add_settings_field(
            'default_target', 
            __('Default target','cp_links'), 
            array( $this, 'default_target_field_callback' ), 
            'cp_links-settings-page', // Page
            'settings_general' //section
        );
        
        add_settings_field(
            'links_orderby', 
            __('Order links by','cp_links'), 
            array( $this, 'links_orderby_field_callback' ), 
            'cp_links-settings-page', // Page
            'settings_general' //section
        );
        
        add_settings_field(
            'get_favicon', 
            __('Get favicon','cp_links'), 
            array( $this, 'links_get_favicon_callback' ), 
            'cp_links-settings-page', // Page
            'settings_general' //section
        );
        
        add_settings_section(
            'settings_system', // ID
            __('System','cp_links'), // Title
            array( $this, 'cp_links_settings_system_desc' ), // Callback
            'cp_links-settings-page' // Page
        );
        
        add_settings_field(
            'hide_from_bookmarks', 
            __('Hide from bookmarks','cp_links'), 
            array( $this, 'hide_from_bookmarks_callback' ), 
            'cp_links-settings-page', // Page
            'settings_system'//section
        );
        
        add_settings_field(
            'reset_options', 
            __('Reset Options','cp_links'), 
            array( $this, 'reset_options_callback' ), 
            'cp_links-settings-page', // Page
            'settings_system'//section
        );

    }
    
    function cp_links_settings_general_desc(){
        
    }
    
    function display_links_field_callback(){
        $option = cp_links()->get_options('display_links');

        printf(
            '<p><input type="radio" name="%1$s[display_links]" value="before" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $option, 'before', false ),
            __('Before content','cp_links')
        );
        
        printf(
            '<p><input type="radio" name="%1$s[display_links]" value="after" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $option, 'after', false ),
            __('After content','cp_links')
        );
        
        printf(
            '<p><input type="radio" name="%1$s[display_links]" value="manual" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $option, 'manual', false ),
            __('Manual','cp_links').' <small>— '.sprintf(__('Use the function %s in your theme templates','cp_links'),'<code>cp_links_output_for_post()</code>').'</small>'
        );
        
        
    }
    
    function links_orderby_field_callback(){
        $option = cp_links()->get_options('links_orderby');

        printf(
            '<p><input type="radio" name="%1$s[links_orderby]" value="name" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $option, 'name', false ),
            __('Name')
        );

        printf(
            '<p><input type="radio" name="%1$s[links_orderby]" value="custom" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $option, 'custom', false ),
            __('Custom order','cp_links').' <small>— '.sprintf(__('Use the %s icon while managing custom links for a post to reorder them','cp_links'),'<i class="fa fa-arrows-v"></i>').'</small>'
        );
        
        
    }
    
    function links_get_favicon_callback(){
        $option = cp_links()->get_options('get_favicon');
        printf(
            '<p><input type="checkbox" name="%1$s[get_favicon]" value="on" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $option, 'on', false ),
            __("Load links favicons using the Google API",'cp_links')
        );
    }
    
    function default_target_field_callback(){
        $option = cp_links()->get_options('default_target');
        $option_ignore = cp_links()->get_options('ignore_target_local');

        printf(
            '<p><input type="radio" name="%1$s[default_target]" value="_blank" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $option, '_blank', false ),
            '<code>'.__('_blank','cp_links').'</code>'
        );
        
        printf(
            '<p><input type="radio" name="%1$s[default_target]" value="_self" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $option, '_self', false ),
            '<code>'.__('_self','cp_links').'</code>'
        );
        
        printf(
            '<p><input type="checkbox" name="%1$s[ignore_target_local]" value="on" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $option_ignore, 'on', false ),
            __('Ignore target for local links','cp_links')
        );

        
    }
    
    function cp_links_settings_system_desc(){
        
    }
    
    function hide_from_bookmarks_callback(){
        $hide_from_bookmarks = ( cp_links()->get_options('hide_from_bookmarks') == "on" ) ? true : false;
        printf(
            '<p><input type="checkbox" name="%1$s[hide_from_bookmarks]" value="on" %2$s /> %3$s</p>',
            CP_Links::$meta_name_options,
            checked( $hide_from_bookmarks, true, false ),
            __("Hide links created with this plugin from regular bookmarks.",'cp_links')
        );
    }
    
    function reset_options_callback(){
        printf(
            '<input type="checkbox" name="%1$s[reset_options]" value="on"/> %2$s',
            CP_Links::$meta_name_options,
            __("Reset options to their default values.","cp_links")
        );
    }
    

	function  settings_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('Custom Post Links Settings','cp_links');?></h2>  
            
            <?php

            settings_errors('cp_links_option_group');

            ?>
            <form method="post" action="options.php">
                <?php

                // This prints out all hidden setting fields
                settings_fields( 'cp_links_option_group' );   
                do_settings_sections( 'cp_links-settings-page' );
                submit_button();

                ?>
            </form>

        </div>
        <?php
	}
}

new CP_Links_Settings;