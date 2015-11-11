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
	var html = '<div class="wmodal-frame" id="'+item+'">';
	if (typeof(decl.title)=='string') html+= '<div class="wmodal-title">'+decl.title+'</div>';
	if (typeof(decl.content)=='string') html+= '<div class="wmodal-content">'+decl.content+'</div>';
	html+= '</div>';
	if (decl.cache === undefined || decl.cache === true) html+= '<div class="wmodal-cache">'+html+'</div>';

	// Append html to document
	$('body').append(html);

	// Center item
	var posLeft = ($(document).width() - $('#'+item).width()) / 2;

	// Display item
	$('#'+item).css({ 'left': posLeft+'px' }).animate({
		'top': '30px',
		'opacity': '1'
	}, 600).find('[data-role="close"]').on('click',function(){
		$(this).parents('.wmodal-frame').animate({
			'top': '20px',
			'opacity': '0'
		}, 600, 'swing', function(){
			$(this).next('.wmodal-cache').remove();
			$(this).remove();
		});
	});

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
			if (typeof(d[key])=='object') d[key] = JSON.stringify(d[key]).replace(/"/g, '&quot;');
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
		var margin = 0;
		if (!$(this).hasClass('btn-sm')) margin = 2;
		var link = $(this).data('ddplugin');
		$('#'+link).css({
			'position': 'absolute',
			'left': $(this).offset().left+'px',
			'top': ($(this).offset().top + $(this).height() + margin)+'px',
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

};


/**
 * Manage file input
 */
$.fn.inputfile = function()
{

	// No need to rebuil inputfile
	if ($(this).data('plugin.inputfile')) return null;

	// Append true form input
	var inp = $('<input type="file" class="hidden-input-file" name="'+$(this).data('name')+'" />');
	if ($(this).data('accept')) inp.attr('accept', $(this).data('accept'));
	if ($(this).data('preview')) inp.data('plugin.inputfile.preview', $(this).data('preview')).on('change',function(){
		var callback = $(this).data('plugin.inputfile.preview');
		var item = this.files[0];
		var fr = new FileReader();
		fr.onload = window[callback];
		fr.readAsDataURL(item);
	});
	inp.insertAfter($(this));
	$(this).data('plugin.inputfile', inp);

	// Click button
	$(this).on('click',function(){
		console.log(this);
		$(this).data('plugin.inputfile').trigger('click');
	}).trigger('click');

};
$(document).ready(function(){
	$(document).on('click', '[data-role="inputfile"]', function(){ $(this).inputfile(); });
});


/**
 * Pseudo heredoc
 */
$.heredoc = function(func, vars)
{

	// Get function heredoc
	var a = func.toString().replace(/\n/g, '\\n').replace(/^function\s*\(\)\s*\{\/\*(\w+)(.*)\1\*\/\}\s*$/, '$2');
	for (var key in vars)
	{
		var val = vars[key];
		if (typeof(val)=='object')
		{
			for (var skey in val)
			{
				var k = new RegExp('\\{:'+key+'\\['+skey+'\\]\\}','g');
				a = a.replace(k, val[skey]);
			}
		}
		else
		{
			var k = new RegExp('\{:'+key+'\}','g');
			a = a.replace(k, val);
		}
	}
	a = a.replace(/{:\w+}/g, '');
	return a.replace(/\\n/g, '\n');

};



