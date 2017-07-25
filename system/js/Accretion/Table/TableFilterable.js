class TableFilterable extends Table {
	constructor(){
		super();
		if($('.table-filterable').length > 0){
			$.each($('.table-filterable:not(.table-filterable-inited)'), function(k,v){

				$(this).addClass('table-filterable-inited');
				
				if(typeof($(this).attr('id')) == 'undefined'){
					$(this).attr('id', 'filter-table-'+k);
				}
				var id = $(this).attr('id');
				var cols = {};
				var col_values = {};
				$.each($('th', this), function(k,v){
					var key = k+1;
					if($(this).attr('data-filter') !== 'false'){
						cols[key] = $(this).text().trim();
						
					}
					else{
						cols[key] = '';
					}
				});

				for(var x in cols){
					col_values[cols[x]] = [];
					if(cols[x] == '') continue;
					var found = [];
					$.each($('#'+id+' > tbody > tr > td:nth-child('+x+')'), function(){
						text = $(this).text().trim();
						if(text != ''){
							if(found[text] == undefined){
								col_values[cols[x]].push(text);
								found[text] = text;
							}	
						}		
					});			
					col_values[cols[x]].sort();				
				}

			
				for(var x in cols){
					
				
					if(col_values[cols[x]].length >= 1){
						var sel = '<select class="form-control input-sm filter-table-select" data-col="'+x+'">'+"\n"+
							'<option value="">All</option>'+"\n";
						
							for(var k in col_values[cols[x]]){
								sel += '<option>'+col_values[cols[x]][k]+'</option>'+"\n";
							}

						sel += '</select>'+"\n";

						$(this).find('th:nth-child('+x+')').append(sel);
						
					}
					else{
						$(this).find('th:nth-child('+x+')').append('<div style="height:30px;"></div>');
					}
				}
				
			});
		}
		$(document).trigger('init-table-filter-complete');

		$(document).off('change.tableFilterMenuSelect').on('change.tableFilterMenuSelect', '.filter-table-select', function(){
	
			var target = $(this).closest('table');
			var filters = $(this).closest('table').find('.filter-table-select');
			
			$($(target).find('tr')).show();
			$.each($(filters), function(){
				if($(this).val() !== '' && $(this).val() !== null){
					var target_val = $(this).val().trim();
					

					var col = $(this).attr('data-col');

					$.each($(target).find('tr:visible'), function(){
						var text = $(this).find('td:nth-child('+col+')').text().trim();
						
						if(text !== target_val){
							$(this).hide();
						}
					});

					$(target).find('thead tr').show();
					
				}
			});

			$(document).trigger('table-filterable-complete');
		});
	}
}