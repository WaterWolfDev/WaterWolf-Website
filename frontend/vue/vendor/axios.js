import axios from "axios";
import VueAxios from "vue-axios";
import {inject} from "vue";
import {useNotify} from "~/vue/functions/useNotify.js";

export const useAxios = () => inject('axios');

export default function installAxios(vueApp) {

    const axiosInstance = axios.create();

    // Configure some Axios settings that depend on the BootstrapVue $bvToast superglobal.
    const handleAxiosError = (error) => {
        let notifyMessage = 'An error occurred and your request could not be completed.';
        if (error.response) {
            // Request made and server responded
            const responseJson = error.response.data ?? {};
            notifyMessage = responseJson.message ?? notifyMessage;
            console.error(responseJson);
        } else if (error.request) {
            // The request was made but no response was received
            console.error(error.request);
        } else {
            // Something happened in setting up the request that triggered an Error
            console.error('Error', error.message);
        }

        const {notifyError} = useNotify();
        notifyError(notifyMessage);
    };

    axiosInstance.interceptors.request.use((config) => {
        return config;
    }, (error) => {
        handleAxiosError(error);
        return Promise.reject(error);
    });

    axiosInstance.interceptors.response.use((response) => {
        return response;
    }, (error) => {
        handleAxiosError(error);
        return Promise.reject(error);
    });

    vueApp.use(VueAxios, axiosInstance);
    vueApp.provide('axios', axiosInstance);
}
