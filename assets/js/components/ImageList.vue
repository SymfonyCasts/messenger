<template>
    <div>
        <ul>
            <imageItem
                v-for="image in images"
                v-bind:key="image.id"
                v-bind:url="image.url"
                v-bind:original-filename="image.originalFilename"
            ></imageItem>
        </ul>
    </div>
</template>

<script>
    import imageItem from './ImageItem';

    export default {
        components: {
            imageItem: imageItem
        },
        props: ['images']
    }
</script>

<style>

</style>
