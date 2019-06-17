<template>
    <div>
        <vue-dropzone
            id="dropzone"
            ref="vueDropzone"
            v-on:vdropzone-success="onDropzoneSuccess"
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
        methods: {
            onDropzoneSuccess(file, response) {
                this.$emit('new-image', response);
                this.$refs.vueDropzone.removeFile(file);
            }
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
