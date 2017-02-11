; (function ($, window, document) {
	// do stuff here and use $, window and document safely
	// https://www.phpbb.com/community/viewtopic.php?p=13589106#p13589106
	
	$("a.simpledialog").simpleDialog({
	    opacity: 0.1,
	    width: '650px',
		closeLabel: '&times;'
	});

	$('#btn_copy_lang').one('mouseup', function(){
		var $this = $(this);
		if ($this.attr('name') == 'download')
		{
			$this.hide();
		}
	});

	$('.row1').bind('mouseenter', function(){
		var $this = $(this);
	
		if(this.offsetWidth < this.scrollWidth && !$this.attr('title')){
			$this.attr('title', $this.text());
		}
	});
})(jQuery, window, document);