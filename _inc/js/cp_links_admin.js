jQuery(function($){

    $(document).ready(function(){

        // Look for changes in the value
        $('.cp_links_new .column-url input').live("change paste", function(event){
            
            if( !$(this).val() ) return;
            
            var cell_url = $(this).parents('td');
            var row = cell_url.parents('.cp_links_new');
            var cell_name = row.find('.column-name');
            var name_input = cell_name.find('input');
            
            var name = cell_name.find('input').val();
            if (name.length) return;
            
            var ajax_data = {
                'action': 'cp_links_get_url_title',
                'url': $(this).val()
            };
            $.ajax({

                type: "post",
                url: ajaxurl,
                data:ajax_data,
                dataType: 'json',
                beforeSend: function() {
                    cell_url.addClass('loading');
                    cell_name.addClass('loading');
                },
                success: function(data){
                    if (data.success === false) {
                        cell_url.addClass('berror');
                        console.log(data);
                    }else{
                        name_input.val(data.name);
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    console.log(xhr.status);
                    console.log(thrownError);
                },
                complete: function() {
                    cell_url.removeClass('loading');
                    cell_name.removeClass('loading');
                }
            })
        });

        var add_new_section =  $('#add-link-section');
        var table = add_new_section.find("table");

        //add new link
        var blankBlock = $('#add-link-section');
        blankBlock.addClass('has-js');
        blankBlock.find('a').click(function(event){

            event.preventDefault();

            var new_line = table.find("tbody tr");
            var new_table_line = new_line.clone();
            new_table_line.addClass('cp_links_new');

            //count existing new link rows
            var new_link_rows = $("#custom-post-links #the-list tr.cp_links_new");
            var new_link_idx = new_link_rows.length -1;
            if (new_link_idx < 0) new_link_idx = 0;

            //check last entry is filled
            var first_line = new_link_rows.first();
            if (first_line.length > 0) {
                var first_line_url_input = first_line.find('.column-url input');
                console.log(first_line_url_input);
                if( first_line_url_input.val().length === 0 ) {
                    first_line_url_input.focus();
                    return;
                }
            }

            //clear form
            new_line.find('input[type="text"]').val('');

            //add line
            new_table_line.prependTo( "#custom-post-links #list-links-section #the-list" );

            //focus input
            new_table_line.find('input').first().focus();

        });

        // sort links
        $('#custom-post-links .wp-list-table #the-list').sortable({
          handle: '.cp-links-link-draghandle',

          update: function(event, ui) {
            // update row weights
            //_custom_post_links_update_links_weight();
          }
        });
    })
})

