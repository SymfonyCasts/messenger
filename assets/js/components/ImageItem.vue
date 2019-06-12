<template>
  <li>
    <img
        width="100"
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

<style>

</style>
