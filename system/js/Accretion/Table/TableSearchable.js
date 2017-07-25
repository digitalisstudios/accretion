class TableSearchable extends Table {
	constructor(){
		super();
		$.each($('.table-searchable:not(.table-searchable-initialized)'), function(){
			var id = 'table-searchable-'+guid();
			$(this).attr('data-searchable-table-id', id).addClass('table-searchable-initialized');
			if($(this).attr('data-wrap') == 'parent'){
				var element = $(this).parent();
			}
			else{
				var element = $(this);
			}

			var search_placeholder = (typeof $(this).attr('data-search_input_placeholder') != 'undefined') ? $(this).attr('data-search_input_placeholder') : 'Search';
			$(element).wrap('<div class="table-searchable-wrap"></div>');
			$(element).before('<input type="text" class="form-control table-searchable-input" placeholder="'+search_placeholder+'"><br>');
			$(this).trigger('table_searchable.initialized');


		});

		$(document).trigger('table_searchable.initialized');

		$(document).off('keyup.SearchableTableInput').on('keyup.SearchableTableInput', '.table-searchable-input', function(){
			Accretion.Table.TableSearchable.run(this);
		});
	}

	run(ele){
		var target_table 	= $(ele).closest('.table-searchable-wrap').find('table.table-searchable tbody');
		var search_val 		= $(ele).val();

		if(search_val == ''){
			$(target_table).find('tr').show();
		}
		else{
			$(target_table).find('tr').hide();
			$.each($(target_table).find('tr'), function(){
				if($(ele).text().toLowerCase().indexOf(search_val.toLowerCase()) > -1){
					$(ele).show();
				}
			});
		}
	}
}