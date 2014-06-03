<?php
/*
Plugin Name: Daggerhart - Custom Post Links
Description: Add Fields to post types for appending/prepending arbitrary links to the post output
Plugin URI: https://github.com/daggerhart/custom-post-links
Author: Jonathan Daggerhart
Author URI: http://daggerhart.com
Version: 1.0
License: GPL2
*/

define( 'CUSTOM_POST_LINKS_FILE', __FILE__);
define( 'CUSTOM_POST_LINKS_DIR', dirname( __FILE__ ) );
define( 'CUSTOM_POST_LINKS_URL', plugin_dir_url( __FILE__ ) );

$custom_post_links_allowed_post_types =  array('post', 'page');

/*
 * Simple getter for allowed post types
 */
function _get_custom_post_links_allowed_post_types(){
  global $custom_post_links_allowed_post_types;
  return apply_filters( 'custom_post_links_allowed_post_types', $custom_post_links_allowed_post_types );
}

/*
 * Show meta box and load js/css
 */
function custom_post_links_admin_init(){
  $post_types = _get_custom_post_links_allowed_post_types();
  
  foreach ( $post_types as $post_type ) {
    add_meta_box( 'custom-post-links', 'Custom Post Links', 'custom_post_links_meta_box', $post_type, 'normal', 'high' );
  }
  
  // css
  wp_register_style( 'custom_post_links_css',  CUSTOM_POST_LINKS_URL.'custom-post-links.css' );
  // js
  wp_register_script( 'custom_post_links_js', CUSTOM_POST_LINKS_URL.'custom-post-links.js', array('jquery-core', 'jquery-ui-core', 'jquery-ui-sortable'), '1.0' );
}
add_action( 'admin_init', 'custom_post_links_admin_init' );

/*
 * enqueue admin scripts
 * https://pippinsplugins.com/loading-scripts-correctly-in-the-wordpress-admin/
 */
function custom_post_links_admin_enqueue_scripts( $hook ){
  $load = false;
  
  // edit pages/posts
  if ( $hook == 'post.php' &&
      isset($_REQUEST['action']) &&
      $_REQUEST['action'] == 'edit' &&
      isset($_REQUEST['post']) &&
      in_array( get_post_type( $_REQUEST['post']), _get_custom_post_links_allowed_post_types() ) )
  {
    $load = true;
  }
  
  // new post/page
  if ( $hook == 'post-new.php' ){
    global $post;
    if ( in_array($post->post_type, _get_custom_post_links_allowed_post_types() ) ){
      $load = true;
    }
  }
  
  // ensure that this is a post type we allow
  if ( $load ) {
    wp_enqueue_script( 'custom_post_links_js' );
    wp_enqueue_style( 'custom_post_links_css' );
  }
}
add_action( 'admin_enqueue_scripts', 'custom_post_links_admin_enqueue_scripts' );

/*
 * Custom meta box for post links
 */
function custom_post_links_meta_box( $post ){
  // Add an nonce field so we can check for it later.
  wp_nonce_field( 'custom_post_links_meta_box', 'custom_post_links_meta_box_nonce' );
  
  // default data / options
  $blank_row = array('url' => '', 'title' => '');
  $output_options = array(
    '_none_' => 'None',
    'below' => 'Below Content',
    'above' => 'Above Content',
  );
  
  $links_title = get_post_meta( $post->ID, '_custom_post_links_title', true);
  $links_output = get_post_meta( $post->ID, '_custom_post_links_output', true);
  
  $existing_links = get_post_meta( $post->ID, '_custom_post_links', true);
  
  if (empty($existing_links)){
    $existing_links = array( 0 => $blank_row);
  }
  ?>
    <script type="text/javascript">
      var custom_post_links_next_index = <?php print ( array_pop(array_keys($existing_links)) + 100);  // large increment to avoid collisions ?>;
      var custom_post_links_row_template = '<?php print str_replace("\n", '', custom_post_links_edit_link_row_template('---NEXT-INDEX---', $blank_row )); ?>';
    </script>
    <div id="custom-post-links-add-new"><strong>Add New Link</strong></div>
    
    <div class="custom-post-links-meta">
      <label>Links Title:</label> <input type="text" name="custom_post_links_title" value="<?php print $links_title; ?>" />
      <p class="description">The title for this group of links on output.</p>
      
      <label>Output:</label>
      <select name="custom_post_links_output">
        <?php
          foreach ($output_options as $k => $v)
          {
            $selected = ($k == $links_output) ? 'selected="selected"' : '';
            ?>
              <option value="<?php print $k; ?>" <?php print $selected; ?>><?php print $v; ?></option>
            <?php
          }
        ?>
      </select>
      <p class="description">Options for automatic output of these links.</p>
    </div>
    
    <div id="custom-post-links-target">
      <?php
        foreach ($existing_links as $index => $values){
          print custom_post_links_edit_link_row_template($index, $values);
        }
      ?>
    </div>
  <?php
}

/*
 * A single link row
 */
