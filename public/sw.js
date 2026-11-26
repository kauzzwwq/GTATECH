// Service worker do app "Notas de Transporte Animal".
// Guarda só os arquivos fixos (visual) e mostra uma página amigável quando não há internet.
// As páginas do sistema (login, notas, PDF) NUNCA são guardadas: sempre vêm do servidor.
// Ao publicar uma versão nova do visual, aumente o número abaixo para atualizar o cache.
const VERSAO = 'v1';
const CACHE = 'notas-animais-' + VERSAO;
const BASE = self.registration.scope;
const FIXOS = [
  'offline.html',
  'manifest.webmanifest',
  'assets/css/style.css',
  'assets/js/app.js',
  'assets/images/Logo-S.png',
  'assets/images/Logo-SENAI_EP.png',
  'assets/images/login-transporte.svg',
  'assets/icons/icon-192.png',
].map((p) => new URL(p, BASE).href);

self.addEventListener('install', (evento) => {
  evento.waitUntil(
    caches.open(CACHE).then((c) => c.addAll(FIXOS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (evento) => {
  evento.waitUntil(
    caches.keys()
      .then((nomes) => Promise.all(nomes.filter((n) => n !== CACHE).map((n) => caches.delete(n))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (evento) => {
  const req = evento.request;
  if (req.method !== 'GET') { return; }
  const url = new URL(req.url);
  if (url.origin !== location.origin) { return; }

  // Páginas: sempre do servidor; sem internet, mostra a página "sem conexão"
  if (req.mode === 'navigate') {
    evento.respondWith(
      fetch(req).catch(() => caches.match(new URL('offline.html', BASE).href))
    );
    return;
  }

  // Visual (css, js, imagens, ícones): tenta o servidor primeiro e guarda cópia; sem internet usa a cópia
  if (url.pathname.includes('/assets/') || url.pathname.endsWith('manifest.webmanifest')) {
    evento.respondWith(
      fetch(req)
        .then((resp) => {
          if (resp && resp.ok) {
            const copia = resp.clone();
            caches.open(CACHE).then((c) => c.put(req, copia));
          }
          return resp;
        })
        .catch(() => caches.match(req))
    );
  }
});
