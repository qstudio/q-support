/*
 *jQuery for WordPress Support Plugin
 **/
(function($) {
    
    // delay display of settings page, in case it's loading slowly due to transient refreshing ##
    $("body.settings_page_q_support .wrap").fadeIn();
    
    // open all links with class "_blank" in new window ##
    $('a._blank').click(function(){
        window.open( $(this).attr("href") );
        return false;
    });
    
    // reroute links to add a new support request into the help tab ##
    $("a[href='post-new.php?post_type=q_support'], body.post-type-q_support .add-new-h2").on('click', function(e) {
        
        // no kick back ##
        e.preventDefault();
        
        // trigger click on help tab - to reveal support form ##
        $('#contextual-help-link').click();
        
        // check ##
        //console.log("clicked it");
        
    });
    
    // hide "add request" link in sidebar until q_support_connected set correctly ##
    if ( !$("body").hasClass("q_support_connected") || $("#contextual-help-link-wrap").length == 0 ) { 
        $("#menu-posts-q_support ul li.wp-first-item").next("li").hide(); 
    }
    
    // show the checkbox in the table-header - @todo find out why this is hidden ##
    $('.wp-list-table #cb, .column-cb').show();
    
    // remove the edit option from the bulk actions ##
    $("body.post-type-q_support select[name*='action'] .hide-if-no-js").hide();
    
    // manipulate each row on the CPT edit screen ##
    $("tr.type-q_support").each(function() {
        
        // re-route link from q_support CPT list to orignal on wp-support.co ##
        $open = $(this).find("span.view a").attr("href"); // grab the good URL ##
        //console.log('view link: '+$open)
        if ( $open !== undefined ) {
            $(this).find(".row-title").attr("href", $open ).attr("target","_blank"); // replace the main link ##
        } else {
            $(this).find(".row-title").attr("href", '#' ); // kill the main link ##
        }

    });
    
    // select validation function ##
    jQuery.validator.addMethod("selectcheck", function(value, element, arg){
        return (value != '0');
    }, "Please select a Dedicated Agency from the list.");
    
    // validate q_support_settings ##
    $("#q_support_settings").validate({
        rules: {
            q_support_settings_agent: { selectcheck: "default" }
        },
        messages: {
            q_support_settings_agent: { selectcheck: "Please select a Dedicated Agency from the list" }
        }  
    });
    
    // hide then show agents <select> if required ##
    $type_select = $("form#q_support_settings select").eq(0); // get the first select ##
    $type = $type_select.val(); // get it's value ##
    
    $agents = $("form#q_support_settings select").eq(1).parents("tr"); // get the second select ##
    
    // hide if not set to agent ##
    if ( $type !== 'agent' ) { 
        $agents.find('select option:eq(0)').prop('selected', true); // reset agent ID ##
        $agents.hide(); // hide select ##
    }
    
    // track changes to "type" select ##
    $type_select.on('change', function(e){
        
        $type = $type_select.val(); // get it's value ##
        if ( $type == 'agent' ) { 
            $agents.show(); 
        } else {
            $agents.find('select option:eq(0)').prop('selected', true); // reset agent ID ##
            $agents.hide(); // hide select ##
        }
    
    });
    
    // validate support request ##
    $("#q_support_request, #q_support").validate();
    $("#q_support_request, #q_support").removeAttr("novalidate");
    
    // grab loading image url ##
    var imageurl = ajaxurl.replace('admin-ajax.php','images/');
    
    // ajax connect to API -- ##
    $('.q_support_connect').on('click', function(e) {

        // no kick back ##
        e.preventDefault();
        
        var t = this; // this shorthand ##
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'q_support_connect'
                ,nonce: ajax_object.ajax_nonce
            },
            dataType: 'json',
            beforeSend: function () {
                
                $(t).attr('disabled','disabled'); // disable submit ##
                $(t).before('<img id="inc_waiting" src="'+imageurl+'wpspin_light.gif" style="padding: 0px 5px; position: relative; top: 3px;" />'); // add a loader image ##
                
            },
            success: function (response) {

                $('#inc_waiting').remove(); // hide the loader ##
                $(t).removeAttr('disabled'); // enable the submit ##
                
                // trigger click on help tab - to hide support form - if form is visible ##
                if ( $('#screen-meta').is(':visible') ) {
                    //console.log("it's visible...?");
                    $('#contextual-help-link').click();
                }
                
                // hide other "updates" ##
                $(".updated").hide();
                
                if (response) {
                    
                    $("p.q_support_connect_p").hide(); // not required now ##
                    $(".q_support_response p").html(response).parent("div").slideDown(); // show response ## 

                } else {

                    $(".q_support_response p").html("Error completing request").parent("div").slideDown(); // show response ## 

                }
            }
        });
        
        // don't go back to submit the form ## 
        return false;

    });
    
    
    // ajax save settings -- ##
    $('form#q_support_settings').on('submit', function(e) {

        // no kick back ##
        e.preventDefault();
        
        // this shorthand ##
        var t = this;
        
        // grab submit button ##
        var submit = $(t).find("#submit");
        
        // grab required values ##
        var target      =  $(t).find("#q_support_settings_target").val();
        var agent      =  $(t).find("#q_support_settings_agent").val();
        var wordpress   =  $(t).find("#q_support_settings_wordpress").val();
        var server      =  $(t).find("#q_support_settings_server").val();
        var client      =  $(t).find("#q_support_settings_client").val();
        
        // validate ##
        if ( !target || !wordpress || !server || !client ) {
            return false;
        }
        
        // if dedicated is selected from the target list - make sure Agency is not '0' ##
        if ( target == 'agent' && agent == 0 ) {
            
            //console.log("stopped..");
            return false;
            
        } 
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'q_support_settings'
                ,q_support_settings_target: target
                ,q_support_settings_agent: agent
                ,q_support_settings_wordpress: wordpress
                ,q_support_settings_server: server
                ,q_support_settings_client: client
                ,nonce: ajax_object.ajax_nonce
            },
            dataType: 'json',
            beforeSend: function () {
                
                $(submit).attr('disabled','disabled'); // disable the submit ##
                $(submit).after('<img id="inc_waiting" src="'+imageurl+'wpspin_light.gif" style="float: left; padding: 4px 10px;" />'); // add a loader image ##
                
            },
            success: function (response) {

                $('#inc_waiting').remove(); // hide the loader ##
                $(submit).removeAttr('disabled'); // enable the submit ##
                
                if (response) {

                    $update = $(".q_support_response p").html(response).parent("div").slideDown(); // show response ## 
                    
                } else {

                    $update = $(".q_support_response p").html("Error completing request").parent("div").slideDown(); // show response ## 

                }
                
            }
            
        });

    });
    
    
    
    // ajax submit support request to plugin class -- ##
    $('#q_support_request').on('submit', function(e) {

        // no kick back ##
        e.preventDefault();
        
        var t = this; // this shorthand ##
        
        // grab submit button ##
        var submit = $(t).find("#submit");
        
        // grab required values ##
        var q_title =  $(t).find("#q_title").val();
        var q_question =  $(t).find("#q_question").val();
        var q_category =  $(t).find("#q_category").val();
        var q_url =  $(t).find("#q_url").val();
        
        // validate ##
        if ( !q_title || !q_question || !q_url ) {
            return false;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'q_support_form'
                ,q_title: q_title
                ,q_question: q_question
                ,q_url: q_url
                ,q_category: q_category
                ,nonce: ajax_object.ajax_nonce
            },
            dataType: 'json',
            beforeSend: function () {
                
                $(submit).attr('disabled','disabled'); // disable the submit ##
                $(submit).before('<img id="inc_waiting" src="'+imageurl+'wpspin_light.gif" style="float: right; padding: 4px 10px;" />'); // add a loader image ##
                
            },
            success: function (response) {
                
                $('#inc_waiting').remove(); // hide the loader ##
                $(submit).removeAttr('disabled'); // enable the submit ##
                
                // clear the form ##
                $(t).trigger("reset");
                
                // trigger click on help tab - to hide support form - if form is visible ##
                if ( $('#screen-meta').is(':visible') ) {
                    //console.log("it's visible...?");
                    $('#contextual-help-link').click();
                }
                
                // hide other "updates" ##
                $(".updated").hide();
                
                if (response) {

                    $update = $(".q_support_response p").html(response).parent("div").slideDown(); // show response ## 
                    
                } else {

                    $update = $(".q_support_response p").html("Error completing request").parent("div").slideDown(); // show response ## 

                }
                
            }
        });
        
        // don't go back to submit the form ## 
        return false;

    });
    
    
    // ajax clear stored plugin data -- ##


    // clear stored data ##
    $('.q_support_data_clear').on('click', function(e) {

        // no kick back ##
        e.preventDefault();
        
        var t = this; // this shorthand ##
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'q_support_data_clear'
                ,nonce: ajax_object.ajax_nonce
            },
            dataType: 'json',
            beforeSend: function () {
                
                $(t).attr('disabled','disabled'); // disable submit ##
                $(t).after('<img id="inc_waiting" src="'+imageurl+'wpspin_light.gif" style="float: left; padding: 4px 10px;" />'); // add a loader image ##
                
            },
            success: function (response) {

                $('#inc_waiting').remove(); // hide the loader ##
                $(t).removeAttr('disabled'); // enable the submit ##
                
                if (response) {

                    $(".q_support_response p").html(response).parent("div").slideDown(); // show response ## 

                } else {

                    $(".q_support_response p").html("Error completing request").parent("div").slideDown(); // show response ## 

                }
            }
        });
        
        // don't go back to submit the form ## 
        return false;

    });

})(jQuery);
