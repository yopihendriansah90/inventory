# Alur Program Inventory Siap Jual

QC mencatat **satu tiket per kiriman dari satu vendor**. Tiket bisa berisi beberapa SKU. Setiap kombinasi model, warna, dan ukuran memiliki SKU dan stok sendiri.

Aplikasi menampilkan SKU singkat berdasarkan model, warna, dan ukuran, misalnya `CEL-HIT-L`. Varian lain yang menghasilkan singkatan sama mendapat nomor pembeda seperti `CEL-HIT-L-02`. Kode dan QR lama tetap bisa digunakan untuk menemukan varian yang sama.

## Peran

| Peran | Tanggung jawab |
|---|---|
| QC | Mendaftarkan vendor dan produk bila diperlukan, mencatat pemeriksaan, serta menyediakan BA dan QR tiket. |
| Admin Gudang | Membuka tiket dari daftar atau QR, mencocokkan barang yang lolos, dan mengonfirmasi penerimaan. |
| CS | Mencari dan melihat stok siap jual per SKU. |

## Form Tiket Penerimaan

1. **Vendor & Tiket.** Cari vendor berdasarkan nama atau ID. Tombol **Add** membuka popup pendaftaran. Vendor yang disimpan mendapat ID seperti `VND-000001` dan langsung dipilih. Isi nomor surat jalan dan tanggal penerimaan satu kali.
2. **Pemeriksaan Barang.** Cari varian berdasarkan SKU atau nama produk. Jika belum terdaftar, tambahkan model, warna, dan ukuran lewat popup. **Add to ticket** mengosongkan pencarian, menempatkan item baru di paling atas, dan memindahkan fokus ke jumlah fisik. Isi jumlah fisik, jumlah gagal, dan catatan per SKU. Sistem menghitung jumlah lolos. Baris bisa dihapus sebelum tiket diselesaikan.
3. **Tinjau & Selesaikan.** Periksa ringkasan vendor dan total barang. **Finish inspection** menyimpan tiket dan seluruh baris sekaligus, lalu membuka detail dengan nomor seperti `QC-000001`.

## BA dan serah terima

- **BA vendor** menampilkan semua barang, jumlah diperiksa, lolos, gagal, dan catatan QC.
- **BA gudang** menampilkan barang yang lolos dan jumlah yang harus diterima. Setelah konfirmasi, BA menampilkan status penerimaan serta nama admin.
- Kedua BA bisa dicetak atau disimpan sebagai PDF lewat dialog cetak browser.
- **QR tiket** membuka detail penerimaan untuk Admin Gudang. QR tersedia sebagai popup dan unduhan SVG jika ada barang yang lolos. Di menu **Pindai QR**, Gudang bisa memakai kamera, mengunggah gambar QR, atau memasukkan nomor tiket. Di HP, menu **Penerimaan**, **Pindai QR**, dan **Stok** berada di navigasi bawah.
- **QR SKU** tersedia di detail tiket QC dan bisa dipakai ulang untuk membuka wizard QC dengan varian yang sudah dipilih. QR tiket berlaku untuk satu kiriman tertentu.

## Aturan stok

- Nomor surat jalan boleh sama di vendor yang berbeda. Satu vendor hanya memiliki satu tiket untuk nomor surat jalan yang sama.
- Jumlah fisik minimal satu. Jumlah gagal berada di antara nol dan jumlah fisik.
- Jika semua barang gagal, tiket tersedia di QC bersama BA vendor.
- Konfirmasi gudang menambah stok tiap SKU sebesar jumlah yang lolos. Konfirmasi ulang mempertahankan jumlah stok yang sudah tercatat.

## Skenario demo

Vendor membawa 20 Gamis Amina Hitam ukuran M dan 10 ukuran L. QC mencatat 2 unit ukuran M gagal karena jahitan lepas. BA vendor menunjukkan **30 diperiksa, 28 lolos, 2 gagal**. Admin Gudang memindai satu QR, lalu mengonfirmasi penerimaan **18 ukuran M dan 10 ukuran L**. Dengan data awal M sebanyak 5 dan L sebanyak 0, CS melihat stok baru **23 ukuran M dan 10 ukuran L**.
