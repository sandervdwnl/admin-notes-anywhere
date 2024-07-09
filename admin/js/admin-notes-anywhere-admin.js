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

		// Create hidden elements to run Quill in, if not existing.
		if (!$('#quill-container').length) {
			$('#wpadminbar').after('<div id="quill-container" style="display: none;"><div id="quill-editor"></div></div>');
		}

		var anaSaveNonce = ana_data_object.nonce;

		// Quill toolbar options
		const toolbarOptions = [
			['bold', 'italic', 'underline'],
			[{ 'list': 'ordered' }, { 'list': 'bullet' }],
			['align'],
			['save']
		];

		// Initialize Quill on the hidden element
		const quill = new Quill('#quill-editor', {
			// Quill configuration options
			modules: {
				toolbar: toolbarOptions
			},
			theme: 'snow'
		});

		// Add nonce to Save-button.
		$('.ql-save').attr('data-nonce', anaSaveNonce);

		// Get the note from the db.
		$.ajax({
			url: ana_data_object.ajax_url,
			dataType: 'json',
			type: 'POST',
			data: {
				action: 'ana_get_content',
				nonce: ana_data_object.nonce,
			},	
			success: function (response) {
				if (response.success) {
					console.log(response.data.content); // Check response in console
					if (response.data.content) {
						// Check if current user is creator
						if (response.data.creator_id != response.data.current_user_id) {
							// If not, set Quill to readonly and hide toolbar.
							quill.enable(false);
							$('.ql-toolbar').hide();
						}
						// Place retrieved content in Quill editor.
						var delta = quill.clipboard.convert({ html: response.data.content });
						quill.setContents(delta);
						$('#wp-admin-bar-admin-notes-anywhere .ab-item').css('background', '#d63638');
					} else {
						console.error('No data received or not successful:', response);
					}
				} else {
					console.error(response.data);
				}
			},
			error: function (xhr, status, error) {
				console.error('AJAX  Error while getting note:', error);
			}
		});

		// Click event handler for showing the Quill editor
		$('#wp-admin-bar-admin-notes-anywhere a.ab-item').on('click', function (e) {
			e.preventDefault(); // Prevent default action

			// Toggle the display of the Quill editor container
			$('#quill-container').toggle();

		});

		$('.ql-save').on('click', function () {
			console.log('Save function triggered');
			var anaContent = $('.ql-editor').html();
			var nonce = $(this).data('nonce');

			$.ajax({
				url: ana_data_object.ajax_url,
				dataType: 'json',
				type: 'POST',
				data: {
					action: 'ana_save_content',
					content: anaContent,
					nonce: ana_data_object.nonce,
				},
				success: function (response) {
					console.log(response);

					if (response.success) {
						window.alert('Note saved');
					} else {
						window.alert('Failed to save note');
					}
				},
				error: function (xhr, status, error) {
					console.error('AJAX Error while savind note:', error);
					window.alert('AJAX Error: ' + error);
				}
			});
		});
	});	
})(jQuery);