(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	jQuery(document).ready(function ($) {

		/**
		 * Initialize Quill
		 */

		// Create hidden elements to run Quill in, if not existing.
		if (!$('#quill-container').length) {
			$('#wpadminbar').after('<div id="quill-container" style="display: none;"><div id="quill-editor"></div></div>');
		}

		// Quill toolbar options
		const toolbarOptions = [
			['bold', 'italic', 'underline'],
			[{ 'list': 'ordered' }, { 'list': 'bullet' }],
			['align'],
		];

		// Initialize Quill on the hidden element
		const quill = new Quill('#quill-editor', {
			// Quill configuration options
			modules: {
				toolbar: toolbarOptions
			},
			theme: 'snow'
		});

		// Add save button, public checkbox and response to toolbar, if user is admin.
		if (ana_data_object.is_admin === '1') {
			$('.ql-toolbar').append('<button type="button" class="ql-save" aria-pressed="false" aria-label="save">Save</button>');
			$('.ql-toolbar').append('<input type="checkbox" id="ana-public-checkbox" name="public-note" value="public"><span class="ana-checkbox-text">Public</span>');
			$('.ql-toolbar').append('<div class="ql-response">&#10004 Note saved</div>');

			// Add nonce to Save-button.
			var anaSaveNonce = ana_data_object.nonce;
			$('.ql-save').attr('data-nonce', anaSaveNonce);
		}

		// Add event listener for the dynamically added checkbox and save status to variable.
		var public_checkbox_checked = 0;
		$('#ana-public-checkbox').change(function () {
			if ($(this).is(':checked')) {
				public_checkbox_checked = 1;
			} else {
				public_checkbox_checked = 0;
			}
		});

		// Define the new CSS rule to change pseudo-element when needed (this cannot be done directly).

		var newPseudoStyle = `
			#wp-admin-bar-admin-notes-anywhere a::after {
				right: 0;
				content: "\\f160"; /* New Lock Dashicon */
		}
			#wp-admin-bar-admin-notes-anywhere a {
				pointer-events: none;
				title
		}
		`;

		/**
		 * Retrieve the note for the current page.
		 */

		var isAdmin = '0';
		var hiddenMode = '0';
		var lockedMode = '0';
		var hasNote = '0';
		var readOnlyMode = '0';
		var isPublic = '0';
		var isCreator = '0';

		// Get the note from the db.
		$.ajax({
			url: ana_data_object.ajax_url,
			dataType: 'json',
			type: 'POST',
			data: {
				action: 'ana_get_content',
				nonce: ana_data_object.check_ana_get_nonce,
			}
		})
		.done(function (response) {
			if (response.success) {
				// Set vars.
				if (response.data.is_admin == '1') {
					isAdmin = '1'
				}
				if (response.data.content) {
					hasNote = '1';
					if (response.data.is_creator == '1') {
						isCreator = '1';
					}
					if (response.data.public == '1') {
						isPublic = '1';
					}
				}

				// Define how to display Quill (hidden, locked, readonly or default).
				if (hasNote == '0' && isAdmin == '0') {
					// Hide Quill 
					hiddenMode = '1';
				}
				else if (hasNote == '1' && isAdmin == '0' && isPublic == '0') {
					hiddenMode = '1';
				}
				if (hasNote == '1' && isAdmin == '1' && isCreator == '0') {
					// Lock Quill
					lockedMode = '1';
				}
				if (hasNote == '1' && isCreator == '0' && isAdmin == '0' && isPublic == '1') {
					// Readonly Quill
					readOnlyMode = '1';
				}
				if (hiddenMode == '1') {
					// Hide Quill
					$('#wp-admin-bar-admin-notes-anywhere').remove();
				}
				else if (lockedMode == '1') {
					// Display lock.
					var style = document.createElement('style');
					style.textContent = newPseudoStyle;
					document.head.appendChild(style);
				}
				else {
					// Load Quill + content
					if (readOnlyMode == '1') {
						// Display note in read-only mode and hide toolbar.
						quill.enable(false);
						$('.ql-toolbar').hide();
					}
				}
				// Quill is hidden by default. When Quill is loaded in the correct mode, show Quill in admin bar.
				$('#wp-admin-bar-admin-notes-anywhere').css(	'visibility', 'visible');
				// Place retrieved content in Quill editor.
				var delta = quill.clipboard.convert({ html: response.data.content });
				quill.setContents(delta);
				$('#wp-admin-bar-admin-notes-anywhere .ab-item').css('background', '#d63638');
				if (response.data.public == "1") {
					$('#ana-public-checkbox').prop('checked', true);
				} else {
					$('#ana-public-checkbox').prop('checked', false);
				}
				
			} else {
				console.error('Error while receiving data:', response);
			}
		})
		.fail(function (xhr, status, error) {
			console.error('AJAX Error:', error);
		});

		/**
		 * Open/close Quill editor.
		 */

		// Click event handler for showing the Quill editor.
		$('#wp-admin-bar-admin-notes-anywhere a.ab-item').on('click', function (e) {
			e.preventDefault(); // Prevent default action

			// Toggle the display of the Quill editor container.
			$('#quill-container').toggle();
		});

		/**
		 * Save note in DB.
		 */

		$('.ql-save').on('click', function () {
			var anaContent = $('.ql-editor').html();
			var nonce = $(this).data('nonce');
			// var public_checkbox_checked = $('#ana-public').prop('checked');
			$.ajax({
				url: ana_data_object.ajax_url,
				dataType: 'json',
				type: 'POST',
				data: {
					action: 'ana_save_content',
					content: anaContent,
					public: public_checkbox_checked,
					nonce: ana_data_object.check_ana_save_nonce,
				}
			})
				.done(function (response) {
					if (response.success) {
						$('.ql-save').css('visibility', 'hidden');
						$('#ana-public-checkbox').css('visibility', 'hidden');
						$('.ana-checkbox-text').css('visibility', 'hidden');
						$('.ql-response').css('visibility', 'visible');
						setTimeout(function () {
							$('.ql-save').css('visibility', 'visible');
							$('#ana-public-checkbox').css('visibility', 'visible');
							$('.ana-checkbox-text').css('visibility', 'visible');
							$('.ql-response').css('visibility', 'hidden');
						}, 3000);
					} else {
						window.alert('Failed to save note: ' + response.data.message);
					}
				})
				.fail(function (xhr, status, error) {
					console.error('AJAX Error:', nonce);
					window.alert('AJAX Error: ' + error);
				});
		});
	});
})(jQuery);