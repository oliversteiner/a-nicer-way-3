<template>
  <div class="hello-world">
    <div class="name">{{ item.name }}</div>
    <div class="link">{{ item.link }}</div>
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop } from 'vue-property-decorator'

@Component({})
class HelloWorld extends Vue {
  @Prop() item: any

  public mounted(): void {
    console.log('Component mounted.')
  }
}
export default HelloWorld
</script>

<style lang="scss">
@import 'HelloWorld';
</style>
