(function($) {
	let fetching = false;
	let delay = (function(){
		let timer = 0;
		return function(callback, ms){
			clearTimeout (timer);
			timer = setTimeout(callback, ms);
		};
	})();

	$('input[name=Occurrences]').entwine({
		onkeyup: function(e) {
			let delaytime = 700;
			let me = $(this);

			delay(function() {
				if(!fetching) {
					fetching = true;
					me.addClass('loading');
					let formUrl = me.parents('form').attr('action');
					let formUrlParts = formUrl.split('?');
					let formUrl = formUrlParts[0];
					let url  = formUrl + '/field/SpecificDates/FieldsHTML';
					$.ajax({
						url: url,
						type: 'POST',
						data: { occurrences: me.val()},
						success: function(data) {
							$('#specific-date-field').replaceWith(data);
							fetching = false;
						},
						complete: function() {
							me.removeClass('loading');
						}
					});
				}
			}, delaytime );
		}
	});

}(jQuery));
