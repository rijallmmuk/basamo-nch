/**
 * Mencetak berkas HTML panduan menjadi PDF.
 *
 * Dipanggil oleh bangun-pdf.php, bukan dijalankan sendiri.
 *
 * Memakai puppeteer-core, bukan `google-chrome --print-to-pdf`, karena tiga hal yang
 * hanya tersedia lewat protokol: nomor halaman pada kaki, penghilangan kop bawaan
 * Chrome yang menampilkan alamat file://, dan daftar penanda (bookmark) PDF yang
 * dirakit dari struktur judulnya.
 */
import puppeteer from 'puppeteer-core';

const [, , berkasHtml, berkasPdf] = process.argv;

const browser = await puppeteer.launch({
  executablePath: '/usr/bin/google-chrome',
  headless: true,
  args: ['--no-sandbox'],
});

const page = await browser.newPage();
await page.goto('file://' + berkasHtml, { waitUntil: 'networkidle0' });

/* Dokumen harus dicetak dalam satu operasi. Memisahkan sampul dan badan lalu
   menggabungkannya dengan utilitas PDF menghilangkan anotasi tautan internal dan
   named destination, sehingga daftar isi tampak dapat diklik tetapi tidak bekerja. */
await page.pdf({
  path: berkasPdf,
  format: 'A4',
  printBackground: true,
  displayHeaderFooter: false,
  // Margin diatur lewat @page agar halaman pertama dapat full-bleed sementara
  // halaman isi tetap memiliki ruang baca yang aman.
  margin: { top: 0, bottom: 0, left: 0, right: 0 },
  // Penanda PDF supaya pembaca bisa melompat lewat panel bookmark, bukan hanya
  // lewat daftar isi di halaman kedua.
  outline: true,
  tagged: true,
});

await browser.close();
console.log('selesai');
