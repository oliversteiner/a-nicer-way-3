import Vue from 'vue'
import Component from 'vue-class-component'

@Component
export default class ExampleComponent extends Vue {
  public mounted(): void {
    console.log('Component mounted.')
  }
}
