(function ($) {
	$(function () {
		var $fields = $('.ps-hyphenate-block-types');

		if (!$fields.length || !$.fn.select2) {
			return;
		}

		$fields.select2({
			placeholder: $fields.data('placeholder'),
			tags: true,
			tokenSeparators: [',', ' ', '\n'],
			width: '100%'
		});
	});
})(jQuery);