<?php

chdir(__DIR__);

// 1. HANDLE DIRECT FILE DOWNLOAD VIA UCRM GATEWAY
if (isset($_GET['file'])) {
    $fileToDownload = basename($_GET['file']);
    $filePath = __DIR__ . '/data/' . $fileToDownload;

    if (file_exists($filePath) && is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'zip') {
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $fileToDownload . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    } else {
        echo 'Error: File not found on server.';
        exit;
    }
}

// 2. HANDLE FILE DELETION REQUEST
if (isset($_POST['delete_file'])) {
    $fileToDelete = basename($_POST['delete_file']);
    $filePath = __DIR__ . '/data/' . $fileToDelete;
    
    if (file_exists($filePath) && is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'zip') {
        unlink($filePath);
        $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $cleanUrl);
        exit;
    }
}

// 3. SECURELY LOAD CONFIGURATION FROM CONFIG.JSON
$configFile = __DIR__ . '/data/config.json';
$config = [];
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true) ?: [];
}

// Load values from configuration
$appKey = $config['api_key'] ?? 'rDG6LDXY2FwopPMRo7mcgAnm6MvfBT44D4pJlmRFEJArzuVvGGTTU1daEb7vBpHj';
$dateFrom = $config['date_from'] ?? null;
$dateTo = $config['date_to'] ?? null;

