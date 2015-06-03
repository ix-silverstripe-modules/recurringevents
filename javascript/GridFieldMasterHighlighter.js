(function($) {

	$.entwine('ss', function($) {
		$('td.ss-gridfield-highlight').entwine({
			onclick: function(e){
				e.preventDefault();
				return false;
			}
		});

		$('.ss-gridfield-alert[data-record-alert-message]').entwine({
			onmatch: function() {
				$(this).tooltip();
			},
			onclick: function(e){
				e.preventDefault();
				return false;
			}
		});
	});

}(jQuery));