jQuery(function($){

    $(document).ready(function(){
        
        var metabox = $("#post-bookmarks");
        
        /**
        To avoid creating complex ajax functions, trick it!
        - check a single row checkbox 
        - set the bulk action
        - click the bulk action button
        **/
        function single_row_fire_bulk_action(row,bulk_action){
            //add loading class
            row.addClass('loading');
            //uncheck all rows
            var table = row.closest('.wp-list-table').find('.check-column input[type="checkbox"]').prop( "checked", false );
            //check this row
            row.find('.check-column input[type="checkbox"]').prop( "checked", true );
            //set top bulk action
            $('#post-bkmarks-bulk-action-selector-top').val(bulk_action);
            //click top bulk button
            $('#post-bkmarks-doaction').trigger('click');
        }
        
        /* Row actions */
        //edit
        metabox.find('.row-actions .edit a').live("click", function(event){
            event.preventDefault();
            var row = $(this).parents('tr');
            row.addClass('metabox-table-row-edit');
        });
        /*
        //save
        metabox.find('.row-actions .save a').live("click", function(event){
            event.preventDefault();

            var row = $(this).parents('tr');
            single_row_fire_bulk_action(row,'save');
        });
        //unlink
        metabox.find('.row-actions .unlink a').live("click", function(event){
            event.preventDefault();
            var row = $(this).parents('tr');
            single_row_fire_bulk_action(row,'unlink');
        });
        //save
        metabox.find('.row-actions .delete a').live("click", function(event){
            event.preventDefault();
            var row = $(this).parents('tr');
            single_row_fire_bulk_action(row,'delete');
        });
         */

        // Look for changes in the value
        metabox.find('.metabox-table-row-edit .column-url input').live("change paste", function(event){
            
            var row = $(this).parents('tr');
            
            var cell_url = $(this).parents('td');
            var link_url = $(this).val().trim();
            
            var cell_name = row.find('.column-name');
            var name_input = row.find('.column-name input');
            var link_name = name_input.val().trim();
            
            var cell_favicon = row.find('.column-favicon');

            if (link_url){
                
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
                        'action': 'post_bkmarks_refresh_url',
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
                
            }

        });

        //add new link
        metabox.find(".row-add-button").click(function(event){

            event.preventDefault();
            
            var rows_list = metabox.find("table #the-list tr:not(.no-items)"); //all list items
            var row_blank = metabox.find("table #the-list tr:first-child"); //item to clone
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
                var pattern = 'post_bkmarks[links][0]';
                var replaceby = 'post_bkmarks[links]['+rows_list.length+']';
                return html.split(pattern).join(replaceby);
            });
            
            //check checkbox & set 'Save' action
            new_row.find('.check-column input[type="checkbox"]').prop('checked', true);
            $('#post-bkmarks-bulk-action-selector-top').val("save");
 
            //add line
            new_row.insertAfter( row_blank );

            //focus input
            new_row.find('input').first().focus();

        });

        // sort links
        ( metabox ).find( 'table #the-list' ).sortable({
          handle: '.metabox-table-row-draghandle',

          update: function(event, ui) {
                var all_rows = ( metabox ).find( 'table #the-list tr' );
                $.each( all_rows, function( key, value ) {
                  var order_input = $(this).find('.column-reorder input');
                    order_input.val(key);
                });
          }
        });
    })
})



