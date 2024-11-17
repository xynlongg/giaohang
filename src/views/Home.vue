<template>
  <div class="home">
    <h1>Welcome to ShipperApp</h1>
    <router-link to="/login" v-if="!shipperStore.isAuthenticated">Login</router-link>
    <router-link to="/profile" v-else>Profile</router-link>
  </div>
</template>

<script setup>
import { useShipperStore } from '../stores/shipper'

const shipperStore = useShipperStore()
</script>