import Vue from 'vue';
import ImageApp from './components/ImageApp';

Vue.component('image-app', ImageApp);

const app = new Vue({
    el: '#images-app'
});
