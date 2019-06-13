<template>
    <li v-bind:class="{ deleting: isDeleting }">
        <a v-bind:href="url" target="_blank">
            <img
                v-bind:src="url"
                v-bind:alt="originalFilename"
            >
        </a>
        <span v-if="this.ponkaAddedAt">
            Ponka visited your photo {{ ponkaAddedAgo }}
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
                isDeleting: false,
                ponkaAddedAgo: null,
            }
        },
        props: ['url', 'originalFilename', 'ponkaAddedAt'],
        methods: {
            onDeleteClick() {
                this.$emit('delete-image');
                this.isDeleting = true;
            },
            updatedPonkaAddedAtAgo() {
                this.ponkaAddedAgo = moment(this.ponkaAddedAt).fromNow();
            }
        },
        created() {
            this.updatedPonkaAddedAtAgo();
            this.timer = setInterval(this.updatedPonkaAddedAtAgo, 60000);
        },
        beforeDestroy() {
            clearInterval(this.timer);
        }
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
