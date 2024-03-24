// Frontend styling
import "~/scss/style.scss";

// Frontend JS
import * as bootstrap from "bootstrap";
import $ from "jquery";

// Custom functions
import passwordStrength from './js/passwordStrength.js';
import {confirmDanger} from './js/sweetalert.js';
import {
    dateTimeToDateTimeString,
    dateTimeToRelative,
    dateTimeToTimeString,
    nowToDateTime,
    timestampToDateTime,
} from './js/dateTimeUtils.js';

window.bootstrap = bootstrap;

window.jQuery = window.$ = $;

window.passwordStrength = passwordStrength;

window.confirmDanger = confirmDanger;

window.nowToDateTime = nowToDateTime;
window.timestampToDateTime = timestampToDateTime;
window.dateTimeToDateTimeString = dateTimeToDateTimeString;
window.dateTimeToTimeString = dateTimeToTimeString;
window.dateTimeToRelative = dateTimeToRelative;

const ready = (callback) => {
    if (document.readyState !== "loading") callback();
    else document.addEventListener("DOMContentLoaded", callback);
};

ready(() => {
    // Toasts
    document.querySelectorAll('.toast-notification').forEach((el) => {
        const toast = new bootstrap.Toast(el);
        toast.show();
    });

    // Trigger the radio popup.
    let radioPopup = null;
    document.querySelectorAll('[data-radio-popup]').forEach((el) => {
        el.addEventListener("click", (e) => {
            e.preventDefault();

            if (radioPopup == null || radioPopup.closed) {
                radioPopup = window.open(
                    el.href,
                    "WaterWolfRadio",
                    "resizable,scrollbars,status,width=550,height=800"
                );
            } else {
                radioPopup.focus();
            }

            return false;
        });
    });
});

export default bootstrap;
