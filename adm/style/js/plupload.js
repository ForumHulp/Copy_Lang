/* global phpbb, plupload, attachInline */

plupload.addI18n(phpbb.plupload.i18n);
phpbb.plupload.ids = [];

(function($) {  // Avoid conflicts with other libraries

'use strict';

/**
 * Set up the uploader.
 */
phpbb.plupload.initialize = function() {
	// Initialize the Plupload uploader.
	phpbb.plupload.uploader.init();

	// Set attachment data.
	phpbb.plupload.setData(phpbb.plupload.data);
	phpbb.plupload.updateMultipartParams(phpbb.plupload.getSerializedData());

	// Only execute if Plupload initialized successfully.
	phpbb.plupload.uploader.bind('Init', function() {
		phpbb.plupload.form = $(phpbb.plupload.config.form_hook)[0];

		// Hide the basic upload panel and remove the attach row template.
		$('#attach-panel-basic').remove();
		// Show multi-file upload options.
		$('#attach-panel-multi').show();
	});

	phpbb.plupload.uploader.bind('PostInit', function() {

		// Ensure "Add files" button position is correctly calculated.
		if ($('#attach-panel-multi').is(':visible')) {
			phpbb.plupload.uploader.refresh();
		}
		$('[data-subpanel="attach-panel"]').one('click', function() {
			phpbb.plupload.uploader.refresh();
		});
	});
};

/**
 * Unsets all elements in the object uploader.settings.multipart_params whose keys
 * begin with 'attachment_data['
 */
phpbb.plupload.clearParams = function() {
	var obj = phpbb.plupload.uploader.settings.multipart_params;
	for (var key in obj) {
		if (!obj.hasOwnProperty(key) || key.indexOf('attachment_data[') !== 0) {
			continue;
		}

		delete phpbb.plupload.uploader.settings.multipart_params[key];
	}
};

/**
 * Update uploader.settings.multipart_params object with new data.
 *
 * @param {object} obj
 */
phpbb.plupload.updateMultipartParams = function(obj) {
	var settings = phpbb.plupload.uploader.settings;
	settings.multipart_params = $.extend(settings.multipart_params, obj);
};

/**
 * Convert the array of attachment objects into an object that PHP would expect as POST data.
 *
 * @returns {object} An object in the form 'attachment_data[i][key]': value as
 * 	expected by the server
 */
phpbb.plupload.getSerializedData = function() {
	var obj = {};
	for (var i = 0; i < phpbb.plupload.data.length; i++) {
		var datum = phpbb.plupload.data[i];
		for (var key in datum) {
			if (!datum.hasOwnProperty(key)) {
				continue;
			}

			obj['attachment_data[' + i + '][' + key + ']'] = datum[key];
		}
	}
	return obj;
};

/**
 * Get the index from the phpbb.plupload.data array where the given
 * attachment id appears.
 *
 * @param {int} attachId The attachment id of the file.
 * @returns {bool|int} Index of the file if exists, otherwise false.
 */
phpbb.plupload.getIndex = function(attachId) {
	var index = $.inArray(Number(attachId), phpbb.plupload.ids);
	return (index !== -1) ? index : false;
};

/**
 * Set the data in phpbb.plupload.data and phpbb.plupload.ids arrays.
 *
 * @param {Array} data	Array containing the new data to use. In the form of
 * array(index => object(property: value). Requires attach_id to be one of the object properties.
 */
phpbb.plupload.setData = function(data) {
	var idate = phpbb.plupload.data.length;
	
	(data.length) ? phpbb.plupload.data[idate] = data[0] : null;

	for (var i = 0; i < data.length; i++) {
		phpbb.plupload.ids.push(Number(data[i].attach_id));
	}
};

/**
 * Update the attachment data in the HTML and the phpbb & phpbb.plupload objects.
 *
 * @param {Array} data			Array containing the new data to use.
 * @param {string} action		The action that required the update. Used to update the inline attachment bbcodes.
 * @param {int} index			The index from phpbb.plupload_ids that was affected by the action.
 * @param {Array} downloadUrl	Optional array of download urls to update.
 */
phpbb.plupload.update = function(data, action, index, downloadUrl) {

	phpbb.plupload.setData(data);
	phpbb.plupload.clearParams();
	phpbb.plupload.updateMultipartParams(phpbb.plupload.getSerializedData());
};


/**
 * Get Plupload file objects based on their upload status.
 *
 * @param {int} status Plupload status - plupload.DONE, plupload.FAILED,
 * plupload.QUEUED, plupload.STARTED, plupload.STOPPED
 *
 * @returns {Array} The Plupload file objects matching the status.
 */
phpbb.plupload.getFilesByStatus = function(status) {
	var files = [];

	$.each(phpbb.plupload.uploader.files, function(i, file) {
		if (file.status === status) {
			files.push(file);
		}
	});
	return files;
};

/**
 * Check whether the user has reached the maximun number of files that he's allowed
 * to upload. If so, disables the uploader and marks the queued files as failed. Otherwise
 * makes sure that the uploader is enabled.
 *
 * @returns {bool} True if the limit has been reached. False if otherwise.
 */
phpbb.plupload.handleMaxFilesReached = function() {
	// If there is no limit, the user is an admin or moderator.
	if (!phpbb.plupload.maxFiles) {
		return false;
	}

	if (phpbb.plupload.maxFiles <= phpbb.plupload.ids.length) {
		// Fail the rest of the queue.
		phpbb.plupload.markQueuedFailed(phpbb.plupload.lang.TOO_MANY_ATTACHMENTS);
		// Disable the uploader.
		phpbb.plupload.disableUploader();
		phpbb.plupload.uploader.trigger('Error', { message: phpbb.plupload.lang.TOO_MANY_ATTACHMENTS });

		return true;
	} else if (phpbb.plupload.maxFiles > phpbb.plupload.ids.length) {
		// Enable the uploader if the user is under the limit
		phpbb.plupload.enableUploader();
	}
	return false;
};

/**
 * Disable the uploader
 */
phpbb.plupload.disableUploader = function() {
	$('#add_files').addClass('disabled');
	phpbb.plupload.uploader.disableBrowse();
};

/**
 * Enable the uploader
 */
phpbb.plupload.enableUploader = function() {
	$('#add_files').removeClass('disabled');
	phpbb.plupload.uploader.disableBrowse(false);
};

/**
 * Mark all queued files as failed.
 *
 * @param {string} error Error message to present to the user.
 */
phpbb.plupload.markQueuedFailed = function(error) {
	var files = phpbb.plupload.getFilesByStatus(plupload.QUEUED);

	$.each(files, function(i, file) {
		$('#' + file.id).find('.file-progress').hide();
		phpbb.plupload.fileError(file, error);
	});
};

/**
 * Marks a file as failed and sets the error message for it.
 *
 * @param {object} file		Plupload file object that failed.
 * @param {string} error	Error message to present to the user.
 */
phpbb.plupload.fileError = function(file, error) {
	file.status = plupload.FAILED;
	file.error = error;
	$('#' + file.id).find('.file-status')
		.addClass('file-error')
		.attr({
			'data-error-title': phpbb.plupload.lang.ERROR,
			'data-error-message': error
		});
};


/**
 * Set up the Plupload object and get some basic data.
 */
phpbb.plupload.uploader = new plupload.Uploader(phpbb.plupload.config);
phpbb.plupload.initialize();


/**
 * Fires when an error occurs.
 */
phpbb.plupload.uploader.bind('Error', function(up, error) {
	error.file.name = plupload.xmlEncode(error.file.name);

	// The error message that Plupload provides for these is vague, so we'll be more specific.
	if (error.code === plupload.FILE_EXTENSION_ERROR) {
		error.message = plupload.translate('Invalid file extension:') + ' ' + error.file.name;
	} else if (error.code === plupload.FILE_SIZE_ERROR) {
		error.message = plupload.translate('File too large:') + ' ' + error.file.name;
	}
	phpbb.alert(phpbb.plupload.lang.ERROR, error.message);
});

/**
 * Fires before a given file is about to be uploaded. This allows us to
 * send the real filename along with the chunk. This is necessary because
 * for some reason the filename is set to 'blob' whenever a file is chunked
 *
 * @param {object} up	The plupload.Uploader object
 * @param {object} file	The plupload.File object that is about to be uploaded
 */
phpbb.plupload.uploader.bind('BeforeUpload', function(up, file) {
	if (phpbb.plupload.handleMaxFilesReached()) {
		return;
	}

	phpbb.plupload.updateMultipartParams({ real_filename: file.name });
});

/**
 * Fired when a single chunk of any given file is uploaded. This parses the
 * response from the server and checks for an error. If an error occurs it
 * is reported to the user and the upload of this particular file is halted
 *
 * @param {object} up		The plupload.Uploader object
 * @param {object} file		The plupload.File object whose chunk has just
 * 	been uploaded
 * @param {object} response	The response object from the server
 */
phpbb.plupload.uploader.bind('ChunkUploaded', function(up, file, response) {
	if (response.chunk >= response.chunks - 1) {
		return;
	}

	var json = {};
	try {
		json = $.parseJSON(response.response);
	} catch (e) {
		file.status = plupload.FAILED;
		up.trigger('FileUploaded', file, {
			response: JSON.stringify({
				error: {
					message: 'Error parsing server response.'
				}
			})
		});
	}

	// If trigger_error() was called, then a permission error likely occurred.
//	if (typeof json.title !== 'undefined') {
//		json.error = { message: json.message };
//	}

//	if (json.error) {
//		file.status = plupload.FAILED;
//		up.trigger('FileUploaded', file, {
//			response: JSON.stringify({
//				error: {
//					message: json.error.message
//				}
//			})
//		});
//	}
});

/**
 * Fires when files are added to the queue.
 */
phpbb.plupload.uploader.bind('FilesAdded', function(up, files) {
	// Prevent unnecessary requests to the server if the user already uploaded
	// the maximum number of files allowed.
	if (phpbb.plupload.handleMaxFilesReached()) {
		return;
	}

	// Switch the active tab if the style supports it
	if (typeof activateSubPanel === 'function') {
		activateSubPanel('attach-panel'); // jshint ignore: line
	}

	// Do not allow more files to be added to the running queue.
	phpbb.plupload.disableUploader();

	// Start uploading the files once the user has selected them.
	up.start();
});


/**
 * Fires when an entire file has been uploaded. It checks for errors
 * returned by the server otherwise parses the list of attachment data and
 * appends it to the next file upload so that the server can maintain state
 * with regards to the attachments in a given post
 *
 * @param {object} up		The plupload.Uploader object
 * @param {object} file		The plupload.File object that has just been
 * 	uploaded
 * @param {string} response	The response string from the server
 */
phpbb.plupload.uploader.bind('FileUploaded', function(up, file, response) {
	var json = {},
		error;

	try {
		json = JSON.parse(response.response);
	} catch (e) {
		error = 'Error parsing server response.';
	}

	$('#language_from').children('option:not(:first)').remove();
	$('#language_to').children('option:not(:first)').remove();
	$.each(json.iso, function (i, item) {
		$('#language_from').append($('<option>', { 
			value: i,
			text : item.name 
		}));
	
	
		$('#language_to').append($('<option>', { 
			value: i,
			text : item.name 
		}));
	});
//	$('#language_from').hide().show();
	
	
	// If trigger_error() was called, then a permission error likely occurred.
/*	if (typeof json.title !== 'undefined') {
		error = json.message;
		up.trigger('Error', { message: error });

		// The rest of the queue will fail.
		phpbb.plupload.markQueuedFailed(error);
	} else if (json.error) {
		error = json.error.message;
	}

	if (typeof error !== 'undefined') {
		phpbb.plupload.fileError(file, error);
	} else if (file.status === plupload.DONE) {
		file.attachment_data = json.data[0];
	}
*/});

/**
 * Fires when the entire queue of files have been uploaded.
 */
phpbb.plupload.uploader.bind('UploadComplete', function() {
	// Hide the progress bar
	setTimeout(function() {
/*		$('#file-total-progress-bar').fadeOut(500, function() {
			$(this).css('width', 0).show();
		});
*/	}, 2000);

	// Re-enable the uploader
	phpbb.plupload.enableUploader();
});

})(jQuery); // Avoid conflicts with other libraries
