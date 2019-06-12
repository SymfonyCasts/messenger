<template>
    <li>
        <a v-bind:href="url">
            <img
                v-bind:src="url"
                v-bind:alt="originalFilename"
            >
        </a>
    </li>
</template>

<script>
    export default {
        props: ['url', 'originalFilename']
    }
</script>

<style scoped>
    img {
        width: 100px;
    }
</style>
