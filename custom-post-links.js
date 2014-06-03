(function($){
  
  // update row weights
  function _custom_post_links_update_links_weight(){
    $('#custom-post-links-target .custom-post-links-row').each(function( weight ){
      $( this ).find('.custom-post-links-weight').val( weight );
    });
  }
  
  
  $(document).ready(function(){
    
    // add new row
    $('#custom-post-links-add-new').click(function(){
      // custom_post_links_next_index
      var new_row = custom_post_links_row_template.replace(/---NEXT-INDEX---/g, custom_post_links_next_index);
      $('#custom-post-links-target').append(new_row);
      
      // update row weights
      _custom_post_links_update_links_weight();
      
      // increment next index
      custom_post_links_next_index++;
    });
    
    // remove row
    $('.custom-post-links-row-remove').on('click', function( event ) {
      var row_index = $( event.target ).attr('title');
      $('.custom-post-links-row.row-index-' + row_index).remove();
      
      // update row weights
      _custom_post_links_update_links_weight();      
    });
    
    // sort links
    $('#custom-post-links-target').sortable({
      handle: '.custom-post-links-drag-handle',
      
      update: function(event, ui) {
        // update row weights
        _custom_post_links_update_links_weight();
      }
    });
    
  });  
})(jQuery);

