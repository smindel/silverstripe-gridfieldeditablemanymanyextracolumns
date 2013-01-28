(function($) {

	$('*[data-gridfield-cell-column] *')
		.live('click', function(){
			return false;
		})
		.live('change', function(){
			$(this).attr('data-gridfield-cell-dirty', 1);
			$('tr').has($(this)).addClass('dirty');
		});

	$('*[data-gridfield-cell-automatically]').live('change', function(){
		var newvalue = $('input,select,textarea', $(this)).attr('value');
		$(this).attr('data-gridfield-cell-dirty', 1);
		$('.gridfield-button-save', $('tr').has($(this)).addClass('dirty')).click();
		// alert('GridFieldEditableManyManyExtraColumns: saving automatically not implemented yet');
	});

}(jQuery));
