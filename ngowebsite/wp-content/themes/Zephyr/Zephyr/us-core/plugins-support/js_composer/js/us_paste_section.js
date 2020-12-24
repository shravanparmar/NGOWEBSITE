! function( $, undefined ) {
	"use strict";
	/**
	 * Class for importing sections (shortcodes) into the WPBakery Page Builder
	 * @class US_VC_PasteSection
	 */
	let US_VC_PasteSection = function() {
		// Variables
		this.$window = $( '.us-paste-section-window:first' );
		this.$initButton = $( '#us_vc_paste_section_button' );
		this.$input = this.$window.find( 'textarea' );
		this.$errMessage = this.$window.find( '.vc_description' );
		this.errors = {};
		// Event Assignments
		this.$initButton.on( 'click', this._events.showWindow.bind( this ) );
		this.$window.on( 'click', '.us-paste-section-window-btn', this._events.addSections.bind( this ) );
		this.$window.on( 'click', '.us-paste-section-window-closer', this._events.hideWindow.bind( this ) );
		// Load errors
		if ( this.$window.length ) {
			this.errors = this.$window[ 0 ].onclick() || {};
		}
	};
	// Export API
	US_VC_PasteSection.prototype = {
		// Event handlers
		_events: {
			/**
			 * Show window
			 */
			showWindow: function() {
				this._hideError();
				this.$window.show();
				this.$input.focus();
			},
			/**
			 * Hide window
			 */
			hideWindow: function() {
				this.$window.hide();
				this.$input.val( '' );
			},
			/**
			 * Add section
			 */
			addSections: function() {
				this.value = $.trim( this.$input.val() );
				if ( ! this._isValid() ) {
					return;
				}
				$.each( vc.storage.parseContent( {}, this.value ), function( _, model ) {
					if ( model && model.hasOwnProperty( 'shortcode' ) ) {
						// Insert sections
						vc.shortcodes.create( model );
						this._events.hideWindow.call( this );
					}
				}.bind( this ) );
			},
		},
		/**
		 * Validate value
		 * @return {boolean} True if valid, False otherwise.
		 */
		_isValid: function() {
			// Add notice if the text is empty
			if ( this.value === '' ) {
				this._showError( this.errors.empty );
				return false;
			}
			// Add a notification if the text does not contain the shortcode [vc_row ... [/vc_row]
			if ( ! /\[vc_row([\s\S]*)\/vc_row\]/gim.test( this.value ) ) {
				this._showError( this.errors.not_valid );
				return false;
			}
			this._hideError();
			return true;
		},
		/**
		 * Show error message.
		 * @param {string} message Error text
		 */
		_showError: function( message ) {
			this.$errMessage
				.text( message )
				.show();
		},
		/**
		 * Hide error message.
		 */
		_hideError: function() {
			this.$errMessage
				.text( '' )
				.hide();
		}
	};

	// Init class
	$().ready( function() {
		new US_VC_PasteSection;
	} );
}( window.jQuery );
