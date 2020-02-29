<template>
  <div class="container">
    <font-awesome-icon :icon="['fas', 'user']"></font-awesome-icon>
    <h2>{{ message }}</h2>
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">Example Component</div>

          <div class="card-body">
            I'm an example component.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop } from 'vue-property-decorator'

@Component
class ExampleComponent extends Vue {
  message: string = 'Holla'

  public mounted(): void {
    console.log('Component mounted.')
  }
}
export default ExampleComponent
</script>
