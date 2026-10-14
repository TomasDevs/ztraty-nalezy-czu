import { defineConfig, loadEnv } from 'vite';
import { existsSync, rmSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const DEV_PORT = 5180;
const DEV_ORIGIN = `http://localhost:${DEV_PORT}`;
const hotFile = fileURLToPath(new URL('./public/hot', import.meta.url));

// Tells PHP that the dev server is running (see vite() in src/helpers.php).
const phpHotFile = () => ({
  name: 'php-hot-file',
  configureServer(server) {
    server.httpServer?.once('listening', () => writeFileSync(hotFile, DEV_ORIGIN));

    const cleanup = () => existsSync(hotFile) && rmSync(hotFile);
    process.on('exit', cleanup);
    ['SIGINT', 'SIGTERM', 'SIGHUP'].forEach((signal) => process.on(signal, () => process.exit()));
  },
});

export default defineConfig(({ command, mode }) => {
  const { BASE_PATH = '' } = loadEnv(mode, process.cwd(), '');

  return {
    base: command === 'build' ? `${BASE_PATH}/build/` : '/',
    publicDir: false,
    plugins: [phpHotFile()],
    server: {
      origin: DEV_ORIGIN,
      port: DEV_PORT,
      strictPort: true,
    },
    build: {
      outDir: 'public/build',
      emptyOutDir: true,
      manifest: true,
      rollupOptions: {
        input: 'assets/js/main.js',
      },
    },
  };
});
