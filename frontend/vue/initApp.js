import {createApp, h} from "vue";
import installAxios from "~/vue/vendor/axios";
import {installCurrentVueInstance} from "~/vue/vendor/vueInstance.js";

export default function initApp(
    appName = 'Generic',
    appComponent
) {
    let componentProps = {};

    const vueApp = createApp({
        render() {
            return h(appComponent, componentProps);
        }
    });

    installCurrentVueInstance(vueApp);

    window[`vueComponent${appName}`] = async (el, props = {}) => {
        componentProps = props;

        installAxios(vueApp);

        vueApp.mount(el);
    };

    return {
        vueApp
    };
}
