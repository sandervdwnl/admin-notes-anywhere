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
		if(ana_data_object.is_admin === '1') {
			$('.ql-toolbar').append('<button type="button" class="ql-save" aria-pressed="false" aria-label="save">Save</button>');
			$('.ql-toolbar').append('<input type="checkbox" id="ana-public-checkbox" name="public-note" value="public"><span class="ana-checkbox-text">Public</span>');
			$('.ql-toolbar').append('<div class="ql-response">&#10004 Note saved</div>');
			
			// Add nonce to Save-button.
			var anaSaveNonce = ana_data_object.nonce;
			$('.ql-save').attr('data-nonce', anaSaveNonce);
		} 

		// Add event listener for the dynamically added checkbox
		var public_checkbox_checked = 0;
		$('#ana-public-checkbox').change(function() {
			if ($(this).is(':checked')) {
				public_checkbox_checked = 1;
			} else {
				public_checkbox_checked = 0;
			}
		});
		
		/**
		 * Retrieve the note for the current page.
		 */

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
				if (response.data.content) {
					// Check if current user is creator.
					if (response.data.creator_id != response.data.current_user_id) {
						// If not, set Quill to readonly and hide toolbar.
						quill.enable(false);
						$('.ql-toolbar').hide();
					}
					// Place retrieved content in Quill editor.
					var delta = quill.clipboard.convert({ html: response.data.content });
					quill.setContents(delta);
					$('#wp-admin-bar-admin-notes-anywhere .ab-item').css('background', '#d63638');
					// console.log(response.data.public);
					if(response.data.public === "1" ) {
						$('#ana-public-checkbox').prop('checked', true);
					} else {
						$('#ana-public-checkbox').prop('checked', false);
					}
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
			var public_checkbox_checked = $('#ana-public').prop('checked');
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
					setTimeout(function() {
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