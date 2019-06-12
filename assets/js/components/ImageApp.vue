<template>
    <div>
        <imageUploader></imageUploader>
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
