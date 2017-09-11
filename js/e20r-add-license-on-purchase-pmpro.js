/**
 * Copyright (c) 2017 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

jQuery(document).ready(function ($) {
    "use strict";

    var saveSettings = {
        init: function() {
            this.saveBtn = $('input[name="e20rlm_save_settings"]');
            this.spinner = $('#e20r-save-spinner');
            this.status = $('#e20r-save-status');
            this.inputs = $('.e20r-save-input');
            this.selects = $('.e20r-save-select');

            var self = this;

            self.saveBtn.on('click', function() {
                event.preventDefault();
                self.spinner.show();
                self.save();
            });
        },
        save: function() {

            var self = this;
            var data = {
                action: 'e20r_save_pmpro_settings',
                e20r_alp_save: jQuery('#e20r_alp_save').val()
            };

            self.inputs.each( function() {

                var input = $(this);
                var $id = input.attr('name');

                data[$id] = input.val();
            });

            self.selects.each( function() {

                var select = $(this);
                var $id = select.attr('name');

                data[$id] = select.val();
            });

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                timeout: 10000,
                dataType: 'JSON',
                data: data,
                success: function( $response ) {

                    window.console.log( $response );

                    if ( false === $response.success ) {
                        self.status.addClass( 'e20r-error' );
                        self.status.find('.e20r-closebtn').after( "Error! " + $response.data );
                        self.status.show();

                        return;
                    }

                    self.status.addClass( 'e20r-success' );
                    self.status.find('.e20r-closebtn').after( "Success! Saved settings for the Software License Manager");
                    self.status.show();

                    window.console.log("Saved the settings for the Software License Manager");
                    return;
                },
                error: function( hdr, $error, errorThrown ) {
                    self.status.addClass( 'error' );
                    self.status.find('.e20r-closebtn').after( "Error! " + $error );
                    self.status.show();

                    window.console.log("Unable to save the settings: ", hdr );
                },
                complete: function() {
                    self.spinner.hide();
                }
            });
        }
    };

    saveSettings.init();
});

