import subprocess
import os

files = [
    'erd-master',
    'erd-lms',
    'erd-sdgs',
    'erd-lapau',
    'arsitektur-sistem',
    'alur-lms'
]

print("Generating SVG and PNG for each diagram...")
for f in files:
    mmd_path = f"docs/diagrams/{f}.mmd"
    svg_path = f"docs/diagrams/{f}.svg"
    png_path = f"docs/diagrams/{f}.png"
    
    print(f"-> Processing {f}...")
    # Generate SVG
    subprocess.run(["npx", "-y", "@mermaid-js/mermaid-cli", "-i", mmd_path, "-o", svg_path, "-b", "white"], check=False)
    # Generate high-res PNG (scale 2.5 for 300 DPI print quality)
    subprocess.run(["npx", "-y", "@mermaid-js/mermaid-cli", "-i", mmd_path, "-o", png_path, "-s", "2.5", "-b", "white"], check=False)

print("Done! Listing docs/diagrams:")
os.system("ls -lh docs/diagrams")
