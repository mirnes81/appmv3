const express = require('express');
const path = require('path');

const app = express();
const PORT = 3000;

app.use(express.static('public'));
app.use('/pwa', express.static('new_dolibarr/mv3pro_portail/pwa_dist'));
app.use('/module', express.static('new_dolibarr/mv3pro_portail'));

app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.get('/dashboard-demo', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'dashboard-demo.html'));
});

app.listen(PORT, () => {
  console.log('');
  console.log('═══════════════════════════════════════════════════════');
  console.log('  ✅ MV-3 PRO Demo Server Started!');
  console.log('═══════════════════════════════════════════════════════');
  console.log('');
  console.log(`  🏠 Accueil:           http://localhost:${PORT}`);
  console.log(`  📱 PWA:               http://localhost:${PORT}/pwa`);
  console.log(`  📊 Dashboard Demo:    http://localhost:${PORT}/dashboard-demo`);
  console.log('');
  console.log('═══════════════════════════════════════════════════════');
  console.log('');
});
