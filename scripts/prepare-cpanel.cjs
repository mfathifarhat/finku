const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

console.log('--- Memulai Persiapan Deploy cPanel ---');

const projectDir = path.join(__dirname, '..');
const tempDir = path.join(projectDir, 'cpanel-deploy-temp');
const zipPath = path.join(projectDir, 'finku_cpanel_deploy.zip');

try {
    // 1. Run npm run build
    console.log('1. Mengompilasi aset frontend (npm run build)...');
    execSync('npm run build', { cwd: projectDir, stdio: 'inherit' });

    // 2. Clean previous temp files
    if (fs.existsSync(tempDir)) {
        console.log('Membersihkan folder temp sebelumnya...');
        fs.rmSync(tempDir, { recursive: true, force: true });
    }
    if (fs.existsSync(zipPath)) {
        console.log('Menghapus zip deploy lama...');
        fs.unlinkSync(zipPath);
    }

    // 3. Create temp directory
    fs.mkdirSync(tempDir);

    // 4. Files and folders to copy
    const itemsToCopy = [
        'app',
        'bootstrap',
        'config',
        'database',
        'public',
        'resources',
        'routes',
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json'
    ];

    console.log('2. Menyalin file proyek (kecuali node_modules & vendor)...');
    for (const item of itemsToCopy) {
        const src = path.join(projectDir, item);
        const dest = path.join(tempDir, item);
        
        if (fs.existsSync(src)) {
            const stat = fs.statSync(src);
            if (stat.isDirectory()) {
                fs.cpSync(src, dest, { recursive: true });
            } else {
                fs.copyFileSync(src, dest);
            }
        }
    }

    // 5. Compress using PowerShell
    console.log('3. Membuat file ZIP (finku_cpanel_deploy.zip)...');
    execSync(`powershell.exe -Command "Compress-Archive -Path '${tempDir}\\*' -DestinationPath '${zipPath}' -Force"`, { stdio: 'inherit' });

    // 6. Clean up temp folder
    console.log('4. Membersihkan folder temp...');
    fs.rmSync(tempDir, { recursive: true, force: true });

    console.log('\n✅ BERHASIL! Berkas siap diunggah ke cPanel.');
    console.log(`Lokasi file: ${zipPath}`);
    console.log('\nSilakan unggah file ZIP tersebut ke folder luar public_html cPanel Anda, lalu ikuti panduan ekstraksi.');

} catch (error) {
    console.error('❌ Terjadi kesalahan:', error.message);
    process.exit(1);
}
