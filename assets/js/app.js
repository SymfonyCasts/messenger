import Vue from 'vue';
import ImageList from './components/ImageList';
import ImageUploader from './components/ImageUploader';

Vue.component('image-list', ImageList);
Vue.component('image-uploader', ImageUploader);

const app = new Vue({
    el: '#images-app'
});