function custom_post_links_edit_link_row_template($index, $values){
  ob_start();
  ?>
    <div class="custom-post-links-row row-index-<?php print $index; ?>">
      <div class="custom-post-links-row-url">
        <label class="text-field">URL:</label>
        <span><input type="text" name="custom_post_links[<?php print $index; ?>][url]" value="<?php print $values['url']; ?>" /></span>
      </div>
      <div class="custom-post-links-row-title">
        <label class="text-field">Title:</label>
        <span><input type="text" name="custom_post_links[<?php print $index; ?>][title]" value="<?php print $values['title']; ?>" /></span>
      </div>
      
      <div class="custom-post-links-drag-handle"></div>
      
      <div class="custom-post-links-row-remove" title="<?php print $index;?>" >Remove</div>
      
      <div class="custom-post-links-more-options">
        <div class="custom-post-links-row-new_window">
          <label>
            <input type="checkbox" name="custom_post_links[<?php print $index; ?>][new_window]" <?php checked($values['new_window'], 'on', 1); ?> /> Open link in new browser window. ( target="_blank" )
          </label>
        </div>
        <div class="custom-post-links-row-nofollow">
          <label>
            <input type="checkbox" name="custom_post_links[<?php print $index; ?>][nofollow]" <?php checked($values['nofollow'], 'on', 1); ?> /> Tell bots to not follow this link. ( rel="nofollow" )
          </label>
        </div>
        <div class="custom-post-links-row-weight">
          <input type="text" size=3 class="custom-post-links-weight" name="custom_post_links[<?php print $index; ?>][weight]" value="<?php print $index; ?>" />
        </div>
      </div>
    </div>
  <?php
  return ob_get_clean();
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function custom_post_links_meta_box_save_data( $post_id ) {

  /*
   * We need to verify this came from our screen and with proper authorization,
   * because the save_post action can be triggered at other times.
   */

  // Check if our nonce is set.
  if ( ! isset( $_POST['custom_post_links_meta_box_nonce'] ) ) {
    return;
  }

  // Verify that the nonce is valid.
  if ( ! wp_verify_nonce( $_POST['custom_post_links_meta_box_nonce'], 'custom_post_links_meta_box' ) ) {
    return;
  }

  // If this is an autosave, our form has not been submitted, so we don't want to do anything.
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
    return;
  }

  // Check the user's permissions.
  if ( isset( $_REQUEST['post_type'] ) ){
    // ensure it's an allowed post_type
    if ( !in_array( $_REQUEST['post_type'], _get_custom_post_links_allowed_post_types() ) ){
      return;
    }
    
    // ensure user can edit page
    if ( 'page' == $_REQUEST['post_type'] && !current_user_can( 'edit_page', $post_id ) ) {
      return;
    }
    
    // ensure user can edit post
    else if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return;
    }
  }
  

  /* OK, its safe for us to save the data now. */
  
  // Make sure that it is set.
  if ( ! isset( $_POST['custom_post_links'] ) ) {
    if ( isset( $_POST['custom_post_links_output'] ) ) {
      // metabox was submitted, but all links were removed
      delete_post_meta( $post_id, '_custom_post_links');
      update_post_meta( $post_id, '_custom_post_links_output', '_none_' );
      update_post_meta( $post_id, '_custom_post_links_title', $_POST['custom_post_links_title'] );
    }
    return;
  }

  // sort links by weight
  $sorted = array();
  foreach ($_POST['custom_post_links'] as $link){
    $weight = $link['weight'];
    unset( $link['weight'] );
    $sorted[$weight] = $link;
  }
  
  ksort($sorted);
  
  // Update the meta field in the database.
  update_post_meta( $post_id, '_custom_post_links', $sorted );
  update_post_meta( $post_id, '_custom_post_links_title', $_POST['custom_post_links_title'] );
  update_post_meta( $post_id, '_custom_post_links_output', $_POST['custom_post_links_output'] );
}
add_action( 'save_post', 'custom_post_links_meta_box_save_data' );

/*
 * Generate the output for links on this post
 */
function custom_post_links_get_links_output($post_id){
  $links = get_post_meta( $post_id, '_custom_post_links', true);
  
  // get 1 link and see if it has a url
  $test = array_shift(array_values($links));
  if (!empty($test['url'])){
    $output = '<div>';
    
    $title = get_post_meta( $post_id, '_custom_post_links_title', true);
    if (!empty($title)){
      $output.= '<strong>'.$title.'</strong>';
    }
    
    $output.= '<ul class="custom-post-links">';
    foreach ($links as $link){
      if ( !empty( $link['url'] ) ){
        $output.= '<li>'.custom_post_links_output_single_link($link).'</li>';
      }
    }
    $output.= '</ul>';
    
    return $output.'</div>';
  }
  return false;
}

/*
 * Template a single link
 */
function custom_post_links_output_single_link($link){
  $text = (!empty($link['title'])) ? $link['title'] : $link['url'];
  
  $a = '<a href="'.$link['url'].'" ';
  
  if ( !empty( $link['title'])){
    $a.= 'title="'.$link['title'].'" ';
  }
  if ( !empty( $link['new_window'])){
    $a.= 'target="_blank" ';
  }
  if ( !empty( $link['nofollow'])){
    $a.= 'rel="nofollow" ';
  }
  $a.='>'.$text.'</a>';
  
  return $a;
}

/*
 * the_content filter to append custom post links to the post content
 */
function custom_post_links_output_links( $content ){
  global $post;
  if ( in_array( $post->post_type, _get_custom_post_links_allowed_post_types() ) ){
    $show = get_post_meta($post->ID, '_custom_post_links_output', true);
    if (!empty($show) && $show != '_none_'){
      $links = custom_post_links_get_links_output($post->ID);
      if ($links){
        if ($show == 'above'){
          $content = $links."\n".$content;
        }
        else if ($show == 'below'){
          $content.= "\n".$links;
        }
      }
    }
  }
  return $content;
}
add_filter('the_content', 'custom_post_links_output_links', 100, 2);