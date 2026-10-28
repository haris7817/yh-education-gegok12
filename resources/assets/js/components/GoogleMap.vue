<template>
  <!--
    Manual address entry (YH Education / Hong Kong localization).
    This component used to load the Google Maps JavaScript + Places APIs, which
    require an API key and billing and showed a localized error without them.
    It keeps the same name, props and emits so every existing form (student,
    teacher, staff, parent, school details) works unchanged — it simply no
    longer depends on any map service. Addresses are typed manually and accept
    Chinese and English text.
  -->
  <div class="tw-form-group w-full">
    <div class="lg:mr-8 md:mr-8">
      <div class="mb-2">
        <label class="tw-form-label" for="manual-address">Address 地址</label>
      </div>
      <div class="mb-2">
        <textarea
          id="manual-address"
          name="address"
          rows="2"
          class="tw-form-control w-full"
          v-model="localAddress"
          placeholder="e.g. Flat A, 10/F, 123 Example Street, Tseung Kwan O 將軍澳"
          @input="onInput"
        ></textarea>
      </div>
      <p class="text-xs text-gray-500">Enter the address manually (Chinese or English).</p>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    address: String,
    latitude: Number,
    longitude: Number
  },

  emits: ['update:address', 'update:latitude', 'update:longitude'],

  data() {
    return {
      localAddress: this.address || ''
    };
  },

  watch: {
    // Keep in sync when the parent loads the stored address after mount (edit forms).
    address(newVal) {
      if (newVal !== this.localAddress) {
        this.localAddress = newVal || '';
      }
    }
  },

  methods: {
    onInput() {
      this.$emit('update:address', this.localAddress);
    }
  }
};
</script>
