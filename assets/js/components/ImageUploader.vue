<template>
    <div>
        <vue-dropzone
            id="dropzone"
            :options="dropzoneOptions"
        ></vue-dropzone>
    </div>
</template>

<script>
    import vue2Dropzone from 'vue2-dropzone'
    import 'vue2-dropzone/dist/vue2Dropzone.min.css'

    export default {
        components: {
            vueDropzone: vue2Dropzone
        },
        data: function () {
            return {
                dropzoneOptions: {
                    url: '/api/images',
                    thumbnailWidth: 150
                }
            }
        }
    }
</script>
