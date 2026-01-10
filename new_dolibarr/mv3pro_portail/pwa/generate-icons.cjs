const fs = require('fs');
const path = require('path');

// Fonction pour créer un PNG valide avec une couleur de fond et du texte
function createPNG(width, height, text, filename) {
  // Créer un buffer PNG valide minimal
  // Format PNG: signature + IHDR chunk + IDAT chunk (vide) + IEND chunk

  const signature = Buffer.from([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A]);

  // IHDR chunk
  const ihdr = Buffer.alloc(25);
  ihdr.writeUInt32BE(13, 0); // Length
  ihdr.write('IHDR', 4);
  ihdr.writeUInt32BE(width, 8);
  ihdr.writeUInt32BE(height, 12);
  ihdr.writeUInt8(8, 16); // Bit depth
  ihdr.writeUInt8(2, 17); // Color type (RGB)
  ihdr.writeUInt8(0, 18); // Compression
  ihdr.writeUInt8(0, 19); // Filter
  ihdr.writeUInt8(0, 20); // Interlace

  // Calculate CRC for IHDR
  const crc = require('zlib').crc32(ihdr.slice(4, 21));
  ihdr.writeUInt32BE(crc, 21);

  // IDAT chunk (données image compressées)
  // Créer une image simple avec un fond cyan (couleur MV3)
  const zlib = require('zlib');
  const rowBytes = width * 3 + 1; // 3 bytes per pixel (RGB) + 1 filter byte
  const imageData = Buffer.alloc(height * rowBytes);

  for (let y = 0; y < height; y++) {
    const rowStart = y * rowBytes;
    imageData[rowStart] = 0; // Filter type: None

    for (let x = 0; x < width; x++) {
      const pixelStart = rowStart + 1 + (x * 3);
      // Gradient cyan (MV3 brand color)
      const gradient = Math.floor((y / height) * 50);
      imageData[pixelStart] = 8 + gradient;     // R
      imageData[pixelStart + 1] = 145 + gradient; // G
      imageData[pixelStart + 2] = 178 + gradient; // B
    }
  }

  const compressed = zlib.deflateSync(imageData, { level: 9 });
  const idat = Buffer.alloc(compressed.length + 12);
  idat.writeUInt32BE(compressed.length, 0);
  idat.write('IDAT', 4);
  compressed.copy(idat, 8);
  const idatCrc = require('zlib').crc32(idat.slice(4, 8 + compressed.length));
  idat.writeUInt32BE(idatCrc, 8 + compressed.length);

  // IEND chunk
  const iend = Buffer.from([0x00, 0x00, 0x00, 0x00, 0x49, 0x45, 0x4E, 0x44, 0xAE, 0x42, 0x60, 0x82]);

  // Combiner tous les chunks
  const png = Buffer.concat([signature, ihdr, idat, iend]);

  fs.writeFileSync(filename, png);
  console.log(`✅ Généré: ${filename} (${width}x${height}) - ${png.length} bytes`);
}

// Générer les icônes
const publicDir = path.join(__dirname, 'public');

try {
  createPNG(192, 192, 'MV3', path.join(publicDir, 'icon-192.png'));
  createPNG(512, 512, 'MV3 PRO', path.join(publicDir, 'icon-512.png'));
  createPNG(192, 192, 'MV3', path.join(publicDir, 'image.png'));

  console.log('\n✅ Icônes PNG générées avec succès!\n');
} catch (error) {
  console.error('❌ Erreur:', error.message);
  process.exit(1);
}
