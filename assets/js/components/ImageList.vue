<template>
  <div>
    <ul>
      <li v-for="image in images">
        {{ image.id }}
      </li>
    </ul>
    <div>
      {{ info }}
    </div>
  </div>
</template>

<script>
  import axios from 'axios';

  export default {
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
