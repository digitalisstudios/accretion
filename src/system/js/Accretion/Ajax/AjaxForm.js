class AjaxForm extends Ajax {
	constructor(){
		super();
		$(document).off('submit.ajaxForm').on('submit.ajaxForm', '.ajax-form', function(e){
			e.preventDefault();	
			Accretion.Ajax.AjaxForm.submit(this);
		})

		$(document).off('submit.AjaxValidateForm').on('submit.AjaxValidateForm', 'form[validate]:not(.validated)', function(e){
			e.preventDefault();
			e.stopPropagation();
			Accretion.Ajax.AjaxForm.validate(this);			
		});
	}

	submit(ele){

		if(typeof($(ele).attr('validate')) !== 'undefined'){
			if(!$(ele).hasClass('validated')){
				return;
			}
		}
		spinner();
		callback_name 		= $(ele).attr('callback');
		var formdata 		= new FormData($(ele)[0]);
		var submit_button 	= $(document.activeElement);
		var json 			= $(ele).attr('data-json') == 'true' ? true : false;

		if(submit_button.context.type == 'submit'){
			if(typeof $(submit_button).attr('name') !== undefined){
				var name = $(submit_button).attr('name');
				var value = true;

				if(typeof $(submit_button).attr('value') !== undefined){
					value = $(submit_button).attr('value');
				}

				formdata.append(name, value);
			}
		}

		$.ajax({
			url: $(ele).attr('action'),
			type: typeof($(ele).attr('method')) !== 'undefined' ? $(ele).attr('method') : 'get',			
			data: formdata,
			processData: false,
			contentType: false,
			success: function(data){
				if(typeof(window[callback_name]) == 'function'){
					if(json){
						try {
							data = $.parseJSON(data);
						}
						catch(err){
							//silent
						}
						
					}
					window[callback_name](data, ele)
				}
				else{
					spinner('hide');
				}
			}
		})
	}

	//AJAX VALIDATE A FORM
	validate(ele){

		//SHOW THE SPINNER
		spinner();

		//SET THE FORMDATA
		var formdata 		= new FormData($(ele)[0]);

		//GET THE SUBMIT BUTTON
		var submit_button 	= $(document.activeElement);

		//CHECK IF THE SUBMIT BUTTON ACTUALLY IS A SUBMIT BUTTON
		if(submit_button.context.type == 'submit'){

			//CHECK THE SUBMIT BUTTONS NAME AND VALUES
			if(typeof $(submit_button).attr('name') !== 'undefined'){
				var name = $(submit_button).attr('name');
				var value = true;

				if(typeof $(submit_button).attr('value') !== 'undefined'){
					value = $(submit_button).attr('value');
				}
				formdata.append(name, value);
			}
		}

		//ADD IN THE VALIDATE PARAMETER
		formdata.append('_validate', 'true');

		//CALL THE FORM VALIDATOR
		$.ajax({
			url: $(ele).attr('validate'),
			type: typeof($(ele).attr('method')) !== 'undefined' ? $(ele).attr('method') : 'get',			
			data: formdata,
			processData: false,
			contentType: false,
			success: function(data){
				try {
			       var json_data = JSON.parse(data);
			       var valid = true;
			    } catch (e) {
			        var valid = false;
			    }

			    if(!valid){
			    	ele.replaceWith(data);
			    	spinner('hide');
			    }
			    else{
			    	$(ele).addClass('validated');
			    	ele.submit();
			    }
			}
		})
	}
}