class Modal extends Accretion {
	constructor(){
		super();
		this.current_modal 	= 0;
		this.modal_trigger 	= false;
		this.modal_html 	= {};

		console.log('called');

		//LISTEN FOR MODAL LINK
		$(document).off('click.TriggerModal').on('click.TriggerModal', '.modal-link', function(e){
			e.preventDefault();
			var url 	= $(this).attr('href');
			var title 	= $(this).attr('title');
			var width 	= $(this).attr('data-width'); 
			if(typeof(title) == 'undefined'){
				title = '';
			}
			Accretion.Modal.modal_trigger = this;
			Accretion.Modal.render(url, title, this);
		});

		//LISTEN FOR MODAL CLOSE
		$(document).off('hidden.bs.modal.MultiModal').on('hidden.bs.modal.MultiModal', function(){
			Accretion.Modal.hidden();
		});
	}

	render(url, title, ele){

		var width_str = '';
		if(typeof($(ele).data('width')) !== 'undefined'){
			width_str = 'style="width:'+$(ele).data('width')+'"';
		}

		var modal_ajax_form 			= false;
		var modal_ajax_form_callback 	= false;
		if(typeof($(ele).data('ajax_form')) !== 'undefined'){
			
			var modal_ajax_form = true;
			if(typeof($(ele).data('ajax_form_callback')) !== 'undefined'){
				var modal_ajax_form_callback = $(ele).data('ajax_form_callback');
			}

		}
		var params = {
			url: url,
			success:function(data){

				Accretion.Modal.from_html(
					'<div class="modal fade" id="accretion-modal" tabindex="-1" role="dialog" aria-labelledby="accretion-modal-title">'+
			  			'<div class="modal-dialog modal-lg" '+width_str+' role="document">'+
							'<div class="modal-content">'+
								'<div class="modal-header">'+
									'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
									'<h4 class="modal-title" id="accretion-modal-title">'+title+'</h4>'+
								'</div>'+
								'<div class="modal-body">'+
								data+
								'</div>'+
							'</div>'+
						'</div>'+
					'</div>'
				);

				var modal = $('body').find('.modal').last();

				if($(modal).find('form').length > 0){
					if(modal_ajax_form){
						$(modal).find('form').addClass('ajax-form');
						$(modal).find('form').append('<input type="hidden" name="ajax" value="true">');
						if(modal_ajax_form_callback){
							$(modal).find('form').attr('callback', modal_ajax_form_callback);
						}
					}
					
					
				}

				$('#accretion-modal').modal('show').draggable({handle: '.modal-header'});
				$(document).trigger('accretion-modal-show');
				$(document).off('shown.bs.modal.Ajax').on('shown.bs.modal.Ajax', function(){				
					//init_fill_height();
					//spinner('hide');
				});
			}
		};


		if(typeof($(ele).data()) !== 'undefined'){
			params.type = 'POST';
			var temp_data = {};
			var ele_data = $(ele).data();
			for(var x in ele_data){
				if(x !== 'ajax_form' && x !== 'ajax_form_callback' && x !== 'width'){
					temp_data[x] = ele_data[x];
				}
			}

			params.data = temp_data;
		}
		$.ajax(params);
	}

	from_html(html){
		if($('#accretion-modal').length > 0){
			this.current_modal++;
		}

		if(this.current_modal > 0){	
			var new_element = $(html);
			var new_element_html = $(new_element).html();
			$('#accretion-modal').html(new_element_html);
		}
		else{		
			$('body').append(html);
			$('#accretion-modal').modal('show').draggable({handle: '.modal-header'});
		}	
		//console.log(current_modal);
		this.modal_html[this.current_modal] = $('#accretion-modal')[0].outerHTML;
		$(document).trigger('accretion-modal-show');
		spinner('hide');
	}

	hidden(){
		if(this.current_modal >= 0){
			this.current_modal--;
			if(this.current_modal >= 0){
				this.from_html(this.modal_html[this.current_modal]);
				$('#accretion-modal').modal('show');
				this.current_modal	-= 1;
			}
			else{
				this.reset();		
			}
		}
		else{
			this.reset();
		}
	}

	reset(){
		$('#accretion-modal').remove();
		this.current_modal 	= 0;
		this.modal_html 	= {};
		this.modal_trigger 	= false;
	}
}