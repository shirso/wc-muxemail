jQuery(function($){
    let windowWidth=$(window).width();
    $(window).resize(function(){
        windowWidth=$(window).width();
    });
    
   $(document).on('blur','#mew_api_key',function(e){
    if($(this).val().length > 10){
        $('#mewLoader').show();    
        loadWorkspaceId().then(function(request){
           $('#mew_workspace_id').html(request);
           $('#mewLoader').hide();    
        },function(){
            $('#mew_workspace_id').html('');
            $('#mewLoader').hide();    
            $.alert({
                title: mew_vars.errorText,
                content:  mew_vars.errorTextServer,
                useBootstrap:false,
                boxWidth: windowWidth > 760 ? '30%' : '90%',
                type: 'red'
            });
        });
    }
   });
   $(document).on('click','.mewLoadWorkSpace',function(e){
        e.preventDefault();
        if($('#mew_api_key').val().length > 10){
            $('#mewLoader').show();    
            loadWorkspaceId().then(function(request){  
               $('#mew_workspace_id').html(request);
               $('#mewLoader').hide();    
            },function(){
                $('#mew_workspace_id').html('');
                $('#mewLoader').hide();    
                $.alert({
                    title: mew_vars.errorText,
                    content:  mew_vars.errorTextServer,
                    useBootstrap:false,
                    boxWidth: windowWidth > 760 ? '30%' : '90%',
                    type: 'red'
                });
            });
        }
   });
   $(document).on('click','#connectMuxEmail',function(e){
        e.preventDefault();
        if($('#mew_api_key').val().length > 10 &&  $('#mew_workspace_id').val()!=''){
            $('#mewLoader').show();
            var data={
                action:'mew_create_login',
                apiKey:$('#mew_api_key').val(),
                workSpaceId:$('#mew_workspace_id').val(),
                security:mew_vars.nonces.login
            };
            $.post(mew_vars.ajax_url,data).done(function(response){
                if(response.success){
                    window.location.replace(response.data.url);
                }else{
                    $.alert({
                        title: mew_vars.errorText,
                        content:  response.data.msg,
                        useBootstrap:false,
                        boxWidth: windowWidth > 760 ? '30%' : '90%',
                        type: 'red'
                    });
                }
            }).fail(function(){
                
                $('#mewLoader').hide();
        }   );
        }else{
            $.alert({
                title: mew_vars.errorText,
                content:  mew_vars.errorTextEmpty,
                useBootstrap:false,
                boxWidth: windowWidth > 760 ? '30%' : '90%',
                type: 'red'
            });
        }
   })
   var loadWorkspaceId=function(){
    
    var ret = $.Deferred();
    $.ajax(
        {
            url: mew_vars.ajax_url,
            method: "POST",
            dataType: 'json',
            data:{
                action:'mew_load_workspace_data',
                apikey:$('#mew_api_key').val(),
                nonce:mew_vars.nonces.validation
            },
            async: true
        }
    ).done( function( res ) {
        if( res.success ) {
            ret.resolve( res.data.html )
        } else {
            ret.reject();
        }
       
    }).fail(function() {
		ret.reject();
       
	});
    return ret.promise();
   }
   if(typeof loadWorkspaceOnPageLoad!=='undefined'){
    $('#mewLoader').show(); 
    loadWorkspaceId().then(function(request){
        $('#mew_workspace_id').html(request);
        $('#mewLoader').hide();    
     },function(){
         $('#mew_workspace_id').html('');
         $('#mewLoader').hide();    
         $.alert({
             title: mew_vars.errorText,
             content:  mew_vars.errorTextServer,
             useBootstrap:false,
             boxWidth: windowWidth > 760 ? '30%' : '90%',
             type: 'red'
         });
     });
}
$('#mewTagSelect').select2({
    width:'90%'
});
$(document).on('click','#mewMapNewAttributes',function(e){
    e.preventDefault();
   var wooAttrs=$('.mewAttributeContainer').find('.mew_woo_attributes').removeAttr('disabled').serialize();
   var mewAttrs=$('.mewAttributeContainer').find('.mew_attributes').removeAttr('disabled').serialize();
   $('.mew_non_removable_attr').prop('disabled',true);
   $('#mewMapNewAttributesButtonContainer').remove();
   var data={
        action:'mew_add_new_attribute',
        mew_attrs:mewAttrs,
        woo_attrs:wooAttrs,
        security:mew_vars.nonces.mapAttribute
   };
   $('#mewLoader').show();
   $.post(mew_vars.ajax_url,data).done(function(response){
      $('#mewNewMapAttributes').append(response);
      $('#mewLoader').hide();
    }).fail(function(){
    $('#mewLoader').hide();
    });
});
$(document).on('click','.mew_remove_attr',function(e){
    e.preventDefault();
    var addnewButton=$('#mewMapNewAttributesButtonContainer');
    $(this).closest('.mewAttributeFormFields').remove();
    
    if($('#mewMapNewAttributesButtonContainer').length <= 0){
        $('.mew_attributes').last().parent().append(addnewButton);
    }

});

$(document).on('click','#mewApplySetting',function(e){
    e.preventDefault();
    $('#mewLoader').show();
    var wooAttrs=$('.mewAttributeContainer').find('.mew_woo_attributes').removeAttr('disabled').serialize();
    var mewAttrs=$('.mewAttributeContainer').find('.mew_attributes').removeAttr('disabled').serialize();
    $('.mew_non_removable_attr').prop('disabled',true);
    var data={
        action:'mew_save_settings',
        mew_attrs:mewAttrs,
        woo_attrs:wooAttrs,
        tags:$('#mewTagSelect').val(),
        security:mew_vars.nonces.saveAttribute
   };
        $.post(mew_vars.ajax_url,data).done(function(response){
            if(response.success){
            $.alert({
                title: mew_vars.successText,
                content:  response.data.msg,
                useBootstrap:false,
                boxWidth: windowWidth > 760 ? '30%' : '90%',
                type: 'green'
            });
        }
            $('#mewLoader').hide();
        }).fail(function(){
        $('#mewLoader').hide();
        });
});

if($('#mewNewMapAttributes').length > 0){
    var addnewButton='<div id="mewMapNewAttributesButtonContainer"><a id="mewMapNewAttributes" class="mew-new-field-button" href="#"><span class="dashicons dashicons-plus-alt"></span></a></div>';
    if($('#mewMapNewAttributesButtonContainer').length <= 0){
        $('.mew_attributes').last().parent().append(addnewButton);
    }
}
$(document).on('click','#mewSyncContacts',function(e){
    e.preventDefault();
    var self=$(this);
    if(self.hasClass('disable')) return false;
    self.addClass('disable');
    var data={
        action:'mew_create_sync_process',
        security:mew_vars.nonces.createSynceProcess
   };
   $.post(mew_vars.ajax_url,data).done(function(response){
        if(response.success){
            $.alert({
                title: mew_vars.successText,
                content:  response.data.msg,
                useBootstrap:false,
                boxWidth: windowWidth > 760 ? '30%' : '90%',
                type: 'green'
            });
        }
   }).fail(function(){
        $('#mewLoader').hide();
    });
});

});