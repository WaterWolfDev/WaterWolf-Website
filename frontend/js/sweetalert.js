import $ from "jquery";
import Swal from 'sweetalert2/dist/sweetalert2';

const swalCustom = Swal.mixin({
    confirmButtonText: "Confirm",
    cancelButtonText: "Cancel",
    showCancelButton: true,
});

const swalConfirmDelete = swalCustom.mixin({
    title: "Are you sure?",
    confirmButtonText: "Delete",
    confirmButtonColor: "#e64942",
    focusCancel: true,
});

export function confirmDanger ($el) {
    let swalConfig = {};

    if ($el.data('confirm-danger')) {
        swalConfig.title = $el.data('confirm-danger');
    }

    let buttonText = $el.text();
    if (buttonText) {
        swalConfig.confirmButtonText = buttonText;
    }

    return swalConfirmDelete.fire(swalConfig);
}

ready(() => {
    $('a.btn-danger,a.btn[data-confirm-danger]').on('click', function (e) {
        e.preventDefault();

        let $el = $(e.target);
        if (!$el.is('a')) {
            $el = $el.closest('a');
        }

        const linkUrl = $(this).attr('href');
        confirmDanger($el).then((result) => {
            if (result.value) {
                window.location.href = linkUrl;
            }
        });
        return false;
    });

    $('button[data-confirm-danger]').on('click', function(e) {
        e.preventDefault();

        let $el = $(e.target);
        if (!$el.is('button')) {
            $el = $el.closest('button');
        }

        confirmDanger($el).then((result) => {
            if (result.value) {
                $el.closest('form').submit();
            }
        });

        return false;
    });
});
