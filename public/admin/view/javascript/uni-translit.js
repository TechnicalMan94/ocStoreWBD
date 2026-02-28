function uniTranslit(source, destination, length, replace, lang_id) {
	let str = $('input[name="'+source+'"]').val(), delimiter = '-', link = '';
		
	if(typeof(length) == 'undefined') length = 255;
	if(typeof(replace) == 'undefined') replace = false;
	if(typeof(lang_id) == 'undefined') lang_id = $('input[name="'+source+'"]').attr('name').split('][')[0].replace(/\D+/g,'');

	if (str != '' && lang_id) {
		const arr = {'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'e', 'ж': 'zh', 'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o', 'п': 'p', 'р': 'r',
				'с': 's', 'т': 't',	'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ь': delimiter, 'ы': 'y', 'ъ': delimiter, 'э': 'e', 'ю': 'yu', 'я': 'ya'}
		
		str = str.toLowerCase();
		
		for (var i = 0; i < str.length; i++) {
			if (/[а-яё]/.test(str.charAt(i))) {
				link += arr[str.charAt(i)];
			} else if (/[a-z0-9]/.test(str.charAt(i))) {
				link += str.charAt(i);
			} else {
				if (link.slice(-1) !== delimiter) link += delimiter;
			}
		}
			
		link = link.slice(0, length);
			
		if(link.slice(-1) == delimiter) link = link.slice(0, link.length-1);
			
		$('input[name^="'+destination+'"]').each(function() {
			const $this = $(this), this_lang_id = $this.attr('name').split('][')[1].replace(/\D+/g,'');
	
			if(this_lang_id == lang_id && ($this.val() == '' || replace)) {
				$this.val(link);
			}
		});
	}
};