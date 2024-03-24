import Swal from 'sweetalert2/dist/sweetalert2';

export function useSweetAlert() {
    const swalCustom = Swal.mixin({
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        showCancelButton: true,
    });

    const swalConfirmDelete = swalCustom.mixin({
        title: 'Delete Record?',
        confirmButtonText: 'Delete',
        confirmButtonColor: '#e64942',
        focusCancel: true
    });

    const showAlert = (options = {}) => {
        return swalCustom.fire(options);
    }

    const confirmDelete = (options = {}) => {
        return swalConfirmDelete.fire(options);
    }

    const vConfirmLink = (el, binding) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();

            const options = {
                title: null
            };

            if (el.hasAttribute('data-confirm-title')) {
                options.title = el.getAttribute('data-confirm-title');
            } else if (binding.value) {
                options.title = binding.value;
            }

            confirmDelete(options).then((resp) => {
                if (!resp.value) {
                    return;
                }

                window.location.href = el.href;
            });
        });
    };

    return {
        showAlert,
        confirmDelete,
        vConfirmLink
    };
}
