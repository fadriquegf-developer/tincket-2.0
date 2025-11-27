<template>
  <div class="box">
    <div class="box-header">
      <h3 class="box-title">{{ $t('payment') }}</h3>
    </div>
    <div class="box-body">
      <div v-if="entry?.payment" class="mb-3">
        <div class="row mb-3">
          <label class="col-sm-4 col-form-label">{{ $t('payment_code') }}</label>
          <div class="col-sm-8">
            <p class="form-control-plaintext">{{ entry.payment.order_code }}</p>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-sm-4 col-form-label">{{ $t('paid_at') }}</label>
          <div class="col-sm-8">
            <p class="form-control-plaintext">{{ entry.payment.paid_at }}</p>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-sm-4 col-form-label">{{ $t('payment_platform') }}</label>
          <div class="col-sm-8">
            <p class="form-control-plaintext">{{ entry.payment.gateway }}</p>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-sm-8 offset-sm-4">
          <div class="form-check form-check-inline">
            <input 
              class="form-check-input" 
              type="radio" 
              id="payment_type_cash" 
              name="payment_type"
              value="cash" 
              v-model="paymentType"
            >
            <label class="form-check-label py-0" for="payment_type_cash">
              <i class="la la-money-bill"></i>
              {{ $t('payment_type.cash') }}
            </label>
          </div>
          <div class="form-check form-check-inline">
            <input 
              class="form-check-input" 
              type="radio" 
              id="payment_type_card" 
              name="payment_type"
              value="card"
              v-model="paymentType"
            >
            <label class="form-check-label py-0" for="payment_type_card">
              <i class="la la-credit-card"></i>
              {{ $t('payment_type.card') }}
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useTranslations } from '../composables/useTranslations'

const props = defineProps({
  entry: {
    type: Object,
    default: null
  }
})

const paymentType = ref('cash')
const { $t } = useTranslations()

</script>