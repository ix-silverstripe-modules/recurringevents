(function($) {	
	$.entwine('ss', function($) {		

		/**
		 * Makes sure the component is above the headers
		 */
		$('.selectAllHeader').entwine({
	        onmatch: function(){
	    		var $parent = this.parents('thead'),
	      		$tr 		= $parent.find('tr'),
	      		targets 	= ['.filter-header', '.sortable-header'],
	      		$target 	= $parent.find(targets.join(',')),
	      		$component 	= this.clone(),
	      		index 		= $tr.index(this),
	      		newIndex 	= $tr.length - 1;
	
	    		$target.each(function(index, Element){
	    			var idx = $tr.index(Element);
	    			if ( idx < newIndex ){
	    				newIndex = idx;
	    			}
	    		});
	
	    		if ( index > newIndex ){
	    			$component.insertBefore($tr.eq(newIndex));
	    			this.remove();
	    		}
	        }
		});
		
		function checkActions(){
			var state = $('td.col-Select input:checked').length ? 'enable' : 'disable';
			
			$('.Actions button.doSaveSelected').button(state);
			$('.Actions button.doPublishSelected').button(state);
		}
		  
		/**
	  	* select table cell behaviours
	  	* check/uncheck checkbox when clicking cell
	  	*/
		$('td.col-Select').entwine({
			onclick: function(e) {
				$(this).parents('.ss-gridfield-table').find('input.SelectAll').prop('checked', '');
				var input = $(e.target).find('input');
				$(input).prop('checked', !$(input).prop('checked'));
				checkActions();
			}
		});
			
		/**
		 * Individual select checkbox behaviour
		 */
		$('td.col-Select input').entwine({
			onclick: function(e) {
				$(this).parents('.ss-gridfield-table').find('input.SelectAll').prop('checked', '');
				checkActions();
			}
		});

		/**
		 * Bulkselect checkbox behaviours
		 */
	    $('input.SelectAll').entwine({
			onclick: function(){
				var state = $(this).prop('checked');
				$(this).parents('.ss-gridfield-table')
	    			.find('td.col-Select input')
	    			.prop('checked', state)
	    			.trigger('change');
				checkActions();
			},
			getSelectRecordsID: function(){
				return $(this).parents('.ss-gridfield-table')
      				.find('td.col-Select input:checked')
      				.map(function() {  
      					return parseInt( $(this).data('record') )
      				})
      				.get();
			}
	    });
	});
}(jQuery));