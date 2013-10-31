jQuery(document).ready(function($){
    var doing_ajax = false;
	$(document).on('change', 'input#tgm-exchange-madmimi-username, input#tgm-exchange-madmimi-api-key', function(e){
		var data = {
			action:   'tgm_exchange_madmimi_update_lists',
			username: $('#tgm-exchange-madmimi-username').val(),
			api_key:  $('#tgm-exchange-madmimi-api-key').val()
		};

        if ( ! doing_ajax ) {
            doing_ajax = true;
            $('.tgm-exchange-loading').css('display', 'inline');
    		$.post(ajaxurl, data, function(res){
    			$('.tgm-exchange-madmimi-list-output').html(res);
    			$('.tgm-exchange-loading').hide();
    			doing_ajax = false;
    		});
        }
	});
});