<template>
    <div>
        <imageUploader
            v-on:new-image="onNewUploadedImage"
        ></imageUploader>
        <imageList
            v-bind:images="images"
        ></imageList>
    </div>
</template>

<script>
    import axios from 'axios';
    import imageList from './ImageList';
    import imageUploader from './ImageUploader';

    export default {
        components: {
            imageList,
            imageUploader
        },
        methods: {
            onNewUploadedImage(image) {
                this.images.unshift(image);
            }
        },
        data() {
            return {
                images: []
            }
        },
        mounted() {
            axios
                .get('/api/images')
                .then(response => (this.images = response.data.items))
        }
    }
</script>
