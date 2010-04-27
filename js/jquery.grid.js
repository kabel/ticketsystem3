if (!jQuery) {
	jQuery = WDN.jQuery;
}

(function($) {
	$.fn.grid = function(url, options) {
		var opts = $.extend({}, $.fn.grid.defaults, options);
		
		this.each(function() {
			var dataTable = $('table.data', this);
			var actionTable = $('table.actions', this);
			
			var self = this;
			var reload = function(url) {
				if (opts.useAjax) {
					var mask = $('<div class="loading-mask"><p class="loader">Loading...</p></div>').width($(self).width()).height($(self).height());
					$(self).before(mask);
					$(self).load(url + (url.match(new RegExp('\\?')) ? '&ajax=true' : '?ajax=true'), function() {
						$(self).prev('.loading-mask').remove();
						initGrid();
					});
				} else {
					window.location.href = url;
				}
			};
			
			var addVarToUrl = function(varName, varValue) {
				if (url[url.length-1] != '/') {
					url+= '/';
				}
				var re = new RegExp('\/('+varName+'\/.*?\/)');
				var parts = url.split(new RegExp('\\?'));
				url = parts[0].replace(re, '/');
				if (varValue) {
					url+= varName+'/'+varValue+'/';
				}
				if(parts.length>1) {
		            url+= '?' + parts[1];
		        }
				
				return url;
			};
			
			var setPage = function(pg) {
				reload(addVarToUrl(opts.pageVar, pg));
			};
			
			var doFilter = function(dataTable) {
				var filters = $('.filters input, .filters select', dataTable).filter(function() {
					return ($(this).val() && $(this).val().length);
				});
				reload(addVarToUrl(opts.filterVar, $.base64Encode(filters.serialize())));
			};
			
			var initGrid = function() {
				var dataTable = $('table.data', self);
				var actionTable = $('table.actions', self);
				
				$('tbody tr', dataTable).hover(function() {
					$(this).toggleClass('on-mouse');
				}).click(function() {
					if ($(this).attr('title')) {
						window.location.href = $(this).attr('title');
					}
				});
				
				$('.pager .prev, .pager .next', actionTable).click(function() {
					setPage($(this).attr('title'));
				});
				$('.pager .page', actionTable).keypress(function(evt) {
					if (evt.which == 13) {
						setPage($(this).val());
					}
				});
				$('.pager .limit', actionTable).change(function() {
					reload(addVarToUrl(opts.limitVar, $(this).val()));
				});
				
				$('.filter-actions .reset', actionTable).click(function() {
					reload(addVarToUrl(opts.filterVar));
				});
				$('.filter-actions .filter', actionTable).click(function() {
					doFilter(dataTable);
				});
				
				$('.headings a', dataTable).click(function() {
					if (opts.useAjax) {
						reload(this.href);
						return false;
					}
				});
				$('.filters input, .filters select', dataTable).keypress(function(evt) {
					if (evt.which == 13) {
						doFilter(dataTable);
					}
				});
			};
			
			initGrid();
		});
		
		return this;
	};
	
	$.fn.grid.defaults = {
		pageVar: 'pg',
		sortVar: 'sort',
		limitVar: 'ps',
		dirVar: 'dir',
		filterVar: 'filter',
		useAjax: false
	};
})(jQuery);