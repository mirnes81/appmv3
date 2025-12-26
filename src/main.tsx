import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './index.css';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>
);

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/pro/sw.js', { scope: '/pro/' })
      .then(registration => console.log('SW registered:', registration))
      .catch(error => console.log('SW registration failed:', error));
  });
}
