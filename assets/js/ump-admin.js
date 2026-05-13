/* global umpData, jQuery */
( function ( $ ) {
	'use strict';

	// ---------------------------------------------------------------------------
	// State
	// ---------------------------------------------------------------------------
	var queue       = [];    // Array<File>
	var processing  = false;
	var dragCounter = 0;     // Track nested dragenter/dragleave

	// ---------------------------------------------------------------------------
	// DOM references (populated on DOMReady)
	// ---------------------------------------------------------------------------
	var $modal, $backdrop, $closeBtn, $dropZone, $fileInput, $fileList, $overlay;

	// ---------------------------------------------------------------------------
	// Init
	// ---------------------------------------------------------------------------
	$( document ).ready( function () {
		$modal    = $( '#ump-modal' );
		$backdrop = $modal.find( '.ump-modal-backdrop' );
		$closeBtn = $modal.find( '.ump-modal-close' );
		$dropZone = $( '#ump-drop-zone' );
		$fileInput = $( '#ump-file-input' );
		$fileList  = $( '#ump-file-list' );
		$overlay   = $( '#ump-global-overlay' );

		bindAdminBarButton();
		bindModalControls();
		bindDropZone();

		if ( ! umpData.nativeDndPage ) {
			initGlobalDnd();
		}
	} );

	// ---------------------------------------------------------------------------
	// Admin bar button
	// ---------------------------------------------------------------------------
	function bindAdminBarButton() {
		$( document ).on( 'click', '#wp-admin-bar-ump-upload > a', function ( e ) {
			e.preventDefault();
			openModal();
		} );
	}

	// ---------------------------------------------------------------------------
	// Modal controls
	// ---------------------------------------------------------------------------
	function bindModalControls() {
		$closeBtn.on( 'click', closeModal );
		$backdrop.on( 'click', closeModal );

		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! $modal.attr( 'hidden' ) ) {
				closeModal();
			}
		} );
	}

	function openModal() {
		$modal.removeAttr( 'hidden' );
		$closeBtn.trigger( 'focus' );
		$( 'body' ).addClass( 'ump-modal-open' );
	}

	function closeModal() {
		$modal.attr( 'hidden', true );
		$( 'body' ).removeClass( 'ump-modal-open' );
	}

	// ---------------------------------------------------------------------------
	// Drop zone inside modal
	// ---------------------------------------------------------------------------
	function bindDropZone() {
		// Click-to-browse
		$dropZone.on( 'click keydown', function ( e ) {
			if ( $( e.target ).is( $fileInput ) ) return; // ignore bubbled click from the input itself
			if ( e.type === 'click' || e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				$fileInput[ 0 ].click(); // native click required — browsers block file picker from synthetic jQuery events
			}
		} );

		$fileInput.on( 'change', function () {
			handleFiles( this.files );
			this.value = ''; // allow re-selecting same file
		} );

		$dropZone
			.on( 'dragenter dragover', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				$( this ).addClass( 'ump-drop-zone--active' );
			} )
			.on( 'dragleave', function ( e ) {
				e.preventDefault();
				$( this ).removeClass( 'ump-drop-zone--active' );
			} )
			.on( 'drop', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				$( this ).removeClass( 'ump-drop-zone--active' );
				var dt = e.originalEvent.dataTransfer;
				if ( dt && dt.files ) {
					handleFiles( dt.files );
				}
			} );
	}

	// ---------------------------------------------------------------------------
	// Global drag-and-drop (non-native DnD pages)
	// ---------------------------------------------------------------------------
	function initGlobalDnd() {
		$( document )
			.on( 'dragenter', function ( e ) {
				if ( ! hasZipFiles( e ) ) return;
				// Don't intercept if target is a known native upload element.
				if ( isNativeTarget( e.target ) ) return;

				dragCounter++;
				if ( dragCounter === 1 ) {
					$overlay.removeAttr( 'hidden' );
				}
			} )
			.on( 'dragover', function ( e ) {
				if ( ! hasZipFiles( e ) ) return;
				if ( isNativeTarget( e.target ) ) return;
				e.preventDefault();
				e.originalEvent.dataTransfer.dropEffect = 'copy';
			} )
			.on( 'dragleave', function ( e ) {
				dragCounter = Math.max( 0, dragCounter - 1 );
				if ( dragCounter === 0 ) {
					$overlay.attr( 'hidden', true );
				}
			} )
			.on( 'drop', function ( e ) {
				dragCounter = 0;
				$overlay.attr( 'hidden', true );

				if ( isNativeTarget( e.target ) ) return;

				e.preventDefault();
				var dt = e.originalEvent.dataTransfer;
				if ( dt && dt.files && dt.files.length ) {
					openModal();
					handleFiles( dt.files );
				}
			} );
	}

	/** Returns true if the dragged items include at least one ZIP file. */
	function hasZipFiles( e ) {
		var items = e.originalEvent && e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.items;
		if ( ! items ) return false;
		for ( var i = 0; i < items.length; i++ ) {
			if ( items[ i ].kind === 'file' ) return true; // can't read type on dragenter in all browsers
		}
		return false;
	}

	/** Returns true if the element is inside a native WordPress upload zone. */
	function isNativeTarget( el ) {
		return !! $( el ).closest(
			'.uploader-inline, .media-frame-content, #plupload-upload-ui, ' +
			'.wp-upload-form, .upload-plugin-wrap'
		).length;
	}

	// ---------------------------------------------------------------------------
	// File handling
	// ---------------------------------------------------------------------------
	function handleFiles( fileList ) {
		var zipFiles = [];
		for ( var i = 0; i < fileList.length; i++ ) {
			var file = fileList[ i ];
			if ( file.name.toLowerCase().endsWith( '.zip' ) ) {
				zipFiles.push( file );
			}
		}

		if ( ! zipFiles.length ) {
			addStatusMessage( umpData.i18n.onlyZip, 'error' );
			return;
		}

		zipFiles.forEach( function ( file ) {
			queue.push( file );
		} );

		if ( ! processing ) {
			processQueue();
		}
	}

	function processQueue() {
		if ( ! queue.length ) {
			processing = false;
			return;
		}
		processing = true;
		var file = queue.shift();
		uploadFile( file, function () {
			processQueue();
		} );
	}

	// ---------------------------------------------------------------------------
	// AJAX upload
	// ---------------------------------------------------------------------------
	function uploadFile( file, callback ) {
		var itemId  = 'ump-item-' + Date.now() + '-' + Math.random().toString( 36 ).substr( 2, 5 );
		var $item   = renderFileItem( itemId, file.name, 'uploading', umpData.i18n.uploading );

		var formData = new FormData();
		formData.append( 'action',     'ump_install' );
		formData.append( 'nonce',      umpData.nonce );
		formData.append( 'ump_plugin', file, file.name );

		$.ajax( {
			url:         umpData.ajaxUrl,
			type:        'POST',
			data:        formData,
			processData: false,
			contentType: false,
			xhr: function () {
				var xhr = $.ajaxSettings.xhr();
				if ( xhr.upload ) {
					xhr.upload.addEventListener( 'progress', function ( ev ) {
						if ( ev.lengthComputable ) {
							var pct = Math.round( ( ev.loaded / ev.total ) * 100 );
							updateProgress( $item, pct );
						}
					} );
				}
				return xhr;
			},
			success: function ( response ) {
				if ( response.success ) {
					var data   = response.data;
					var status = 'success';
					var label  = umpData.i18n.installed;

					if ( data.skipped ) {
						status = 'skipped';
						label  = umpData.i18n.skipped;
					} else if ( data.activated ) {
						label = umpData.i18n.activated;
					}

					updateFileItem( $item, status, label, data.message );
				} else {
					var msg = ( response.data && response.data.message ) ? response.data.message : umpData.i18n.error;
					updateFileItem( $item, 'error', umpData.i18n.error, msg );
				}
				callback();
			},
			error: function ( jqXHR ) {
				updateFileItem( $item, 'error', umpData.i18n.error, jqXHR.statusText );
				callback();
			}
		} );
	}

	// ---------------------------------------------------------------------------
	// UI helpers
	// ---------------------------------------------------------------------------
	function renderFileItem( id, name, status, statusText ) {
		var $item = $(
			'<li class="ump-file-item ump-file-item--' + escAttr( status ) + '" id="' + escAttr( id ) + '">' +
				'<span class="ump-file-name" title="' + escAttr( name ) + '">' + escHtml( name ) + '</span>' +
				'<span class="ump-file-status">' + escHtml( statusText ) + '</span>' +
				'<div class="ump-progress-wrap"><div class="ump-progress-bar" style="width:0%"></div></div>' +
				'<span class="ump-file-message"></span>' +
			'</li>'
		);
		$fileList.append( $item );
		$item[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		return $item;
	}

	function updateProgress( $item, pct ) {
		$item.find( '.ump-progress-bar' ).css( 'width', pct + '%' );
	}

	function updateFileItem( $item, status, statusText, message ) {
		$item
			.removeClass( 'ump-file-item--uploading' )
			.addClass( 'ump-file-item--' + status );
		$item.find( '.ump-file-status' ).text( statusText );
		$item.find( '.ump-progress-bar' ).css( 'width', '100%' );
		if ( message ) {
			$item.find( '.ump-file-message' ).text( message );
		}
	}

	function addStatusMessage( msg, type ) {
		var $msg = $(
			'<li class="ump-file-item ump-file-item--' + escAttr( type ) + '">' +
				'<span class="ump-file-status">' + escHtml( msg ) + '</span>' +
			'</li>'
		);
		$fileList.append( $msg );
	}

	function escHtml( str ) {
		return $( '<span>' ).text( str ).html();
	}

	function escAttr( str ) {
		return $( '<span>' ).attr( 'data-v', str ).attr( 'data-v' ) || '';
	}

} )( jQuery );
