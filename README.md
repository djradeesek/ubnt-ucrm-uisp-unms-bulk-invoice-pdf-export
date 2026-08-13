# Bulk Invoice PDF Export to ZIP for UCRM / UISP / UNMS

A professional plugin for Ubiquiti UISP/UCRM that allows administrators to export individual invoice PDF files into a single ZIP archive for a selected date range. 

## Features
- **Custom Date Range:** Select any start and end date directly from the plugin settings.
- **On-Demand Execution:** Run the export instantly with a button click from your Billing menu—no cron job or wait times required.
- **File Management:** Download generated ZIP files via secure UCRM gateway links and delete old exports directly from the web interface to save disk space.
- **Autonomy:** Built using native PHP cURL and zip extensions, meaning zero composer dependencies are required inside the archive.

## Installation
1. Pack the root files (`manifest.json`, `public.php`, `main.php`, `README.md`) into a `ZIP` archive.
2. Go to your UCRM dashboard -> **System** -> **Plugins** -> **Upload plugin**.
3. Enable the plugin and enter your **UCRM API App Key** and desired date parameters in the **Settings** menu.
4. Access the tool directly from **Billing** -> **Bulk Invoice PDF Export**.

## Author
Powered by [BroadCaster, s.r.o.](http://broadcaster.cz)

MySOFT.cz
BroadCaster, s.r.o.
www.broadcaster.cz
tel. +420 - 4111 90 111

Tento plugin exportuje všechny vydané faktury v zadaném období a nabídne je ke stažení jako zip soubor. Vhodné pro další import do úèetních systémù.