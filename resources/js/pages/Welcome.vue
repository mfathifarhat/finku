<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';
import { register } from '@/routes';
import AppLogo from '@/components/AppLogo.vue';

const siteConfig = {
    navItems: [
        { name: 'Beranda', href: '#beranda' },
        { name: 'Fitur', href: '#fitur' },
        { name: 'FAQ', href: '#faq' },
    ],
    hero: {
        badge: { icon: 'ri-sparkling-2-line', text: 'Smart Financial Platform' },
        title: 'Kelola Uangmu<br><em class="font-serif italic font-normal text-green not-italic">Lebih Cerdas,</em><br>Setiap Hari',
        description: 'Pantau pemasukan, pengeluaran, dan target tabungan secara real-time dalam satu dashboard. Tidak perlu spreadsheet, tidak perlu repot.',
        buttonText: 'Mulai Gratis',
        buttonIcon: 'ri-rocket-2-line',
        imageUrl: 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?w=900&q=80'
    },
    balanceCard: {
        label: 'Total Saldo',
        amount: 'Rp 24,5 Jt',
        change: '+12,4% bulan ini'
    },
    goalCard: {
        label: 'Target Liburan',
        percentage: 72,
        currentAmount: 'Rp 7,2 Jt',
        targetAmount: 'Rp 10 Jt'
    },
    tickerItems: [
        { icon: 'ri-book-open-line', title: 'Catat Transaksi', description: 'Pencatatan pemasukan & pengeluaran mudah' },
        { icon: 'ri-calendar-check-line', title: 'Budget Planner', description: 'Rencanakan anggaran bulanan' },
        { icon: 'ri-wallet-line', title: 'Smart Saving', description: 'Target tabungan & investasi' },
        { icon: 'ri-ai-generate', title: 'AI Insights', description: 'Analisis & rekomendasi cerdas' },
        { icon: 'ri-medal-2-line', title: 'Level Up', description: 'Gamifikasi & kumpulkan XP' },
        { icon: 'ri-award-line', title: 'Badge Awards', description: 'Raih prestasi eksklusif' }
    ],
    features: {
        eyebrow: 'Fitur Unggulan',
        title: 'Semua yang kamu<br><em class="font-serif italic font-normal text-green not-italic">butuhkan ada di sini</em>',
        description: 'Solusi lengkap untuk mengatur keuangan pribadi dan bisnis — dari pencatatan sederhana hingga analisis mendalam.',
        items: [
            { icon: 'ri-book-open-line', title: 'Catat Pengeluaran & Pendapatan', description: 'Catat setiap transaksi dengan mudah, baik pemasukan maupun pengeluaran. Dilengkapi kategori dan sub-kategori untuk analisis lebih detail.', tag: 'Smart Recording' },
            { icon: 'ri-calendar-check-line', title: 'Perencanaan Pengeluaran Bulanan', description: 'Buat rencana anggaran bulanan yang realistis dengan metode 50/30/20 atau kustom sesuai kebutuhanmu.', tag: 'Budget Planning' },
            { icon: 'ri-wallet-line', title: 'Tabungan & Investasi', description: 'Atur target tabungan, pantau progres, dan dapatkan rekomendasi investasi sesuai profil risiko dan tujuan finansialmu.', tag: 'Smart Saving' },
            { icon: 'ri-ai-generate', title: 'Analisis AI Cerdas', description: 'Dapatkan insight dan rekomendasi personal dari AI berdasarkan pola pengeluaranmu. Deteksi pemborosan dan saran penghematan otomatis.', tag: 'AI Powered' },
            { icon: 'ri-medal-2-line', title: 'Gamifikasi Leveling', description: 'Kumpulkan XP setiap kali mencatat transaksi dan mencapai target keuangan. Naikkan level karaktermu dari Pemula hingga Financial Master!', tag: 'Level Up' },
            { icon: 'ri-award-line', title: 'Sistem Badge Prestasi', description: 'Dapatkan berbagai badge eksklusif untuk setiap pencapaian keuangan. Koleksi semua badge dan tunjukkan progress finansialmu!', tag: 'Achievement' }
        ]
    },
    faqs: {
        eyebrow: 'FAQ',
        title: 'Pertanyaan<br><em class="font-serif italic font-normal text-[#4BDAAB] not-italic">yang sering ditanya</em>',
        description: 'Temukan jawaban lengkap seputar fitur-fitur Finku di sini. Kami telah merangkum pertanyaan terpopuler untuk Anda.',
        items: [
            { question: 'Bagaimana cara mencatat pengeluaran dan pendapatan di Finku?', answer: 'Mencatat transaksi di Finku sangat mudah! Anda cukup tekan tombol "+" di dashboard, pilih jenis transaksi (pemasukan/pengeluaran), isi nominal, kategori, dan deskripsi. Transaksi akan langsung tersimpan dan terintegrasi dengan laporan keuangan Anda secara real-time.' },
            { question: 'Metode budgeting apa saja yang tersedia untuk perencanaan bulanan?', answer: 'Finku menyediakan 3 metode budgeting: 50/30/20 (kebutuhan, keinginan, tabungan), 70/20/10, dan metode kustom di mana Anda bisa menentukan persentase sendiri. Anda juga bisa mengatur notifikasi ketika pengeluaran mendekati batas yang ditentukan.' },
            { question: 'Bagaimana cara mengatur target tabungan dan investasi?', answer: 'Anda bisa membuat target tabungan dengan menentukan nama target, nominal yang ingin dicapai, dan tenggat waktu. Finku akan menghitung berapa banyak yang perlu ditabung setiap bulan dan menampilkan progres visual. Untuk investasi, Finku memberikan rekomendasi produk sesuai profil risiko Anda.' },
            { question: 'Apa saja yang bisa dianalisis oleh AI Finku?', answer: 'AI Finku dapat menganalisis pola pengeluaran Anda, mendeteksi kebocoran keuangan, memberikan rekomendasi penghematan bulanan, memprediksi cash flow masa depan, dan memberi saran investasi yang dipersonalisasi berdasarkan kebiasaan finansial Anda.' },
            { question: 'Bagaimana sistem leveling dan badge bekerja?', answer: 'Setiap kali Anda mencatat transaksi, mencapai target tabungan, atau konsisten mengikuti anggaran, Anda akan mendapatkan XP (Experience Points). XP akan menaikkan level karakter Anda dari "Financial Newbie" hingga "Financial Master". Badge prestasi diberikan untuk pencapaian spesifik seperti "30 Days Record" atau "Saving Hero".' },
            { question: 'Apakah data keuangan saya aman di Finku?', answer: 'Keamanan data adalah prioritas utama kami. Finku menggunakan enkripsi AES-256 (standar perbankan) untuk semua data sensitif. Kami juga tidak pernah menyimpan kredensial perbankan Anda dan semua koneksi dilakukan melalui API yang tersertifikasi.' }
        ]
    },
    footer: {
        description: 'Solusi cerdas untuk mengelola keuangan pribadi dan bisnis Anda dengan lebih efisien dan terukur.',
        copyright: '© 2026 Finku. All rights reserved.',
        columns: [
            {
                title: 'Menu',
                links: [
                    { label: 'Beranda', url: '#beranda' },
                    { label: 'Fitur', url: '#fitur' },
                    { label: 'FAQ', url: '#faq' }
                ]
            }
        ],
        bottomLinks: [
            { label: 'Kebijakan Privasi', url: '#' },
            { label: 'Syarat & Ketentuan', url: '#' },
            { label: 'Cookie', url: '#' }
        ]
    }
};

