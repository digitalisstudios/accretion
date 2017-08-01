function spinner(show_hide){
	if(show_hide == 'hide'){
		$('.accretion-spinner').delay(100).fadeOut(100, function(){
			$(this).remove();
		});
	}
	else{
		if($('.accretion-spinner').length == 0){
			$('body').append('<div class="accretion-spinner" style="display:none"><div class="accretion-spinner-loader"></div>');
			$('.accretion-spinner').fadeIn(100);
		}
	}
}

$.fn.spinner = function(options){

	var width 	= this.width();
	var height 	= this.height();

	this.prepend('<div style="position:absolute; z-index:99; width:'+width+'px; height:'+height+'px; min-height:50px; background:rgba(255,255,255,0.8);"><div class="accretion-spinner-loader"></div></div>');

	return this;
}

$(document).off('click.ShowLoader').on('click.ShowLoader', '.show-loader', function(){
	spinner();
});

class Accretion{
	constructor(){
		//console.log('called');
		//this.Guid = new Guid();
		//this.Modal = new Modal();
		//this.Table = new Table();
		//this.Ajax = new Ajax();

	}
}

$(window).load(function(){
	spinner('hide');
	Accretion = new Accretion();
	Accretion.Guid = new Guid();
	Accretion.Modal = new Modal();
	Accretion.Ajax = new Ajax();
	Accretion.AjaxForm = new AjaxForm();
	Accretion.AjaxLink = new AjaxLink();
	Accretion.AjaxUpload = new AjaxUpload();
	Accretion.Table = new Table();
	Accretion.TableFilterable = new TableFilterable();
	Accretion.TableSearchable = new TableSearchable();
	Accretion.TableSelectable = new TableSelectable();
});

