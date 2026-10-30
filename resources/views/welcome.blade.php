<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>PT Lautan Berkah</title>

        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-sans text-gray-950 antialiased dark:bg-gray-950 dark:text-white">

        {{-- HEADER --}}
        <header class="sticky top-0 z-50 border-b border-gray-100 bg-white/95 backdrop-blur-xl dark:border-white/[0.06] dark:bg-gray-950/95">
            <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-4 sm:px-6">

                <a href="{{ url('/') }}" class="flex items-center gap-2.5 transition-opacity hover:opacity-75">
                    <span class="grid size-7 place-items-center rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 ring-1 ring-amber-600/20">
                        <svg class="size-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 9.5c2.5-2.6 5-2.6 7.5 0s5 2.6 7.5 0 3.5-1.95 4-1.5M2.5 15.5c2.5-2.6 5-2.6 7.5 0s5 2.6 7.5 0 3.5-1.95 4-1.5" />
                        </svg>
                    </span>
                    <span class="text-sm font-bold tracking-tight text-gray-950 dark:text-white">PT Lautan Berkah</span>
                </a>

                <button
                    type="button"
                    aria-label="Toggle theme"
                    class="grid size-8 place-items-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/[0.06] dark:hover:text-gray-200"
                    onclick="const d = document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', d ? 'dark' : 'light');"
                >
                    <svg class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                    <svg class="block size-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                </button>
            </div>
        </header>

        <main>

            {{-- HERO --}}
            <section class="mx-auto max-w-5xl px-4 py-24 sm:px-6 sm:py-32">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-amber-600 dark:text-amber-400">PT Lautan Berkah</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-gray-950 sm:text-5xl dark:text-white">
                        Sistem informasi terpadu untuk operasional perusahaan.
                    </h1>
                    <p class="mt-5 text-base leading-relaxed text-gray-500 dark:text-gray-400">
                        Kami membangun platform digital yang membantu tim bergerak lebih efisien — mulai dari pengadaan, operasional, hingga pelaporan dalam satu dasbor terintegrasi.
                    </p>
                    <div class="mt-8">
                        <a href="#tentang" class="text-sm font-medium text-gray-500 transition-colors hover:text-gray-950 dark:text-gray-400 dark:hover:text-white">
                            Tentang kami ↓
                        </a>
                    </div>
                </div>
            </section>

            {{-- DIVIDER --}}
            <div class="border-t border-gray-100 dark:border-white/[0.06]"></div>

            {{-- TENTANG --}}
            <section id="tentang" class="mx-auto max-w-5xl px-4 py-20 sm:px-6 sm:py-28">
                <div class="grid grid-cols-1 gap-16 lg:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400">Tentang Kami</p>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-gray-950 sm:text-3xl dark:text-white">
                            Bergerak bersama menuju tata kelola yang lebih baik.
                        </h2>
                        <p class="mt-4 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            PT Lautan Berkah adalah perusahaan yang berkomitmen menghadirkan sistem informasi internal yang andal, transparan, dan mudah digunakan oleh seluruh lini organisasi.
                        </p>
                        <p class="mt-3 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            Dibangun di atas teknologi modern, platform kami dirancang untuk menyederhanakan proses pengadaan, pengelolaan aset, kepegawaian, dan pelaporan — sehingga tim dapat fokus pada pekerjaan yang benar-benar penting.
                        </p>
                    </div>
                    <div class="space-y-6">
                        @foreach([
                            ['Visi','Menjadi perusahaan terdepan dalam pengelolaan operasional berbasis sistem informasi yang transparan dan akuntabel.'],
                            ['Misi','Menyediakan platform digital yang memudahkan setiap proses bisnis, mendorong efisiensi tim, dan memperkuat tata kelola perusahaan.'],
                        ] as [$judul, $isi])
                        <div class="border-l-2 border-amber-500 pl-5">
                            <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400">{{ $judul }}</p>
                            <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $isi }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- DIVIDER --}}
            <div class="border-t border-gray-100 dark:border-white/[0.06]"></div>

            {{-- NILAI INTI --}}
            <section class="mx-auto max-w-5xl px-4 py-20 sm:px-6 sm:py-28">
                <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400">Nilai Inti</p>
                <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-gray-950 sm:text-3xl dark:text-white">Prinsip yang kami pegang.</h2>

                <dl class="mt-10 grid grid-cols-1 gap-x-12 gap-y-8 sm:grid-cols-2">
                    @foreach([
                        ['Integritas','Jujur, beretika, dan berkomitmen dalam setiap tindakan dan keputusan.'],
                        ['Profesional','Standar kerja tinggi dengan kompetensi yang terus dikembangkan.'],
                        ['Akuntabel','Setiap keputusan dan anggaran dapat dipertanggungjawabkan secara terbuka.'],
                        ['Kolaboratif','Sinergi produktif bersama tim, mitra, dan seluruh pemangku kepentingan.'],
                    ] as [$judul, $isi])
                    <div>
                        <dt class="text-sm font-bold text-gray-950 dark:text-white">{{ $judul }}</dt>
                        <dd class="mt-1 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $isi }}</dd>
                    </div>
                    @endforeach
                </dl>
            </section>

            {{-- DIVIDER --}}
            <div class="border-t border-gray-100 dark:border-white/[0.06]"></div>

            {{-- KONTAK --}}
            <section id="kontak" class="mx-auto max-w-5xl px-4 py-20 sm:px-6 sm:py-28">
                <div class="grid grid-cols-1 gap-16 lg:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400">Kontak</p>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-gray-950 sm:text-3xl dark:text-white">Hubungi kami.</h2>
                        <p class="mt-4 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            Untuk informasi lebih lanjut, silakan hubungi tim kami melalui kontak di bawah ini.
                        </p>
                    </div>
                    <dl class="space-y-5">
                        @foreach([
                            ['Alamat','Gedung PT Lautan Berkah, Kawasan Bisnis Terpadu, Indonesia'],
                            ['Email','kontak@lautanberkah.com'],
                            ['Jam Operasional','Senin – Jumat · 08.00 – 17.00 WIB'],
                        ] as [$label, $value])
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ $label }}</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $value }}</dd>
                        </div>
                        @endforeach
                    </dl>
                </div>
            </section>

        </main>

        {{-- FOOTER --}}
        <footer class="border-t border-gray-100 bg-white dark:border-white/[0.06] dark:bg-gray-950">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-6 sm:px-6">
                <div class="flex items-center gap-2">
                    <span class="grid size-6 place-items-center rounded-md bg-gradient-to-br from-amber-400 to-amber-600">
                        <svg class="size-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 9.5c2.5-2.6 5-2.6 7.5 0s5 2.6 7.5 0 3.5-1.95 4-1.5M2.5 15.5c2.5-2.6 5-2.6 7.5 0s5 2.6 7.5 0 3.5-1.95 4-1.5" />
                        </svg>
                    </span>
                    <span class="text-xs font-bold text-gray-950 dark:text-white">PT Lautan Berkah</span>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500">&copy; {{ date('Y') }} PT Lautan Berkah</p>
            </div>
        </footer>

    </body>
</html>
