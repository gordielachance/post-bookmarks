jQuery(function($){

    $(document).ready(function(){
        
        var metabox = $("#post-bookmarks");

        /* Row actions */
        //edit
        metabox.find('.row-actions .edit a').live("click", function(event){
            event.preventDefault();
            var row = $(this).parents('tr');
            row.addClass('metabox-table-row-edit');
        });

        //save
        metabox.find('.row-actions .save a').live("click", function(event){
            event.preventDefault();

            var row = $(this).parents('tr');
            if ( !post_bkmarks_is_row_filled(row) ) return;
            
            post_bkmarks_row_action(row,'save');

        });

        //unlink
        metabox.find('.row-actions .unlink a').live("click", function(event){
            event.preventDefault();
            var row = $(this).parents('tr');
            post_bkmarks_row_action(row,'unlink');
        });

        //delete
        metabox.find('.row-actions .delete a').live("click", function(event){
            event.preventDefault();
            var row = $(this).parents('tr');
            post_bkmarks_row_action(row,'delete');
        });


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
                        url: post_bkmarks_L10n.ajaxurl,
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
                if ( !post_bkmarks_is_row_filled(row_filled_last) ) return; //abord
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
                post_bkmarks_reorder_rows();
            }
        });
    })
})

function post_bkmarks_reorder_rows(){
    var all_rows = jQuery("#post-bookmarks").find( 'table #the-list tr' );
    jQuery.each( all_rows, function( key, value ) {
      var order_input = jQuery(this).find('.column-reorder input');
        order_input.val(key);
    });
}

/*
Checks that the row data is ready to be saved, used to know if we can save the row.
Focus on row and return false if not.
*/

function post_bkmarks_is_row_filled(row){
    var row_url_input = row.find('.column-url input');
    if( row_url_input.val().trim().length === 0 ) {
        row_url_input.focus(); //focus on URL field
        return false;
    }
    
    return true;
}

function post_bkmarks_row_action(row,row_action){
    //link categories
    link_categories = [];
    var categories_checked = row.find('.column-category input:checked:enabled');
    categories_checked.each(function() {
        link_categories.push(jQuery(this).val());
    });

    var link = {
        'link_id'       : row.find('.check-column input[type="hidden"]').val(),
        'link_url'      : row.find('.column-url input').val().trim(),
        'link_name'     : row.find('.column-name input').val().trim(),
        'link_target'   : row.find('.column-target input:checked').val(),
        'link_category' : link_categories,
        'link_order'    : row.find('.column-reorder input[type="hidden"]').val(),
    }

    var ajax_data = {
        'action'        : 'post_bkmarks_row_action',
        'post_id'       : row.closest('#post-bkmarks-list').attr('data-post-bkmarks-post-id'),
        'row_action'    : row_action,
        'ajax_link'     : link
    };

    jQuery.ajax({
        type: "post",
        url: post_bkmarks_L10n.ajaxurl,
        data:ajax_data,
        dataType: 'json',
        beforeSend: function() {
            row.addClass('loading');
        },
        success: function(data){
            if (data.success === false) {
                console.log(data);
            }else{
                if (row_action == 'save'){
                    row.html( data.html );
                    row.removeClass('metabox-table-row-edit');
                }else if ( (row_action == 'unlink') || (row_action == 'delete') ){
                    row.remove();
                    post_bkmarks_reorder_rows();
                }

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
}

