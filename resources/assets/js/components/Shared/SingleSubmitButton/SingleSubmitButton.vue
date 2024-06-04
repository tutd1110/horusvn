<template>
    <a-button :disabled="disabled || processing" @click="handleClick">
      <slot></slot>
    </a-button>
</template>

<script>
import { ref} from 'vue';
export default {
  name: 'single-submit-button',
  props: {
  // A function which returns Promise.
    onclick: {
      type: Function,
      required: true,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  setup(props){   
    const processing = ref(false);
    const handleClick = (event)=> {
      //Exit if processing
      if (processing.value) return;
      //Otherwise do the following
      //Make it running and enable the button disable
        processing.value = true;
        props.onclick(event)
        .finally(() => {
          processing.value = false;
      });
    }
    return {
      handleClick,
      processing,
    };
  },
};
</script>