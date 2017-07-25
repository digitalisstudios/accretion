class AjaxUpload extends Ajax {
	constructor(){
		super();
		$(document).on('change', '.ajax_file_upload', function(e){
			callback_name 	= $(this).attr('data-callback');
			var ele 		= $(this);
			Accretion.Ajax.AjaxUpload.upload(e, $(this).data(), function(data){
				window[callback_name](data, ele)
			});
		});
	}

	//UPLOAD FILES VIA AJAX
	upload(event, meta, callback, add_files){

		//STOP STUFF FROM HAPPENING
	  	event.stopPropagation();
	    event.preventDefault();

	    if(typeof(add_files) !== 'undefined'){
	    	files = add_files;
	    }
	    else{
	    	
	    	//GET THE FILES
			files = event.target.files;
	    }    
	    
	    //SHOW THE SPINNER
	    spinner();

	    //ADD THE FILES
	    var data = new FormData();
	    $.each(files, function(key, value){
	        data.append(key, value);
	    });

	    //ADD THE META DATA
	    $.each(meta, function(k, v){
	    	data.append(k, v);
	    });   

	    //SEND IT OFF
	    $.ajax({
	        url 		: '/Ajax/upload_file',
	        type 		: 'POST',
	        data 		: data,
	        cache 		: false,
	        dataType 	: 'json',
	        processData : false, // Don't process the files
	        contentType : false, // Set content type to false as jQuery will tell the server its a query string request
	        success 	: function(data, textStatus, jqXHR) {
	            if(typeof data.error === 'undefined' && typeof callback == 'function'){
	            	spinner('hide');
	               	callback(data);
	            }
	        }
	    });
	}
}