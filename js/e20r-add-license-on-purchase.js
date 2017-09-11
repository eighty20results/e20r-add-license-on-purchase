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

    var lm_enabled = $('#e20rlm_licensing_enabled');
    var is_downloadable = $( '#_downloadable' );

    is_downloadable.unbind('click').on('click', function() {

        if ( is_downloadable.is(':checked')) {
            $(".e20r-toggled-hide").hide();
        }
    });

    lm_enabled.unbind('click').on("click", function () {

        toggle_e20rlm();
    });

    toggle_e20rlm();

    function toggle_e20rlm() {

        if (lm_enabled.is(':checked')) {
            $(".e20r-toggled-hide").show();
        } else {
            $(".e20r-toggled-hide").hide();
        }
    }
});