jQuery(document).ready(function($){
    $('.color-picker').each(function(){
        $(this).wpColorPicker();
    });
    $('.field[conditional-formatting="true"]').change(function(){
        var el = $(this).prop('nodeName');
        var condition = $(this).attr('data-condition');    
        if(el == 'INPUT' && $(this).attr('type') == 'checkbox'){
            var val = $(this).is(':checked');
            if(val) { 
                $('td[condition="'+condition+'"]').attr('show', 'true'); 
                $('div[condition="'+condition+'"]').attr('show', 'true'); 
            }
            else {
                $('td[condition="'+condition+'"]').attr('show', 'false'); 
                $('div[condition="'+condition+'"]').attr('show', 'false'); 
            }
        }
        else if (el == 'SELECT'){
            var val = $(this).val();
            $('td[condition="'+condition+'"][condition-value="'+val+'"]').attr('show', 'true'); 
            $('td[condition="'+condition+'"][condition-value!="'+val+'"]').attr('show', 'false'); 
        }
    })
    $('.field#show_override').change(function(){
        var el = $(this).prop('nodeName');   
        if(el == 'INPUT' && $(this).attr('type') == 'checkbox'){
            var val = $(this).is(':checked');
            if(val) {
                $('#page-overrides').show();
            }else{
                $('#page-overrides').hide();
            }
        }
    })
    wp.codeEditor.initialize($('.access-manager-wrapper textarea#custom_css'), cm_settings);
})
