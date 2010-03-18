var jQuery = jQuery || WDN.jQuery;

jQuery(document).ready(function($) {
	var removeFunc = function() {
		var parent = $(this).closest("tr");
		var attrName = parent.attr("class");
		if (parent.prevAll("." + attrName).length) {
			parent.remove();
		}  else if (parent.next("." + attrName).length) {
			var parentSib = parent.next("." + attrName); 
			$(".filter input, .filter select", parent).val($(".filter input, .filter select", parentSib).val());
			parentSib.remove();
		} else {
			var inputs = $("input, select", parent).not(".add-filter, .remove-filter");
			inputs.filter("input").val("");
			inputs.filter("select").each(function() {
				this.selectedIndex = 0;
			});
			inputs.attr("disabled", true);
			this.disabled = true;
			$(this).siblings(".add-filter").attr("disabled", false);
		}
	};
	
	$("#filters input.add-filter").click(function() {
		var parent = $(this).closest("tr");
		var attrName = parent.attr("class");
		var inputs = $("input, select", parent).not(".add-filter, .remove-filter");
		if (inputs.attr("disabled")) {
			inputs.attr("disabled", false);
			$(this).siblings(".remove-filter").attr("disabled", false);
		} else {
			var filterEle = $(".filter input, .filter select", parent).clone();
			filterEle.attr("id", "");
			filterEle.val("");
			var row = $('<tr class="' + attrName + '"><th colspan="2">or</th></tr>');
			var filterTd = $('<td class="filter" />');
			filterTd.append(filterEle);
			row.append(filterTd);
			var actionTd = $('<td class="actions" />');
			var actionButton = $('<input class="remove-filter" type="button" value="-" />').click(removeFunc);
			actionTd.append(actionButton);
			row.append(actionTd);
			parent.after(row);
		}
	});
  
	$("#filters input.remove-filter").click(removeFunc);
	
	$("#query legend.foldable a").click(function() {
		$(this).closest("fieldset").toggleClass("collapsed");
		return false;
	});
	
	$("#columns").toggleClass("collapsed");
});
