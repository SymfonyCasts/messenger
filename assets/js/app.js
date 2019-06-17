import Vue from 'vue';
import ImageApp from './components/ImageApp';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap';

Vue.component('image-app', ImageApp);

const app = new Vue({
    el: '#images-app'
});
