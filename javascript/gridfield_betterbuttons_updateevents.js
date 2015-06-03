(function($) {
$.entwine('ss', function($) {
	$('.cms #Form_ItemEditForm .Actions .gridfield-better-buttons-update-events').entwine({
		onclick: function(e) {
			e.preventDefault();
			this.showDialog();
		},
		Loading: null,
		Dialog:  null,
		URL:  null,
		onmatch: function() {
			this.before("<div class='update-events-dialog'></div>");
			var self = this;
			this.setDialog($('.update-events-dialog:first'));
			this.setURL(this.attr('href'));

			// configure the dialog
			var windowHeight = $(window).height();

			this.getDialog().data("field", this).dialog({
				autoOpen: 	false,
				width:   	$(window).width()  * 80 / 100,
				height:   	$(window).height() * 80 / 100,
				modal:    	true,
				title: 		this.data('dialog-title'),
				position: 	{ my: "center", at: "center", of: window }
			});

			// submit button loading state while form is submitting 
			this.getDialog().on("click", "button", function() {
				$(this).addClass("loading ui-state-disabled");
			});

			// handle dialog form submission
			this.getDialog().on("submit", "form", function() {
				var dlg = self.getDialog().dialog(),
					options = {};

				options.success = function(response) {
					self.getDialog().html(response);
				}

				$(this).ajaxSubmit(options);

				return false;
			});
		},
		showDialog: function(url) {
			var dlg = this.getDialog();

			dlg.empty().dialog("open").parent().addClass("loading");

			dlg.load(this.getURL(), function(){
				dlg.parent().removeClass("loading");
			});
		}
	});
});
})(jQuery);