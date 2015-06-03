(function($) {
	var fetching = false;
    var delay 	 = (function(){
    	var timer = 0;
    	return function(callback, ms){
	    clearTimeout (timer);
	    timer = setTimeout(callback, ms);
	  };
	})();
	    
	$('input[name=Occurrences]').entwine({
		onkeyup: function(e) {
			var delaytime 	= 700;
			var me 			= $(this);
			
			delay(function(){
				if(!fetching){
					fetching 		 = true;
					me.addClass('loading');
					var formUrl 	 = me.parents('form').attr('action')
						formUrlParts = formUrl.split('?'),
						formUrl 	 = formUrlParts[0],
						url 		 = formUrl + '/field/SpecificDates/FieldsHTML';
					$.ajax({
						url: url,
						type: "POST",
						data: { occurrences: me.val()},
						success: function(data) {
							$('#specific-date-field').replaceWith(data);
							fetching = false;
						},
						complete: function(){
							me.removeClass('loading');
						}
					});
				}
			}, delaytime );
		}
	});
	
}(jQuery));