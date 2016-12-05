jQuery(function($){

    $(document).ready(function(){
        
        //Quick Edit
        $('#custom-post-links .row-actions .edit a').live("click", function(event){
            event.preventDefault();
            var row = $(this).parents('tr');
            row.addClass('cp-links-row-edit');
        });

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

        var section_new =   $("#custom-post-links #add-link-section");
        var section_list =  $("#custom-post-links #list-links-section")
        
        var table_list =    section_list.find("table");

        //add new link
        section_new.find('a.page-title-action').click(function(event){

            event.preventDefault();
            
            var rows_list = table_list.find("#the-list tr:not(.no-items)"); //all list items
            var row_blank = table_list.find("#the-list tr:first-child"); //item to clone
            var rows_filled = rows_list.not(row_blank); //other items
            
            //check last entry is filled
            var row_filled_last = rows_filled.first(); //skip first item (blank row)
            if (row_filled_last.length > 0) {
                var first_row_url_input = row_filled_last.find('.column-url input');
                if( first_row_url_input.val().length === 0 ) {
                    first_row_url_input.focus();
                    return;
                }
            }

            var new_row = row_blank.clone();
            new_row.find('input[type="text"]').val(''); //clear form

            //increment input name prefixes
            new_row.html(function(index,html){
                var pattern = 'custom_post_links[links][0]';
                var replaceby = 'custom_post_links[links]['+rows_list.length+']';
                return html.split(pattern).join(replaceby);
            }); 
 
            //add line
            new_row.insertAfter( row_blank );

            //focus input
            new_row.find('input').first().focus();

        });

        // sort links
        ( table_list ).find( '#the-list' ).sortable({
          handle: '.cp-links-link-draghandle',

          update: function(event, ui) {
                var all_rows = ( table_list ).find( '#the-list tr' );
                $.each( all_rows, function( key, value ) {
                  var order_input = $(this).find('.column-reorder input');
                    order_input.val(key);
                });
          }
        });
    })
})