const duplicatedTickerItems = [...siteConfig.tickerItems, ...siteConfig.tickerItems];

// Reactive States
const isScrolled = ref(false);
const isMenuOpen = ref(false);
const isTickerPaused = ref(false);
const activeFaqIndex = ref<number | null>(null);

// Navbar scroll logic
const handleScroll = () => {
    isScrolled.value = window.scrollY > 40;
};

// Handle resize to close menu on desktop
const handleResize = () => {
    if (window.innerWidth >= 768 && isMenuOpen.value) {
        isMenuOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    window.addEventListener('resize', handleResize);
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('resize', handleResize);
});

const toggleMobileMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMobileMenu = () => {
    isMenuOpen.value = false;
};

const toggleFaq = (index: number) => {
    activeFaqIndex.value = activeFaqIndex.value === index ? null : index;
};

const handleAnchorClick = (e: MouseEvent, href: string) => {
    if (href.startsWith('#')) {
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            closeMobileMenu();
        }
    }
};
</script>

<template>
    <Head title="Welcome">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet" />
    </Head>

    <div class="landing-page bg-[#FAFAF8] text-navy overflow-x-hidden min-h-screen">
        <!-- NAVBAR -->
        <nav
            id="navbar"
            class="fixed top-0 w-full z-[100] px-[max(2rem,calc((100%-1200px)/2+2rem))] navbar-transition"
            :class="isScrolled ? 'bg-[#FAFAF8]/90 backdrop-blur-md shadow-[0_1px_0_rgba(4,10,29,0.08)]' : 'bg-transparent'"
        >
            <div class="flex items-center justify-between h-[72px]">
                <a href="#beranda" @click="(e) => handleAnchorClick(e, '#beranda')" class="flex items-center gap-2 text-2xl font-extrabold text-navy no-underline">
                    <AppLogo class="text-2xl font-normal"/>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-8">
                    <ul class="flex list-none gap-8">
                        <li v-for="item in siteConfig.navItems" :key="item.name">
                            <a :href="item.href" @click="(e) => handleAnchorClick(e, item.href)" class="text-sm font-medium text-[#6B7280] hover:text-navy transition-colors no-underline">
                                {{ item.name }}
                            </a>
                        </li>
                    </ul>
                    <div class="flex items-center gap-3">
                        <template v-if="$page.props.auth.user">
                            <Link
                                :href="dashboard()"
                                class="bg-green text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-green-alt hover:-translate-y-0.5 transition-all no-underline shadow-md shadow-green/20"
                            >
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                :href="login()"
                                class="text-navy px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-gray-100 transition-all no-underline"
                            >
                                Sign In
                            </Link>
                            <Link
                                :href="register()"
                                class="bg-green text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-green-alt hover:-translate-y-0.5 transition-all no-underline shadow-md shadow-green/20"
                            >
                                Sign Up
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- Hamburger Menu Button (Mobile) -->
                <button
                    id="menu-btn"
                    @click="toggleMobileMenu"
                    class="md:hidden flex flex-col items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100 transition-all"
                >
                    <i id="menu-icon" :class="isMenuOpen ? 'ri-close-line' : 'ri-menu-line'" class="text-2xl text-navy"></i>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div
                id="mobile-menu"
                :class="{ 'hidden': !isMenuOpen }"
                class="md:hidden absolute top-[72px] left-0 right-0 bg-white/95 backdrop-blur-md shadow-lg rounded-b-2xl overflow-hidden transition-all duration-300"
            >
                <ul class="flex flex-col py-4">
                    <li v-for="item in siteConfig.navItems" :key="item.name">
                        <a
                            :href="item.href"
                            @click="(e) => handleAnchorClick(e, item.href)"
                            class="block px-6 py-3 text-sm font-medium text-[#6B7280] hover:bg-gray-50 hover:text-navy transition-colors no-underline"
                        >
                            {{ item.name }}
                        </a>
                    </li>
                </ul>
                <div class="flex flex-col gap-3 px-6 pb-6">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboard()"
                            @click="closeMobileMenu"
                            class="text-center bg-green text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-green-alt transition-all no-underline shadow-md shadow-green/20"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            @click="closeMobileMenu"
                            class="text-center text-navy px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-gray-100 transition-all no-underline border border-gray-200"
                        >
                            Sign In
                        </Link>
                        <Link
                            :href="register()"
                            @click="closeMobileMenu"
                            class="text-center bg-green text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-green-alt transition-all no-underline shadow-md shadow-green/20"
                        >
                            Sign Up
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- HERO SECTION -->
        <section
            id="beranda"
            class="min-h-screen pt-[120px] pb-20 px-[max(2rem,calc((100%-1200px)/2+2rem))] grid lg:grid-cols-2 gap-16 items-center"
        >
            <div>
                <div class="inline-flex items-center gap-2 bg-cream border border-cream-dark text-green-dark text-[0.8rem] font-bold uppercase tracking-wide px-[14px] py-[6px] rounded-full mb-6">
                    <i :class="[siteConfig.hero.badge.icon, 'text-[0.85rem]']"></i>
                    <span>{{ siteConfig.hero.badge.text }}</span>
                </div>
                <h1
                    class="text-[clamp(2.8rem,5vw,4.5rem)] font-extrabold leading-[1.08] text-navy tracking-[-0.03em]"
                    v-html="siteConfig.hero.title"
                ></h1>
                <p class="mt-6 text-[1.0625rem] leading-relaxed text-[#6B7280] max-w-[480px]">
                    {{ siteConfig.hero.description }}
                </p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboard()"
                            class="bg-green text-white px-7 py-[14px] rounded-[14px] text-[0.9375rem] font-bold flex items-center gap-2 hover:bg-green-alt hover:-translate-y-[2px] hover:shadow-[0_12px_24px_rgba(26,163,117,0.25)] transition-all no-underline"
                        >
                            <i class="ri-dashboard-line"></i>
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="register()"
                            class="bg-green text-white px-7 py-[14px] rounded-[14px] text-[0.9375rem] font-bold flex items-center gap-2 hover:bg-green-alt hover:-translate-y-[2px] hover:shadow-[0_12px_24px_rgba(26,163,117,0.25)] transition-all no-underline"
                        >
                            <i :class="siteConfig.hero.buttonIcon"></i>
                            {{ siteConfig.hero.buttonText }}
                        </Link>
                    </template>
                </div>
            </div>

            <div class="relative">
                <div class="bg-navy-mid rounded-[28px] overflow-hidden aspect-[4/3]">
                    <img :src="siteConfig.hero.imageUrl" alt="Finku Dashboard" class="w-full h-full object-cover opacity-85" />
                </div>
                <div class="float-card !bottom-[-20px] !left-[-20px] min-w-[200px]">
                    <div class="text-[0.72rem] font-semibold tracking-wide uppercase text-[#6B7280] mb-1">
                        {{ siteConfig.balanceCard.label }}
                    </div>
                    <div class="text-2xl font-extrabold text-navy leading-none">
                        {{ siteConfig.balanceCard.amount }}
                    </div>
                    <div class="inline-flex items-center gap-1 text-[0.75rem] font-semibold text-green bg-green/10 px-2 py-[3px] rounded-full mt-1.5">
                        <i class="ri-arrow-up-line"></i> <span>{{ siteConfig.balanceCard.change }}</span>
                    </div>
                </div>
                <div class="float-card !top-[-16px] !right-[-20px] min-w-[170px]">
                    <div class="text-[0.72rem] font-semibold tracking-wide uppercase text-[#6B7280] mb-2">
                        {{ siteConfig.goalCard.label }}
                    </div>
                    <div class="goal-bar-wrap bg-[#EAE7DF] rounded-full h-[6px] overflow-hidden">
                        <div
                            class="goal-bar-fill bg-green rounded-full h-[6px] animate-progress-slow"
                            :style="{ '--target-percentage': siteConfig.goalCard.percentage + '%' }"
                        ></div>
                    </div>
                    <div class="text-[1.1rem] font-extrabold text-navy leading-none mt-1.5">
                        {{ siteConfig.goalCard.percentage }}%
                    </div>
                    <div class="text-[0.72rem] text-[#6B7280] mt-0.5">
                        {{ siteConfig.goalCard.currentAmount }} dari {{ siteConfig.goalCard.targetAmount }}
                    </div>
                </div>
            </div>
        </section>

        <!-- TICKER -->
        <div
            class="bg-navy py-3.5 overflow-hidden relative cursor-pointer ticker-container"
            @mouseenter="isTickerPaused = true"
            @mouseleave="isTickerPaused = false"
        >
            <div
                class="flex gap-0 w-max animate-ticker"
                :style="{ animationPlayState: isTickerPaused ? 'paused' : 'running' }"
            >
                <div
                    v-for="(item, index) in duplicatedTickerItems"
                    :key="index"
                    class="ticker-item flex items-center gap-3 px-10 whitespace-nowrap text-[0.825rem] font-semibold text-white/55 transition-all duration-300 hover:scale-110 hover:text-white cursor-pointer"
                >
                    <i :class="[item.icon, 'text-green']"></i> <strong class="text-white">{{ item.title }}</strong> — {{ item.description }}
                </div>
            </div>
        </div>

        <!-- FEATURES -->
        <section id="fitur" class="py-[100px] px-[max(2rem,calc((100%-1200px)/2+2rem))]">
            <div class="inline-flex items-center gap-2 text-green text-[0.8rem] font-bold uppercase tracking-wide mb-4">
                <i class="ri-medal-line"></i> <span>{{ siteConfig.features.eyebrow }}</span>
            </div>
            <h2
                class="text-[clamp(2rem,3.5vw,3rem)] font-extrabold text-navy tracking-[-0.025em] leading-[1.15]"
                v-html="siteConfig.features.title"
            ></h2>
            <p class="mt-4 text-base leading-relaxed text-[#6B7280] max-w-[520px]">
                {{ siteConfig.features.description }}
            </p>
            <div class="grid md:grid-cols-3 gap-6 mt-16">
                <div
                    v-for="(feature, index) in siteConfig.features.items"
                    :key="index"
                    class="group bg-white border border-cream-dark rounded-3xl p-8 feat-card hover:bg-navy hover:border-navy hover:shadow-[0_20px_48px_rgba(4,10,29,0.06)] hover:-translate-y-1 transition-all duration-300 cursor-pointer"
                >
                    <div class="w-[52px] h-[52px] rounded-[14px] flex items-center justify-center text-2xl mb-6 bg-cream text-green group-hover:bg-green/15 transition-colors duration-300">
                        <i :class="feature.icon"></i>
                    </div>
                    <div class="text-[1.0625rem] font-bold text-navy mb-2.5 group-hover:text-white transition-colors duration-300">
                        {{ feature.title }}
                    </div>
                    <p class="text-[0.9rem] leading-relaxed text-[#6B7280] group-hover:text-white/60 transition-colors duration-300">
                        {{ feature.description }}
                    </p>
                    <span class="inline-block mt-5 bg-green/10 text-green text-[0.75rem] font-bold tracking-wide px-3 py-1 rounded-full group-hover:bg-green/20 group-hover:text-[#4BDAAB] transition-colors duration-300">
                        {{ feature.tag }}
                    </span>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="py-[100px] px-[max(2rem,calc((100%-1200px)/2+2rem))] bg-navy">
            <div class="grid lg:grid-cols-[1fr_1.8fr] gap-24 items-start">
                <div class="lg:sticky lg:top-[100px]">
                    <div class="inline-flex items-center gap-2 text-green text-[0.8rem] font-bold uppercase tracking-wide mb-4">
                        <i class="ri-question-line"></i> <span>{{ siteConfig.faqs.eyebrow }}</span>
                    </div>
                    <h2
                        class="text-[clamp(2rem,3.5vw,3rem)] font-extrabold text-white tracking-[-0.025em] leading-[1.15]"
                        v-html="siteConfig.faqs.title"
                    ></h2>
                    <p class="mt-4 text-base leading-relaxed text-white/55 max-w-[520px]">
                        {{ siteConfig.faqs.description }}
                    </p>
                </div>
                <div class="flex flex-col">
                    <div
                        v-for="(faq, index) in siteConfig.faqs.items"
                        :key="index"
                        class="faq-item border-b border-white/10 first:border-t border-white/10"
                        :class="{ 'open': activeFaqIndex === index }"
                    >
                        <div @click="toggleFaq(index)" class="faq-q flex justify-between items-center gap-4 py-6 cursor-pointer">
                            <h3 class="text-[1.0625rem] font-semibold text-white">{{ faq.question }}</h3>
                            <i class="ri-add-line text-xl text-green flex-shrink-0"></i>
                        </div>
                        <div class="faq-a" :class="{ 'open': activeFaqIndex === index }">
                            <p class="text-[0.9375rem] leading-relaxed text-white/55 pb-6">{{ faq.answer }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="bg-white pt-[72px] pb-9 px-[max(2rem,calc((100%-1200px)/2+2rem))]">
            <div class="grid md:grid-cols-[2fr_1fr] gap-12 pb-12 border-b border-gray-200">
                <div>
                    <div class="flex items-center gap-2 text-2xl font-extrabold text-navy mb-4">
                        <AppLogo class="text-2xl font-normal"/>
                    </div>
                    <p class="text-[0.9rem] leading-relaxed text-gray-500 max-w-[480px]">
                        {{ siteConfig.footer.description }}
                    </p>
                </div>
                <div v-for="(col, index) in siteConfig.footer.columns" :key="index">
                    <h4 class="text-[0.8rem] font-bold tracking-wide uppercase text-gray-400 mb-5">{{ col.title }}</h4>
                    <ul class="flex flex-col gap-3">
                        <li v-for="(link, lIndex) in col.links" :key="lIndex">
                            <a :href="link.url" @click="(e) => handleAnchorClick(e, link.url)" class="text-[0.9rem] text-gray-500 hover:text-green transition-colors no-underline">
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="flex justify-center items-center pt-8 gap-4 flex-wrap">
                <p class="text-[0.8rem] text-gray-400">{{ siteConfig.footer.copyright }}</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
:global(html) {
    scroll-behavior: smooth;
}

.landing-page {
    font-family: var(--font-landing-sans);
}

.navbar-transition {
    transition: background 0.3s, box-shadow 0.3s;
}

.feat-card {
    transition: all 0.3s ease;
}

.faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s ease, padding 0.25s;
}

.faq-a.open {
    max-height: 300px;
    padding-bottom: 1.5rem;
}

.faq-q i {
    transition: transform 0.25s;
}

.faq-item.open .faq-q i {
    transform: rotate(45deg);
}

.float-card {
    position: absolute;
    background: white;
    border-radius: 16px;
    padding: 14px 18px;
    box-shadow: 0 8px 32px rgba(4, 10, 29, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.goal-bar-wrap {
    background: #EAE7DF;
    border-radius: 100px;
    height: 6px;
}

.animate-ticker {
    animation: ticker 28s linear infinite;
}

.goal-bar-fill {
    animation: progressSlow 2.5s ease-in-out forwards;
}

@keyframes ticker {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

@keyframes progressSlow {
    0% {
        width: 0%;
    }
    100% {
        width: var(--target-percentage, 72%);
    }
}

.ticker-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.ticker-container:hover .ticker-item {
    transform: scale(0.95);
    opacity: 0.7;
}

.ticker-container .ticker-item:hover {
    transform: scale(1.15);
    opacity: 1;
    color: white;
}
</style>
