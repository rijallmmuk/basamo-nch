import subprocess
import os

alur_lms = """sequenceDiagram
    autonumber
    actor Warga as Warga / Peserta Belajar
    participant UI as Antarmuka Portal Belajar
    participant LmsCtrl as Controller & Engine LMS
    participant DB as Database MySQL
    participant Cert as DomPDF & QR Generator

    Warga->>UI: 1. Pilih Pelatihan di Nagari
    UI->>LmsCtrl: Cek Akses & Status Pelatihan
    LmsCtrl->>DB: Query tabel pelatihans (status = terbuka)
    DB-->>LmsCtrl: Data Pelatihan & Daftar Modul
    LmsCtrl-->>UI: Tampilkan Materi Ajar

    opt Pre-test Sebelum Belajar
        Warga->>UI: 2. Kerjakan Pre-test Awal
        UI->>LmsCtrl: Kirim Jawaban Pre-test
        LmsCtrl->>DB: Simpan Skor di evaluasi_percobaans
    end

    loop Belajar Tiap Materi
        Warga->>UI: 3. Baca Materi / Putar Video
        UI->>LmsCtrl: Tandai Selesai
        LmsCtrl->>DB: Update user_module_progress
    end

    Warga->>UI: 4. Kerjakan Kuis Akhir (Evaluasi Kegiatan)
    UI->>LmsCtrl: Submit Lembar Jawaban
    LmsCtrl->>DB: Evaluasi Nilai terhadap KKM (>= 70)

    alt Nilai Lulus
        LmsCtrl->>Cert: Generate Sertifikat Digital
        Cert->>DB: Catat Nomor Seri Unik di certificates
        Cert-->>Warga: Unduh Berkas PDF Sertifikat + QR Code
    else Nilai Belum Mencukupi
        LmsCtrl-->>Warga: Notifikasi Remedial / Ulangi Materi
    end
"""

with open("docs/diagrams/alur-lms.mmd", "w", encoding="utf-8") as f:
    f.write(alur_lms.strip())

files = [
    'erd-master',
    'erd-lms',
    'erd-sdgs',
    'erd-lapau',
    'arsitektur-sistem',
    'alur-lms'
]

puppeteer_cfg = "docs/diagrams/puppeteer-config.json"

for f in files:
    mmd_path = f"docs/diagrams/{f}.mmd"
    svg_path = f"docs/diagrams/{f}.svg"
    png_path = f"docs/diagrams/{f}.png"
    
    print(f"-> Compiling {f}...")
    # Generate SVG
    subprocess.run(["npx", "-y", "@mermaid-js/mermaid-cli", "-p", puppeteer_cfg, "-i", mmd_path, "-o", svg_path, "-b", "white"], check=True)
    # Generate high-resolution PNG (scale 2.5 for 300 DPI print quality)
    subprocess.run(["npx", "-y", "@mermaid-js/mermaid-cli", "-p", puppeteer_cfg, "-i", mmd_path, "-o", png_path, "-s", "2.5", "-b", "white"], check=True)

print("All diagrams compiled successfully!")
