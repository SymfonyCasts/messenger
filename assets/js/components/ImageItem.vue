<template>
    <li v-bind:class="{ deleting: isDeleting }">
        <a v-bind:href="url" target="_blank">
            <img
                v-bind:src="url"
                v-bind:alt="originalFilename"
            >
        </a>
        <span v-if="this.ponkaAddedAt">
            Ponka visited your photo {{ ponkaAddedAtAgo() }}
        </span>
        <span v-else>
            Ponka is napping. Check back soon.
        </span>
        <button v-on:click="onDeleteClick">X</button>
    </li>
</template>

<script>
    import moment from 'moment';

    export default {
        data() {
            return {
                isDeleting: false
            }
        },
        props: ['url', 'originalFilename', 'ponkaAddedAt'],
        methods: {
            onDeleteClick() {
                this.$emit('delete-image');
                this.isDeleting = true;
            },
            ponkaAddedAtAgo() {
                return moment(this.ponkaAddedAt).fromNow();
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
