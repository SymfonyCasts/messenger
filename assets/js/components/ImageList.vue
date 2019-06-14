<template>
    <div>
        <transition-group name="list" tag="ul">
            <imageItem
                v-for="image in images"
                v-bind:key="image.id"
                v-bind:url="image.url"
                v-bind:original-filename="image.originalFilename"
                v-bind:ponka-added-at="image.ponkaAddedAt"
                v-on:delete-image="onDeleteImage(image)"
            ></imageItem>
        </transition-group>
    </div>
</template>

<script>
    import imageItem from './ImageItem';

    export default {
        components: {
            imageItem: imageItem
        },
        props: ['images'],
        methods: {
            onDeleteImage(image) {
                this.$emit('delete-image', image);
            }
        }
    }
</script>

<style scoped lang="scss">
    .list-enter-active, .list-leave-active {
      transition: all 1s;
    }
    .list-enter, .list-leave-to {
      opacity: 0;
      transform: translateY(30px);
    }
    ul {
        list-style: none
    }
</style>
