/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
(function() {
	"use strict";
	/**
	 * Javascript to insert the link
	 * View element calls jSelectDownload when an download is clicked
	 * jSelectDownload creates the link tag, sends it to the editor,
	 * and closes the select frame.
	 **/
	window.jSelectDownload = function (id, title, catid, object, link ) {
		var editor, tag;

		if (!Joomla.getOptions('xtd-downloads')) {
			// Something went wrong!
			return false;
		}

		editor = Joomla.getOptions('xtd-downloads').editor;

		tag = '{jd_file file=='+ id + '}';

		if (window.parent.Joomla && window.parent.Joomla.editors && window.parent.Joomla.editors.instances && window.parent.Joomla.editors.instances.hasOwnProperty(editor)) {
			window.parent.Joomla.editors.instances[editor].replaceSelection(tag)
		} else {
			window.parent.jInsertEditorText(tag, editor);
		}

	    if (window.parent.Joomla.Modal) {
	      window.parent.Joomla.Modal.getCurrent().close();
	    }
		return true;
	};

	document.addEventListener('DOMContentLoaded', function(){
		// Get the elements
		var elements = document.querySelectorAll('.select-link');

		for(var i = 0, l = elements.length; l>i; i++) {
			// Listen for click event
			elements[i].addEventListener('click', function (event) {
				event.preventDefault();
				const {
	          		target
	        	} = event;
				var functionName = target.getAttribute('data-function');

				if (functionName === 'jSelectDownload') {
					// Used in xtd_contacts
					window[functionName](target.getAttribute('data-id'), target.getAttribute('data-title'), event.target.getAttribute('data-cat-id'), null, target.getAttribute('data-uri'), target.getAttribute('data-language'));
				} else {
					// Used in com_menus
					window.parent[functionName](target.getAttribute('data-id'), target.getAttribute('data-title'), target.getAttribute('data-cat-id'), null, target.getAttribute('data-uri'), target.getAttribute('data-language'));
				}

                if (window.parent.Joomla.Modal) {
                    window.parent.Joomla.Modal.getCurrent().close();
                }

			})
		}
	});
})();
