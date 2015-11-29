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
	if (decl.cache === undefined || decl.cache === true) html = '<div class="wmodal-cache"></div>'+html;

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
			$(this).prev('.wmodal-cache').remove();
			$(this).remove();
		});
	});

	//
	return null;

};
$(document).ready(function(){
	$(document).on('click','.wmodal-cache',function(){
		$(this).next('.wmodal-frame').animate({
			'top': '20px',
			'opacity': '0'
		}, 600, 'swing', function(){
			$(this).prev('.wmodal-cache').remove();
			$(this).remove();
		});
	});
});


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
		var left = $(this).offset().left;
		if (left+$('#'+link).width()>parseInt(window.innerWidth,10)) left-= $('#'+link).width() - $(this).width();
		$('#'+link).css({
			'position': 'absolute',
			'left': left,
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


/**
 * Copy to clipboard content in the first 'pre' block in a modal window
 */
function copy_to_clipboard(e)
{
	var container = $(e).parents('.wmodal-content').find('pre');
	var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(container[0]);
        selection.removeAllRanges();
        selection.addRange(range);
	document.execCommand('copy');
	$.modal({ content: '<p>Data copied to your clipboard.</p><p class="text-center"><span data-role="close" class="btn btn-default">OK</span></p>' });
}


/**
 * Show argument in a modal with copy to clopboard button
 */
function clip_modal(content)
{
	$.modal({
		content: '<pre>'+content+'</pre><p class="text-right"><a onclick="javascript:copy_to_clipboard(this);" class="btn btn-primary">Copy to clipboard</a> <span data-role="close" class="btn btn-default">Close</span></p>'
	});
}


/**
 * Manage tags list
 */
$.fn.appendTag = function(tgname){

	// Only for $().tags() declared
	if (!$(this).data('tgplugin')) return false;
	var o = $(this).data('tgplugin');

	// Check if we need to add it
	var list = $(this).val().split(/,/);
	var inList = false;
	for (var i=0; i<list.length; i++)
	{
		if (list[i].toUpperCase()==tgname.toUpperCase())
		{
			inList = true;
			break;
		}
	}
	if (inList) return false;

	// 
	var tag = $('<span data-value="'+tgname+'" class="tag">'+tgname+' <span class="fa fa-times" data-role="close"></span></span>');
	tag.insertBefore(o.inp),

	// Append new list
	list.push(tgname);
	$(this).val(list.join(','));

};
$.fn.removeTag = function(){

	// Only for $().tags() declared
	if ($(this).data('role')!='close') return false;
	if (!$(this).parents('.tags-input').length) return false;

	// 
	var tag = $(this).parents('.tag');
	var ref = $(this).parents('.tags-input').data('tgplugin');
	var tgname = tag.data('value');

	// Rebuild new field
	var tmp = [];
	var list = ref.val().split(/,/);
	for (var i=0; i<list.length; i++)
	{
		if (!list[i] || list[i].toUpperCase()==tgname.toUpperCase()) continue;
		tmp.push(list[i]);
	}
	ref.val(tmp.join(','));

	//
	tag.remove();

};
$.fn.tags = function(){

	// Attach extra actions
	$(this).appendTag = function(a){ console.log(a); };

	// Append fake field and hide true field
	var nfield = $('<div class="form-control tags-input"><input type="input" /></div>');
	nfield.insertBefore($(this));
	nfield.data('tgplugin',$(this));
	$(this).css({ 'position':'absolute', 'visibility':'hidden', 'top':'0px' });
	nfield.on('click',function(){ $(this).find('input').focus(); });

	// Fake field actions
	var inp = nfield.find('input');
	inp.data('tgplugin',$(this));
	inp.on('blur',function(){
		if ($(this).val())
		{
			$(this).data('tgplugin').appendTag($(this).val());
			$(this).val('').focus();
			if ($(this).data('tgplugin.propals').length) $(this).data('tgplugin.propals').hide();
		}
	});
	if ($(this).data('autocomplete'))
	{

		// Append propals box
		var props = $('<div class="tags-propals"></div>');
		$('body').append(props);
		inp.data('tgplugin.propals', props);

		// Search engine
		inp.on('keyup',function(){

			var ref = $(this).data('tgplugin');
			var o = $(this).parents('.tags-input');
			var p = o.offset();
			var posTop = parseInt(o.height(),10) + parseInt(p.top,10);
			var posLeft = parseInt(p.left,10);

			if ($(this).val().length<2)
			{
				props.hide();
				return true;
			}
			$.ajax({
				'url': ref.data('autocomplete'),
				'context': $(this),
				'method': 'POST',
				'data': {
					'filters': $(this).data('tgplugin').val().split(/,/),
					'search': $(this).val()
				},
				'complete': function(xhr){
					var props = $(this).data('tgplugin.propals');
					props.css({
						'position': 'absolute',
						'top': posTop,
						'left': posLeft
					});
					var list = JSON.parse(xhr.responseText);
					if (xhr.status==200)
					{
						props.html('<ul></ul>');
						for (var i=0; i<list.length; i++)
						{
							props.find('ul').append('<li data-value="'+list[i].value+'">'+list[i].match+'</li>');
						}
						console.log(props.get(0));
						props.show();
					}
					else
					{
						props.hide();
					}
				}
			});

		});

	}

	// Declare plugin
	$(this).data('tgplugin',{ 'fake':nfield, 'inp':inp });

	// Append tags
	var list = $(this).val().split(/,/);
	$(this).val('');
	for (var i=0; i<list.length; i++)
	{
		$(this).appendTag(list[i]);
	}

};
$(document).ready(function(){
	$('[data-role=tags]').each(function(){
		$(this).tags();
	});
	$(document).on('click','.tags-input .tag [data-role=close]',function(){
		$(this).removeTag();
	});
});




