function config_select2(elId){
	var current_term=null;	
	
	$(elId).select2({
		placeholder: setPlaceholder(elId),
		matcher: matchCustom,

		language: {
			noResults: function (params) {
				current_term=null;
				return "Adresse inexistante";
			},
			searching: function (params) {
				// Intercept the query as it is happening
				query = params;
				current_term=params.term;
				// Change this to be appropriate for your application
				//return 'Searching…';
				return 'Recherche…';
			}
		},

		templateResult: function (item) {
			// No need to template the searching text
			if (item.loading) {
				return item.text;
			}
			var term = query.term || '';
			var $result = markMatch(item.text, term);
			return $result;
		},		
		
		sorter: function(data) {
			// Sort data using lowercase comparison 
			// data is an array containing data objects which have all the property text
			// Sort the data objects first on position of the search term in their .text property.
			// e.g. 'ab' before 'ba'
			// If data objects have the search term on the same position sort alphabetically
		
			data=data.sort(function (a, b) {
				a = a.text.toLowerCase();
				b = b.text.toLowerCase();
				if (a.indexOf(current_term) > b.indexOf(current_term)) {
					return 1;
				} else if (a.indexOf(current_term) < b.indexOf(current_term)) {
					return -1;
				}
				//Search term on same position. Sort alphabetically
				if (a > b) {
					return 1;
				} else if (a < b) {
					return -1;
				}
				return 0;
			});
			// After sorting the options the first option is not necessarily
			// visible in the dropdown because it will automatically scrolled down
			// to show the selected option
			// Ensure that the first option is in view in the dropdown by deselecting;
			$(elId).prop('selectedIndex', '');  //unselects in dropdown
			//$(elId).trigger('change'); // if you don't trigger change option shown as selected will not change until you select another option 
			$(elId).scrollTop(1); //Note: scrollTop(0) does not work probably because first option is empty(?)
			return data;
		}		
	});
}

function markMatch (text, term) {
	// Find where the match is
	var match = text.toUpperCase().indexOf(term.toUpperCase());
	var $result = $('<span></span>');
	// If there is no match, move on
	if (match < 0) {
		return $result.text(text);
	}
	// Put in whatever text is before the match
	$result.text(text.substring(0, match));
	// Mark the match. Set in css for the class e.g font-weight: bold to mark matches
	var $match = $('<span class="select2-rendered__match"></span>');
	$match.text(text.substring(match, match + term.length));
	// Append the matching text
	$result.append($match);
	// Put in whatever is after the match
	$result.append(text.substring(match + term.length));
	return $result;
}

function matchCustom(params, data) {
    // If there are no search terms, return all of the data
	if ($.trim(params.term) === '') {
		return data;
    }
    // Do not display the item if there is no 'text' property
    if (typeof data.text === 'undefined') {
		return null;
    }
		
    // `params.term` should be the term that is used for searching
    // `data.text` is the text that is displayed for the data object
	// Case insensitive search
    if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
		return data;
    }
    // Return `null` if the term should not be displayed
    return null;
}

function setPlaceholder(elId){
	$(elId).prepend("<option></option>"); //select2 requires blank option for placeholder to appear. See https://select2.org/placeholders  
	var placeholder = $(elId+" option[value='-1']").detach(); //remove option element from DOM but keep available in JQuery
	// Overrule default select2 grey color #999 for placeholder: 
	//$(elId).siblings('.select2-container').find('.select2-selection__placeholder').css('color', 'inherit !important;');
	return placeholder.text()
}