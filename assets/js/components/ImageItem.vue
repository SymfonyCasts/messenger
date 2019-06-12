<template>
  <li>
    <img
        v-bind:src="url"
        v-bind:alt="originalFilename"
    >
  </li>
</template>

<script>
  export default {
    props: ['url']
  }
</script>

<style scoped>
  img {
    width: 100px;
  }
</style>
