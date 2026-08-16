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

const kaki = `
  <div style="width:100%; font-family:'Segoe UI',Arial,sans-serif; font-size:8pt;
              color:#5b6672; padding:0 16mm; display:flex;
              justify-content:space-between; align-items:center;">
    <span>Panduan Pengajar Smart Learning Center</span>
    <span>Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span></span>
  </div>`;

await page.pdf({
  path: berkasPdf,
  format: 'A4',
  printBackground: true,
  displayHeaderFooter: true,
  headerTemplate: '<div></div>',
  footerTemplate: kaki,
  margin: { top: '16mm', bottom: '18mm', left: '16mm', right: '16mm' },
  // Penanda PDF supaya pembaca bisa melompat lewat panel bookmark, bukan hanya
  // lewat daftar isi di halaman kedua.
  outline: true,
  tagged: true,
});

await browser.close();
console.log('selesai');
