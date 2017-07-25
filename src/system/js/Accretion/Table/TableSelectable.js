class TableSelectable extends Table {
	constructor(){
		super();
		$(document).off('click.keepSelectedRows').on('click.keepSelectedRows', '.table-selectable tbody tr > td > *', function(e){
			e.stopPropagation();
		});

		$(document).off('click.selectRow').on('click.selectRow', '.table-selectable tbody tr', function(e){
			e.stopPropagation();

			var target_table = $(this).closest('table');

			$(this).toggleClass('selected-row');

			starting_row = false;
			if(e.shiftKey){
				e.preventDefault();
				if($(this).hasClass('selected-row')){
					if($('.starting-row', target_table).length > 0){
						$(this).addClass('shift-row');

						$.each($(target_table).find('tr'), function(){
							if($(this).hasClass('starting-row') || $(this).hasClass('shift-row')){
								if(starting_row == false){
									starting_row = true;
								}
								else{
									starting_row = false;
								}
							}
							if(starting_row == true){
								
								$(this).addClass('selected-row');
							}
						});

						$('.starting-row', target_table).removeClass('starting-row');
						$('.shift-row', target_table).removeClass('shift-row');
					}
					else{
						$(this).addClass('starting-row');
					}
				}			
			}
			else if(e.ctrlKey){
				//SILENT ON CONTROL CLICK
			}
			else{	
				active = false;
				if($(this).hasClass('selected-row')){
					active = true;
				}
				$('.shift-row', target_table).removeClass('shift-row');
				$('.starting-row', target_table).removeClass('starting-row');				

				$('.selected-row', target_table).removeClass('selected-row');	
				$(this).addClass('starting-row');
				if(active){
					
					$(this).addClass('selected-row');
				}		
			}

			if($(target_table).find('.selected-row').length == 0){
				$(document).trigger('no_rows_selected');
				$(target_table).trigger('no_rows_selected');

			}
			else if($(target_table).find('.selected-row').length == 1){
				$(document).trigger('one_row_selected');
				$(target_table).trigger('one_row_selected');
				
			}
			else{
				$(document).trigger('multiple_rows_selected');
				$(target_table).trigger('multiple_rows_selected');		
			}
			$(document).trigger('selected_row_change');
			$(target_table).trigger('selected_row_change');
		});
	}
}