<template>
  <div class="min-h-screen bg-gray-100">
    <NavBar />
    <main class="py-8 px-4">
      <slot />
    </main>
  </div>
</template>

<script setup>
import NavBar from './NavBar.vue'
</script>
