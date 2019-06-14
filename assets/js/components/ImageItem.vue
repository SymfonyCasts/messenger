<template>
    <li v-bind:class="{ deleting: isDeleting }" class="text-white">
        <button title="clear image" v-on:click="onDeleteClick" class="btn btn-default font-weight-bold text-white">X</button>
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
        watch: {
            ponkaAddedAt() {
                this.updatedPonkaAddedAtAgo();
            }
        },
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

<style scoped lang="scss">
    img {
        width: 100px;
        border-radius: 5px;
    }
    .deleting {
        opacity: .3;
    }
    button {
        cursor: pointer;
    }

</style>
