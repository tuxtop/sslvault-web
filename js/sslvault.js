/**
 * SSL Vault: jQuery plugins
 */



/**
 * Modal window
 */
$.modal = function(decl){

	// Assign id
	var item = 'modal-'+Math.ceil(Math.random()*1000);

	// Prepare html
	var html = '<div class="modal-frame" id="'+item+'">';
	if (typeof(decl.title)=='string') html+= '<div class="modal-title">'+decl.title+'</div>';
	if (typeof(decl.content)=='string') html+= '<div class="modal-content">'+decl.content+'</div>';
	html+= '</div>';
	if (decl.cache === undefined || decl.cache === true) html+= '<div class="modal-cache">'+html+'</div>';

	// Append html to document
	$('body').append(html);

	// Center item
	var posLeft = ($(document).width() - $('#'+item).width()) / 2;

	// Display item
	$('#'+item).css({ 'left': posLeft+'px' }).animate({
		'top': '30px',
		'opacity': '1'
	}, 600);

	//
	return null;

};


/**
 * Dropdown menu
 */
$.fn.dropdown = function(){

	// Generate ID
	var id = 'dropdown-'+Math.ceil(Math.random()*1000);
	$(this).data('ddplugin', id);

	// Get template
	if ($(this).data('template') === undefined) return null;
	var tpl = $('#'+$(this).data('template'))[0].outerHTML;

	// Update template
	if ($(this).data('infos'))
	{
		var d = $(this).data('infos');
		for (var key in d)
		{
			var re = new RegExp('{:'+key+'}', 'g');
			tpl = tpl.replace(re, d[key]);
		}
	}
	tpl = tpl.replace('id="'+$(this).data('template')+'"', 'id="'+id+'"');

	// Append
	$('body').append(tpl);
	$('#'+id).on('click',function(e){
		if (e.target.localName != 'a')
		{
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});

	// Catch events
	$(this).on('click',function(e){
		$('[data-role="dropdown"]').each(function(){
			$(this).removeClass('active');
			if ($(this).data('ddplugin'))
			{
				$('#'+$(this).data('ddplugin')).hide();
			}
		});
		var link = $(this).data('ddplugin');
		$('#'+link).css({
			'position': 'absolute',
			'left': $(this).offset().left+'px',
			'top': ($(this).offset().top + $(this).height())+'px',
		}).show();
		$(this).addClass('active');
		e.preventDefault();
		e.stopPropagation();
		return false;
	});

};
$(document).ready(function(){ $('[data-role="dropdown"]').each(function(){ $(this).dropdown(); }) }).on('click',function(){
	$('[data-role="dropdown"]').each(function(){
		$(this).removeClass('active');
		if ($(this).data('ddplugin'))
		{
			$('#'+$(this).data('ddplugin')).hide();
		}
	});
});


/**
 * Perform a post form submission
 */
$.AutoPostForm = function(data)
{

	var form = $('<form method="post" action=""></form>');
	for (var key in data)
	{
		form.append('<input type="hidden" name="'+key+'" value="'+data[key]+'" />');
	}
	form.submit();

}



