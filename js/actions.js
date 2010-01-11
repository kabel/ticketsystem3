jQuery(document).ready(function($) {
	$(".action input:radio").click(function() {
		$(this).siblings("input, select").attr("disabled", false);
		var otherActions = $(this).parent().siblings(".action");
		$("input[name!=action], select", otherActions).attr("disabled", true);
	});
	$(".action input:radio:checked").click();
});
