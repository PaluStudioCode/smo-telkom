<!--
  Komponen: GuestLayout.vue
  Deskripsi: Layout wrapper untuk halaman-halaman tamu (guest) yang tidak memerlukan autentikasi.
             Komponen ini berfungsi sebagai lapisan abstraksi tipis (thin wrapper) di atas AuthLayout,
             memudahkan penggunaan layout autentikasi di halaman-halaman publik.
  Digunakan oleh: Halaman-halaman publik seperti Login, Register, Lupa Password, Reset Password, dll.
-->
<script setup>
// Import layout autentikasi yang berisi tampilan branding dan area formulir
import AuthLayout from '@/Layouts/AuthLayout.vue';
</script>

<template>
    <!-- Menggunakan AuthLayout sebagai layout dasar untuk halaman tamu -->
    <AuthLayout>
        <!-- Slot default untuk merender konten halaman tamu (formulir login/register/dll) -->
        <slot />
    </AuthLayout>
</template>
