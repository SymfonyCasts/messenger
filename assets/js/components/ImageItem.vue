<template>
    <li v-bind:class="{ deleting: isDeleting }">
        <a v-bind:href="url" target="_blank">
            <img
                v-bind:src="url"
                v-bind:alt="originalFilename"
            >
        </a>
        <button v-on:click="onDeleteClick">X</button>
    </li>
</template>

<script>
    export default {
        data() {
            return {
                isDeleting: false
            }
        },
        props: ['url', 'originalFilename'],
        methods: {
            onDeleteClick() {
                this.$emit('delete-image');
                this.isDeleting = true;
            }
        },
    }
</script>

<style scoped>
    img {
        width: 100px;
    }
    .deleting {
        opacity: .3;
    }
</style>