// HTML layout with UCRM matching design
echo '<html lang="en"><head><title>Export</title><style>
    body { font-family: sans-serif; padding: 20px; background: #f8f9fa; color: #333; display: flex; flex-direction: column; min-height: 90vh; }
    .content { flex: 1; }
    .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block;}
    .btn:hover { background: #0056b3; }
    .btn-secondary { background: #6c757d; font-size: 14px; padding: 8px 15px; margin-bottom: 15px; }
    .btn-secondary:hover { background: #5a6268; }
    .btn-danger { background: #dc3545; padding: 5px 10px; font-size: 13px; border-radius: 4px; border: none; color: white; cursor: pointer; }
    .btn-danger:hover { background: #bd2130; }
    .log-box { background: #222; color: #fff; padding: 15px; font-family: monospace; border-radius: 4px; margin-top: 20px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; }
    .alert { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    ul { list-style-type: none; padding: 0; }
    li { background: white; padding: 10px; margin-bottom: 5px; border-radius: 4px; border: 1px solid #e3e6f0; display: flex; justify-content: space-between; align-items: center; max-width: 600px; }
    .file-link { color: #007bff; text-decoration: none; font-weight: bold; }
    .file-link:hover { text-decoration: underline; }
    .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e3e6f0; font-size: 14px; color: #6c757d; }
    .footer a { color: #007bff; text-decoration: none; }
    .footer a:hover { text-decoration: underline; }
</style></head><body><div class="content">';

echo '<h1>Bulk Invoice PDF Export</h1>';

// FIX: Absolute path from CRM root ensures 100% functionality inside any iframe zanoření
echo '<a href="/crm/system/plugins/djradeesek__ubnt-ucrm-uisp-unms-bulk-invoice-pdf-export/configure" class="btn btn-secondary" target="_parent">⚙️ Go to Period & API Key Settings</a>';

if (!$dateFrom || !$dateTo || !$appKey) {
    echo '<div style="background:#fff3cd; color:#856404; padding:15px; border-radius:4px; margin-bottom:20px;">';
    echo '<strong>Warning:</strong> You have not filled in all required fields in the plugin settings (API key, date from, or date to). ';
    echo 'Please click the button above, fill in the data, and save it.';
    echo '</div>';
} else {
    echo '<p><strong>Currently set period:</strong> From ' . htmlspecialchars($dateFrom) . ' To ' . htmlspecialchars($dateTo) . '</p>';
    echo '<p><strong>API Key Used:</strong> ' . htmlspecialchars(substr($appKey, 0, 8)) . '...</p>';
    
    echo '<form method="POST" action="" target="_self" onsubmit="document.getElementById(\'btn-text\').innerText=\'⏳ Generating export, please wait...\';">';
    echo '<button type="submit" name="run_export" value="1" class="btn" id="btn-text">🚀 Start Export Now</button>';
    echo '</form>';
}

$baseUrl = strtok($_SERVER['REQUEST_URI'], '?');

// 4. THE EXPORT PROCESS AFTER SUBMISSION
if (isset($_POST['run_export']) && $dateFrom && $dateTo && $appKey) {
    echo '<div class="alert">Export process started. You can track progress below:</div>';
    echo '<div class="log-box">';

    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0777, true);
    }

    $folderName = $dateFrom . '_to_' . $dateTo;
    echo "Connecting to internal API for the period from $dateFrom to $dateTo...\n";
    flush();

    $apiUrl = 'http://localhost/crm/api/v1.0/invoices?createdDateFrom=' . urlencode($dateFrom) . '&createdDateTo=' . urlencode($dateTo) . '&limit=1000';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Auth-App-Key: ' . $appKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "ERROR: API returned HTTP code $httpCode.\n";
        echo "Please make sure your App Key has 'Read' permission enabled for Invoices under Security settings.\n";
        echo '</div></div></body></html>';
        exit;
    }

    $invoices = json_decode($response, true);

    if (empty($invoices) || !is_array($invoices)) {
        echo "Authorized successfully, but no invoices were found for this period.\n";
        echo '</div></div></body></html>';
        exit;
    }

    $zipFileName = __DIR__ . '/data/Export_Invoices_' . $folderName . '.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        echo "ERROR: Could not create ZIP file on disk.\n";
        echo '</div></div></body></html>';
        exit;
    }

    echo "Found " . count($invoices) . " invoices. Starting individual PDF downloads...\n";
    flush();

    foreach ($invoices as $invoice) {
        $invoiceId = $invoice['id'];
        $invoiceNumber = $invoice['number'];
        
        $pdfUrl = 'http://localhost/crm/api/v1.0/invoices/' . $invoiceId . '/pdf';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pdfUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Auth-App-Key: ' . $appKey]);
        $pdfData = curl_exec($ch);
        $pdfCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($pdfCode === 200 && $pdfData) {
            $zip->addFromString('Invoice_' . $invoiceNumber . '.pdf', $pdfData);
            echo "Saved to ZIP: Invoice number $invoiceNumber\n";
            flush();
        } else {
            echo "Error downloading PDF for invoice $invoiceNumber (Code: $pdfCode)\n";
            flush();
        }
    }

    $zip->close();
    
    echo "\n==================================================\n";
    echo "DONE! All invoices have been saved to the ZIP archive.\n";
    echo "==================================================\n";
    echo '</div>';
    
    $cleanDownloadUrl = $baseUrl . '?file=Export_Invoices_' . $folderName . '.zip';
    echo '<br><a href="' . $cleanDownloadUrl . '" class="btn" style="background:#28a745;">⬇️ Download ZIP Export Now</a><br><br>';
}

// 5. LIST GENERATED FILES ON DISK (Placed at the end to instantly show newly created files)
if (is_dir(__DIR__ . '/data')) {
    $files = glob(__DIR__ . '/data/*.zip');
    if (!empty($files)) {
        echo '<h3>Generated files available on server disk:</h3><ul>';
        foreach ($files as $file) {
            $bname = basename($file);
            $downloadUrl = $baseUrl . '?file=' . urlencode($bname);
            echo '<li>';
            echo '  <a href="' . $downloadUrl . '" class="file-link">⬇️ ' . htmlspecialchars($bname) . '</a>';
            echo '  <form method="POST" action="" style="margin:0;" onsubmit="return confirm(\'Are you sure you want to permanently delete this ZIP file from the server?\');">';
            echo '    <input type="hidden" name="delete_file" value="' . htmlspecialchars($bname) . '">';
            echo '    <button type="submit" class="btn-danger">🗑️ Delete</button>';
            echo '  </form>';
            echo '</li>';
        }
        echo '</ul>';
    }
}

echo '</div>'; // End of .content

// 6. BRAND FOOTER
echo '<div class="footer">';
echo 'Powered by <a href="http://broadcaster.cz" target="_blank">BroadCaster, s.r.o.</a> &copy; ' . date('Y');
echo '</div>';

echo '</body></html>';
