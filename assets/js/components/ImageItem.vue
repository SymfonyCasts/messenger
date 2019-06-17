<template>
    <li v-bind:class="{ deleting: isDeleting }" class="text-white pt-3">
        <div class="d-flex flex-row">
            <div>
                <a v-bind:href="url" target="_blank">
                    <img
                        v-bind:src="url"
                        v-bind:alt="originalFilename"
                    >
                </a>
            </div>
            <div class="pl-2">
                <span v-if="this.ponkaAddedAt">
                    Ponka visited your photo {{ ponkaAddedAgo }}
                </span>
                <span v-else>
                    Ponka is napping. Check back soon.
                </span>
            </div>
            <div class="pl-2">
                <button title="clear image" v-on:click="onDeleteClick" class="btn btn-warning font-weight-bold">X</button>
            </div>
        </div>
    </li>
</template>

<script>
    import moment from 'moment';
    import $ from 'jquery';

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
        },
        mounted() {
            $(this.$el).find('button').tooltip();
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
