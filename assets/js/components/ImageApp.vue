<template>
    <div>
        <imageUploader
            v-on:new-image="onNewUploadedImage"
        ></imageUploader>
        <imageList
            v-bind:images="images"
            v-on:delete-image="onDeleteImage"
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
            },
            onDeleteImage(image) {
                axios
                    .delete(image['@id'])
                    .then(() => {
                        this.$delete(this.images, this.images.indexOf(image));
                    })
            },
            fetchImagesData() {
                axios
                    .get('/api/images')
                    .then(response => (this.images = response.data.items))
            }
        },
        data() {
            return {
                images: []
            }
        },
        mounted() {
            this.fetchImagesData();
        },
        created() {
            // slow "polling" for updates
            this.timer = setInterval(this.fetchImagesData, 10000);
        },
        beforeDestroy() {
            clearInterval(this.timer);
        }
    }
</script>
