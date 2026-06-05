<?php
session_start();

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/functions.php';

$db = Database::getInstance()->getConnection();
$pageTitle = 'Scan QR Code - Inventaris Lab';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        #reader {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            background: #000;
            min-height: 250px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-800 to-blue-500 min-h-screen p-5">
    <div class="max-w-2xl mx-auto">
        <!-- Header Card -->
        <div class="card bg-white/95 shadow-2xl p-8 text-center mb-5">
            <div class="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-qrcode text-white text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Scan QR Code Aset</h2>
            <p class="text-gray-600">Arahkan kamera ke QR Code aset laboratorium</p>
        </div>

        <!-- Scanner Section -->
        <div class="card bg-white/95 shadow-2xl p-6 mb-5" id="scannerSection">
            <h4 class="text-gray-800 font-semibold mb-4">
                <i class="fas fa-camera text-blue-600 mr-2"></i>Scanner QR Code
            </h4>
            
            <div id="reader"></div>
            
            <div class="mt-4 flex gap-2">
                <button class="btn btn-primary flex-grow" id="startScan">
                    <i class="fas fa-video mr-2"></i>Mulai Scan
                </button>
                <button class="btn btn-error" id="stopScan" style="display: none;">
                    <i class="fas fa-stop mr-2"></i>Stop
                </button>
            </div>

            <div class="mt-4">
                <p class="text-gray-600 text-sm mb-2">
                    <i class="fas fa-info-circle mr-1"></i>Atau input kode aset manual:
                </p>
                <div class="flex gap-2">
                    <input type="text" id="manualInput" class="input input-bordered flex-grow" placeholder="Masukkan kode aset (INV-001)">
                    <button class="btn btn-primary" id="submitManual">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Result Section -->
        <div class="card bg-white/95 shadow-2xl p-6 mb-5 hidden" id="resultSection">
            <div id="resultContent"></div>
            <button class="btn btn-primary w-full mt-4" id="btnScanLain">
                <i class="fas fa-redo mr-2"></i>Scan Aset Lain
            </button>
        </div>

        <!-- Back Link -->
        <div class="text-center mt-4">
            <a href="index.php" class="text-white hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
            </a>
        </div>
    </div>

    <script>
        let html5QrcodeScanner = null;
        let currentAsset = null;

        document.getElementById('startScan').addEventListener('click', startScanner);
        document.getElementById('stopScan').addEventListener('click', stopScanner);
        document.getElementById('submitManual').addEventListener('click', submitManual);
        document.getElementById('manualInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') submitManual();
        });
        document.getElementById('btnScanLain').addEventListener('click', resetForm);

        function startScanner() {
            document.getElementById('reader').innerHTML = '';
            document.getElementById('startScan').style.display = 'none';
            document.getElementById('stopScan').style.display = 'block';

            html5QrcodeScanner = new Html5Qrcode("reader");
            
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 150 }
                },
                onScanSuccess,
                onScanFailure
            ).catch(err => {
                console.error("Error starting scanner:", err);
                alert("Gagal mengakses kamera. Pastikan izin kamera diberikan.");
                stopScanner();
            });
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                }).catch(err => {
                    console.error("Error stopping scanner:", err);
                });
            }
            document.getElementById('startScan').style.display = 'block';
            document.getElementById('stopScan').style.display = 'none';
        }

        function onScanSuccess(decodedText) {
            stopScanner();
            processQRData(decodedText);
        }

        function onScanFailure(error) {
            // Silent fail - continue scanning
        }

        function submitManual() {
            const input = document.getElementById('manualInput').value.trim();
            if (input) {
                // Check if it's a URL or direct code
                if (input.includes('detail.php?id=')) {
                    const urlParams = new URLSearchParams(input.split('?')[1]);
                    const id = urlParams.get('id');
                    if (id) checkAssetById(id);
                } else {
                    // Assume it's an asset code
                    checkAssetByCode(input);
                }
            }
        }

        function processQRData(data) {
            // Check if it's a URL from our system
            if (data.includes('detail.php?id=')) {
                const urlParams = new URLSearchParams(data.split('?')[1]);
                const id = urlParams.get('id');
                if (id) checkAssetById(id);
            } else {
                // Assume it's an asset code
                checkAssetByCode(data);
            }
        }

        function checkAssetById(id) {
            fetch('assets/check_id.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAssetResult(data);
                    } else {
                        showError(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Terjadi kesalahan saat memuat data aset.');
                });
        }

        function checkAssetByCode(code) {
            fetch('assets/check_kode.php?kode=' + encodeURIComponent(code))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        checkAssetById(data.id);
                    } else {
                        showError(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Terjadi kesalahan saat mengecek aset.');
                });
        }

        function showAssetResult(data) {
            const resultSection = document.getElementById('resultSection');
            const resultContent = document.getElementById('resultContent');
            
            resultSection.classList.remove('hidden');
            resultContent.innerHTML = `
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-green-600 text-4xl"></i>
                    </div>
                    <h4 class="text-gray-800 font-bold text-lg mb-1">Aset Ditemukan!</h4>
                    <p class="text-gray-500 text-sm mb-4">${escapeHtml(data.kode_aset)}</p>
                    
                    <div class="bg-gray-50 rounded-xl p-4 mb-4 text-left">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 text-sm">Nama Aset</span>
                            <span class="text-gray-800 font-semibold text-sm">${escapeHtml(data.nama_barang)}</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 text-sm">Status</span>
                            <span class="badge ${data.status === 'tersedia' ? 'badge-success' : (data.status === 'dipinjam' ? 'badge-warning' : 'badge-error')}">
                                ${escapeHtml(data.status)}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 text-sm">Kategori</span>
                            <span class="text-gray-800 text-sm">${escapeHtml(data.kategori || '-')}</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="borrowings/form-pinjam.php?asset_id=${data.id}" 
                           class="btn btn-primary flex-1 text-sm ${data.status !== 'tersedia' ? 'opacity-50 pointer-events-none' : ''}">
                            <i class="fas fa-handshake mr-1"></i> Pinjam
                        </a>
                        <a href="assets/detail.php?id=${data.id}" 
                           class="btn btn-neutral flex-1 text-sm">
                            <i class="fas fa-info-circle mr-1"></i> Detail
                        </a>
                    </div>
                    ${data.status !== 'tersedia' ? '<p class="text-xs text-yellow-600 mt-2"><i class="fas fa-exclamation-triangle mr-1"></i>Aset sedang ' + data.status + ', tidak bisa dipinjam</p>' : ''}
                </div>
            `;
            document.getElementById('scannerSection').style.display = 'none';
        }

        function showError(message) {
            const resultSection = document.getElementById('resultSection');
            const resultContent = document.getElementById('resultContent');
            
            resultSection.classList.remove('hidden');
            resultSection.classList.add('border-4', 'border-red-500');
            resultContent.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-times-circle text-6xl text-red-500 mb-4"></i>
                    <h4 class="text-gray-800 font-bold mb-2">Aset Tidak Ditemukan</h4>
                    <p class="text-gray-600 mb-4">${message}</p>
                </div>
            `;
            document.getElementById('scannerSection').style.display = 'none';
        }

        function resetForm() {
            document.getElementById('resultSection').classList.add('hidden');
            document.getElementById('resultSection').classList.remove('border-4', 'border-red-500');
            document.getElementById('scannerSection').style.display = 'block';
            document.getElementById('manualInput').value = '';
        }
    </script>
</body>
</html>
