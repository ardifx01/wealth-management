@extends("layouts.main")

@section("content")
    <main>
        <section class="bg-blue-600 text-white py-20 md:py-32">
            <div class="container mx-auto px-6 text-center">
                <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-4">
                    Kelola Uangmu dengan Lebih Mudah
                </h1>
                <p class="text-lg md:text-xl font-light mb-8 max-w-2xl mx-auto">
                    Aplikasi manajemen keuangan yang membantumu mencatat pengeluaran, menghemat, dan mencapai tujuan finansial.
                </p>
                <a href="{{ route('filament.admin.auth.register') }}" class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold text-lg shadow-lg hover:bg-gray-100 transition-colors">
                    Daftar Gratis
                </a>
            </div>
        </section>

        <section id="fitur" class="py-16 md:py-24">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-bold text-center mb-12">
                    Kenapa Harus Menggunakan Aplikasi Kami?
                </h2>
                <div class="grid md:grid-cols-3 gap-8 text-center">
                    <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                        <div class="text-blue-500 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m-4 0v-6a2 2 0 012-2h2a2 2 0 012 2v6m0 0a1 1 0 001 1h2a2 2 0 002-2v-6a2 2 0 00-2-2h-2a2 2 0 00-2 2v6" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Pencatatan Cepat</h3>
                        <p class="text-gray-600">Catat setiap pengeluaran dan pemasukanmu dalam hitungan detik. Mudah dan praktis.</p>
                    </div>

                    <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                        <div class="text-blue-500 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Laporan Visual</h3>
                        <p class="text-gray-600">Lihat grafik pengeluaranmu setiap bulan untuk tahu ke mana saja uangmu pergi.</p>
                    </div>

                    <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-xl transition-shadow">
                        <div class="text-blue-500 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2-4h2m4 0h2m-4 0v2m-4 0v2" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Budgeting Pintar</h3>
                        <p class="text-gray-600">Atur batasan anggaran untuk setiap kategori dan dapatkan notifikasi saat hampir melewati batas.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="kontak" class="bg-blue-600 py-16 md:py-24 text-white">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    Siap Mengontrol Keuanganmu?
                </h2>
                <p class="text-lg mb-8 max-w-2xl mx-auto">
                    Mulai perjalanan finansialmu sekarang. Daftar gratis, tidak ada kartu kredit yang diperlukan.
                </p>
                <a href="{{ route('filament.admin.auth.register') }}" class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold text-lg shadow-lg hover:bg-gray-100 transition-colors">
                    Daftar Sekarang
                </a>
            </div>
        </section>
    </main>
@endsection