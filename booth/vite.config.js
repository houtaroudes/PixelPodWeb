import { defineConfig } from 'vite'

/*
 * The booth is a small site of its own sitting inside the PHP repo, so it takes
 * a port of its own rather than competing for Vite's default with the other
 * projects on this machine. strictPort is on so a clash fails loudly instead of
 * quietly moving somewhere a verification pass is not looking.
 */
export default defineConfig({
  server: {
    port: 5400,
    strictPort: true,
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
})
