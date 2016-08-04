(function($){
  
  // update row weights
  function _custom_post_links_update_links_weight(){
    $('#custom-post-links-target .custom-post-links-row').each(function( weight ){
      $( this ).find('.custom-post-links-weight').val( weight );
    });
  }
  
  
  $(document).ready(function(){

      var add_new_section =  $('#add-link-section');
      var table = add_new_section.find("table");

      //add new link
      $('#cp_links_add_new').click(function(event){
          
        event.preventDefault();
          
        var table_line = table.find(".cp_links_new").clone();
          
        //count existing new link rows
        var new_link_rows = $("#custom-post-links #the-list tr.cp_links_new");
        var new_link_idx = new_link_rows.length -1;
        if (new_link_idx < 0) new_link_idx = 0;

        //increment input names
        table_line.find('input').each(function() {
            var current_name = $( this ).attr('name');
            var new_name = current_name.replace('[new][0]', '[new]['+new_link_idx+']');
            $( this ).attr('name',new_name);
        });
          
        table_line.prependTo( "#custom-post-links #the-list" );

        //focus input
        table_line.find('input').first().focus();

          
          
      });

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
    $('#custom-post-links-target').on('click', '.custom-post-links-row-remove', function( event ) {
      var row_index = $( event.target ).attr('title');
      $('.custom-post-links-row.row-index-' + row_index).remove();
      
      // update row weights
      _custom_post_links_update_links_weight();      
    });
    
    // sort links
    $('#custom-post-links .wp-list-table #the-list').sortable({
      handle: '.cp-links-link-draghandle',
      
      update: function(event, ui) {
        // update row weights
        //_custom_post_links_update_links_weight();
      }
    });
    
  });  
})(jQuery);

