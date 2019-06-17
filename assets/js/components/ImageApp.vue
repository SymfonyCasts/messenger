<template>
    <div>
        <div style="position:relative;">
            <div class="row no-gutters" style="box-shadow: 0 3px 7px 1px rgba(0,0,0,0.06);">
                <div class="col py-5">
                    <h1 class="text-center">Ponka-fy Me</h1>
                </div>
            </div>
            <div class="row no-gutters">
                <div class="col-xs-12 col-md-6 px-5" style="background-color: #659dbd; padding-bottom: 150px;">
                    <h2 class="text-center mb-5 pt-5 text-white">First: Upload Photo</h2>
                    <imageUploader
                        v-on:new-image="onNewUploadedImage"
                    ></imageUploader>
                </div>
                <div class="col-xs-12 col-md-6 px-5" style="background-color: #7FB7D7; min-height: 40rem; padding-bottom: 150px;">
                    <h2 class="text-center mb-5 pt-5 text-white">Second: Download Improved Photo</h2>
                    <imageList
                        v-bind:images="images"
                        v-on:delete-image="onDeleteImage"
                    ></imageList>
                </div>
            </div>
            <footer class="footer">
                
                    <p class="text-muted my-5 text-center">Made with love by the <a style="text-decoration: underline; color: #6c757d; font-weight: bold;" href="http://www.symfonycasts.com">SymfonyCasts</a> Team</p>
                
            </footer>
        </div>
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
                // bail if already found - solves a race condition
                // when upload and polling finish at similar time
                if (this.images.find(oneImage => oneImage.id === image.id)) {
                    return;
                }

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
        }
    }
</script>

<style scoped lang="scss">
    .footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        min-height: 60px;
        background-color: #f5f5f5;
    }
</style>