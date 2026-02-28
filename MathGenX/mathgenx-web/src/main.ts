import { createApp } from "vue";
import { createPinia } from "pinia";
import piniaPluginPersistedstate from "pinia-plugin-persistedstate";
import App from "./App.vue";
import router from "./router";
import "@/style.css";
import type { Component } from 'vue';
import * as lucideIcons from "lucide-vue-next";

const app = createApp(App);
const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);

for (const [key, component] of Object.entries(lucideIcons)) {
  app.component(`Lucide${key}`, component as Component);
}

app.use(pinia).use(router).mount("#app");


import("preline/dist/index.js");