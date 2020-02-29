import 'jest'
import { mount } from '@vue/test-utils'

const NewCom = require('@/components/NewCom').default


describe('Component', () => {
  test('is a Vue instance', () => {
    const wrapper = mount(NewCom)
    expect(wrapper.isVueInstance()).toBeTruthy()
  })
})
