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
    <div>
      {{ info }}
    </div>
  </div>
</template>

<script>
  import axios from 'axios';
  import imageItem from './ImageItem';

  export default {
    components: {
        imageItem: imageItem
    },
    data () {
      return {
        images: [
          {id: 1},
          {id: 2},
        ],
        info: null
      }
    },
    mounted() {
      axios
          .get('/api/images')
          .then(response => (this.images = response.data.items))
    }
  }
</script>

<style>

</style>
