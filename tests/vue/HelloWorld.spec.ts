import Vue from 'vue';
import { mount } from '@vue/test-utils';

import faker from 'faker';

// import configStore from '../mocks/config-store';
// import storeMock from '../mocks/store-mock';

 import HelloWorld from '@/components/HelloWorld/HelloWorld';
// const HelloWorld = require('@/components/HelloWorld/HelloWorld').default

describe('HelloWorld.vue', () => {
 //   const store = configStore(Vue, storeMock);

  it('should render correctly', () => {
    const item = {
      name: faker.name.findName(),
      link: faker.internet.url(),
    };

    const wrapper = mount(HelloWorld, {
  //     store,
      propsData: {
        item,
      },
    });

    expect(wrapper.find('.name').text()).toEqual(item.name);
    expect(wrapper.find('a').element.getAttribute('href')).toEqual(item.link);
  });
});
