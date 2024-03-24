import {h, render} from "vue";
import {default as BSToast} from 'bootstrap/js/src/toast';

import Toast from '~/vue/components/Common/Toast.vue';
import {currentVueInstance} from "~/vue/vendor/vueInstance";

export function createToast(props) {
    let slot;
    if (Array.isArray(props.message)) {
        slot = props.message
        delete props.message
    }

    const defaultSlot = () => {
        return slot
    };

    const vNode = h(Toast, props, defaultSlot);
    vNode.appContext = currentVueInstance._context;

    const newDiv = document.createElement('div');
    newDiv.style.display = "contents";
    document.querySelector('.toast-container').appendChild(newDiv);

    render(vNode, newDiv);

    return new BSToast(vNode.el);
}

/* Composition API BootstrapVue utilities */
export function useNotify() {
    const notify = (message = null, options = {}) => {
        if (document.hidden) {
            return;
        }

        const toast = createToast({
            ...options,
            message
        });
        toast.show();
    };

    const notifyError = (message = null, options = {}) => {
        if (message === null) {
            message = 'An error occurred and your request could not be completed.';
        }

        const defaults = {
            variant: 'danger'
        };

        notify(message, {...defaults, ...options});

        return message;
    };

    const notifySuccess = (message = null, options = {}) => {
        if (message === null) {
            message = 'Changes saved.';
        }

        const defaults = {
            variant: 'success'
        };

        notify(message, {...defaults, ...options});

        return message;
    };

    return {
        notify,
        notifyError,
        notifySuccess
    };
}
