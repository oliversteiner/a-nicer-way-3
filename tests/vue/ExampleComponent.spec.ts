import 'jest'
import {shallowMount} from '@vue/test-utils'

const ExampleComponent = require('@/components/ExampleComponent/ExampleComponent').default


describe('HelloWorld.vue', () => {
  test('renders props.msg when passed', () => {
    const message = 'new message'
    const wrapper = shallowMount(ExampleComponent, {
      propsData: {message},
    })
    expect(wrapper.text()).toMatch(message)
  })
})
