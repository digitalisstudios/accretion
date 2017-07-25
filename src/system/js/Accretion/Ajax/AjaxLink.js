class AjaxLink extends Ajax {
	constructor(){
		super();

		var that = this;
		$(document).off('click.ajaxLink').on('click.ajaxLink', '.ajax-link:not([disabled])', function(e){
	
			//PREVENT FROM DOING ANYTHING ELSE
			e.preventDefault();
			e.stopPropagation();

			that.load(this);
		});
	}

	load(ele){

		//SHOW THE SPINNER
		spinner();

		//GET THE CALLBACK NAME
		var callback_name 	= $(ele).attr('callback');
		var target 			= $(ele).attr('data-element') !== undefined ? $($(ele).attr('data-element')) : false;  
		var url 			= $(ele).attr('data-url') !== undefined ? $(ele).attr('data-url') : $(ele).attr('href');
		var callback_type 	= $(ele).attr('data-callback_type') !== undefined ? $(ele).attr('data-callback_type') : 'html';

		console.log(callback_type);
		
		//GET AND FORMAT SEND DATA
		var send_data 	= JSON.parse(JSON.stringify($(ele).data()));

		$.ajax({
			url: url,
			type: 'POST',
			data: send_data,
			success: function(data){			
				
				if(target){
					if(callback_type == 'remove'){
						$(target).remove();
					}
					else{
						$(target)[callback_type](data);
					}
					
				}
				spinner('hide');
				if(typeof callback_name !== 'undefined'){
					if(typeof(window[callback_name]) == 'function'){
						window[callback_name](data, ele)
					}
				}
				
				
			}
		});
	}
}