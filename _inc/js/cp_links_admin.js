jQuery(function($){

    $(document).ready(function(){

        // Look for changes in the value
        $('#custom-post-links .cp-links-row-edit .column-url input').live("change paste", function(event){
            
            var row = $(this).parents('tr');
            
            var cell_url = $(this).parents('td');
            var link_url = $(this).val().trim();
            
            var cell_name = row.find('.column-name');
            var name_input = row.find('.column-name input');
            var link_name = name_input.val().trim();
            
            var cell_favicon = row.find('.column-favicon');

            var link = $('<a>',{href: link_url});
            var uri = link.uri();

            //check for protocol
            var protocol = uri.protocol();
            if ( !protocol ){
                link_url = 'http://' + link_url;
                $(this).val(link_url);
                $(this).trigger( "change" );
                return;
            }

            //check for domain and top level domain
            var domain = uri.domain();
            var tld = uri.tld();

            if (domain && tld){ //ok for ajax

                var ajax_data = {
                    'action': 'cp_links_refresh_url',
                    'url':  link_url,
                    'name': link_name
                };

                $.ajax({

                    type: "post",
                    url: ajaxurl,
                    data:ajax_data,
                    dataType: 'json',
                    beforeSend: function() {
                        row.addClass('loading');
                    },
                    success: function(data){
                        if (data.success === false) {
                            console.log(data);
                        }else{
                            if (!link_name){ //if field was empty
                                name_input.val(data.name);
                            }
                            cell_favicon.html(data.favicon);
                            
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        console.log(xhr.status);
                        console.log(thrownError);
                    },
                    complete: function() {
                        row.removeClass('loading');
                    }
                })
            }else{
                cell_favicon.html('');
            }

        });

        var add_new_section =  $('#add-link-section');
        var table = add_new_section.find("table");

        //add new link
        add_new_section.find('a').click(function(event){

            event.preventDefault();

            var new_line = table.find("tbody tr");
            var new_table_line = new_line.clone();

            //count existing new link rows
            var new_link_rows = $("#list-links-section #the-list tr.cp-links-row-new");
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

