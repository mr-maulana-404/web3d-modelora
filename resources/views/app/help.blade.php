@extends('layouts.app')
@section('title', 'Help & FAQ')

@section('content')
  <x-dashboard-navbar />

  <main class="container-fluid py-5 px-5" style="background-color: #2E323D !important; color: #f8f9fa;">
    <br><br>
    
    <div class="help-header text-center text-md-start d-md-flex align-items-center justify-content-between">
        <div>
            <h1 class="display-5 fw-bold text-white mb-2"><i class="fas fa-question-circle text-warning me-2"></i>Help & FAQ</h1>
            <p class="lead mb-0">Panduan lengkap fitur, alur kerja platform 3D, dan bantuan teknis.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="#contact" class="btn btn-warning px-4 py-2 fw-bold shadow-sm">
                <i class="fab fa-whatsapp me-2"></i>Hubungi Admin
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            
            <div class="faq-section">
                <h4 class="faq-title"><i class="fas fa-user-shield me-2"></i> Halaman Utama & Akses Akun</h4>
                <div class="accordion" id="authAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#auth1">
                                Bagaimana cara melakukan registrasi akun baru?
                            </button>
                        </h2>
                        <div id="auth1" class="accordion-collapse collapse" data-bs-parent="#authAccordion">
                            <div class="accordion-body text-light">
                                Klik tombol <strong>"Register"</strong> pada Landing Page. Isi form pendaftaran dengan email dan password yang valid. Setelah berhasil, akun akan langsung dibuat dan sistem otomatis melakukan login ke dalam halaman dashboard tanpa verifikasi ulang.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#auth2">
                                Bagaimana alur proses login ke dalam sistem?
                            </button>
                        </h2>
                        <div id="auth2" class="accordion-collapse collapse" data-bs-parent="#authAccordion">
                            <div class="accordion-body text-light">
                                Klik tombol <strong>"Login"</strong>, masukkan email beserta password yang telah didaftarkan sebelumnya. Jika sukses, session Anda akan aktif dan sistem otomatis mengarahkan ke halaman <strong>"User Model"</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="faq-section">
                <h4 class="faq-title"><i class="fas fa-cube me-2"></i> Manajemen Model 3D</h4>
                <div class="accordion" id="modelAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#model1">
                                Bagaimana cara mengunggah model 3D milik saya?
                            </button>
                        </h2>
                        <div id="model1" class="accordion-collapse collapse" data-bs-parent="#modelAccordion">
                            <div class="accordion-body text-light">
                                Masuk ke halaman <strong>User Model</strong>, lalu klik tombol <strong>"+ Upload Model"</strong>. Anda dapat mengunggah file berformat <code>.glb</code> atau file <code>.zip</code> yang berisi file <code>.gltf</code>. Sistem mengizinkan pengunggahan maksimal hingga 5 file secara bersamaan dalam satu waktu.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#model2">
                                Bagaimana mengubah nama, kategori, dan thumbnail model?
                            </button>
                        </h2>
                        <div id="model2" class="accordion-collapse collapse" data-bs-parent="#modelAccordion">
                            <div class="accordion-body text-light">
                                Klik pada salah satu model di daftar koleksi pribadi Anda, kemudian klik tombol <strong>"Edit Data"</strong>. Anda dapat memperbarui nama, kategori, serta mengunggah gambar thumbnail baru. Akhiri dengan menekan tombol <strong>"Save Changes"</strong>.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#model3">
                                Apa arti dari fitur "Publish Model"?
                            </button>
                        </h2>
                        <div id="model3" class="accordion-collapse collapse" data-bs-parent="#modelAccordion">
                            <div class="accordion-body text-light">
                                Ketika Anda menekan tombol <strong>"Publish"</strong> pada suatu model, tombol tersebut akan berubah menjadi "Unpublish". Model Anda kini berstatus publik dan dapat dilihat oleh pengunjung umum atau user lain melalui halaman galeri utama (Home).
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="faq-section">
                <h4 class="faq-title"><i class="fas fa-paint-brush me-2"></i> Halaman Model Customization</h4>
                <div class="accordion" id="customAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#custom1">
                                Bagaimana cara masuk dan melakukan kustomisasi objek 3D?
                            </button>
                        </h2>
                        <div id="custom1" class="accordion-collapse collapse" data-bs-parent="#customAccordion">
                            <div class="accordion-body text-light">
                                Pilih salah satu model pada halaman koleksi, lalu klik <strong>"Customize"</strong>. Sistem akan memuat objek ke dalam 3D Canvas Editor. Di sini Anda bisa memilih material bawaan (seperti Wood, Gold, Fabric, Metal) atau mengunggah gambar tekstur kustom Anda sendiri dari storage lokal perangkat.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#custom2">
                                Bagaimana cara mengambil foto/capture sudut pandang model (Viewport)?
                            </button>
                        </h2>
                        <div id="custom2" class="accordion-collapse collapse" data-bs-parent="#customAccordion">
                            <div class="accordion-body text-light">
                                Klik tombol berlambang <strong>Kamera (Capture Image)</strong> pada toolbar editor. Gunakan menu dropdown rasio untuk menentukan aspek rasio gambar yang diinginkan. Sistem akan menangkap tampilan kanvas 3D secara presisi sesuai posisi kamera terkini. Hasil capture dapat diunduh dalam format <code>.png</code> dalam bentuk arsip zip.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#custom3">
                                Bagaimana cara mengekspor/menyimpan dan mendownload hasil kustomisasi?
                            </button>
                        </h2>
                        <div id="custom3" class="accordion-collapse collapse" data-bs-parent="#customAccordion">
                            <div class="accordion-body text-light">
                                <p>Klik tombol <strong>"Save & Download"</strong> di dalam editor untuk mengunci perubahan dan menyimpan data ke halaman "Saved Customization".</p>
                                <p class="mb-0">Pada halaman Saved Customization, klik tombol <strong>"Download"</strong> pada proyek kustomisasi terkait, lalu pilih format file yang Anda inginkan: <strong>.glb, .obj, atau .stl</strong>. File terbuat dan diunduh otomatis secara aman.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="faq-section">
                <h4 class="faq-title"><i class="fas fa-magic me-2"></i> Fitur 3D Texture Enhancement</h4>
                <div class="accordion" id="enhanceAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#enhance1">
                                Apa perbedaan Mode "Texture Enhance" dengan Mode "Repair + Retexture"?
                            </button>
                        </h2>
                        <div id="enhance1" class="accordion-collapse collapse" data-bs-parent="#enhanceAccordion">
                            <div class="accordion-body text-light">
                                <ul>
                                    <li class="mb-2"><strong>Mode Texture Enhance (Gratis):</strong> Digunakan untuk menaikkan ketajaman, upscaling, serta kontras tekstur asli tanpa memodifikasi pola dasarnya secara signifikan.</li>
                                    <li><strong>Mode Repair + Retexture (Experimental - Membutuhkan Credit):</strong> Menggunakan generator kecerdasan buatan (AI) untuk memperbaiki topologi struktur objek sekaligus menghasilkan set tekstur baru berdasarkan deskripsi teks/prompt yang Anda ketikkan (atau dibiarkan tanpa prompt).</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#enhance2">
                                Bagaimana cara mengisi ulang (Top Up) Kredit AI akun saya?
                            </button>
                        </h2>
                        <div id="enhance2" class="accordion-collapse collapse" data-bs-parent="#enhanceAccordion">
                            <div class="accordion-body text-light">
                                Setiap proses pemanfaatan fitur <em>Repair + Retexture</em> membutuhkan konsumsi kredit tertentu (misal: 20 kredit). Anda dapat menambahkan jumlah saldo dengan mengklik tombol/fitur <strong>"Top Up Kredit"</strong> pada halaman Enhancement, lalu tentukan nominal paket kredit yang diperlukan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4" id="contact">
            <div class="contact-card shadow">
                <i class="fab fa-whatsapp text-success display-4 mb-3"></i>
                <h4 class="text-white fw-bold mb-2">Butuh Bantuan Lebih?</h4>
                <p class="small mb-4">Jika Anda mengalami kendala teknis sistem, error saat memproses rendering model, atau kendala transaksi top up, silakan hubungi saluran pusat layanan pelanggan kami di bawah ini.</p>
                
                <div class="bg-dark p-3 rounded mb-4 border border-secondary text-start">
                    <small class="text-warning d-block mb-1 font-monospace">CONTACT PERSON :</small>
                    <span class="fs-5 text-white fw-bold"><i class="fas fa-headset me-2 text-muted"></i>Admin Support</span>
                </div>

                <a href="https://api.whatsapp.com/send?phone=6285853496389&text=Halo%20Admin%2C%20saya%20mengalami%20kendala%20di%20Website%203D%20Platform.%20Mohon%20bantuannya." 
                   target="_blank" 
                   class="btn btn-success btn-lg w-100 fw-bold shadow-sm py-2">
                   <i class="fab fa-whatsapp me-2"></i>Chat WhatsApp Sekarang
                </a>
                
                <small class="d-block mt-3" style="font-size: 0.75rem;">
                    Jam Operasional: Setiap Hari (24 Jam)<br>Response rate: < 15 Menit.
                </small>
            </div>
        </div>
    </div>
  </main>

  {{-- Footer --}}
  <x-big-footer />
@endsection

@push('scripts')
  @vite('resources/js/pages/userdashboard.js')
@endpush