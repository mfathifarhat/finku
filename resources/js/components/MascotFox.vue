<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    level: number;
    equipped: string[];
    size?: number | string;
    hideBadge?: boolean;
}>(), {
    size: 200,
    hideBadge: false
});

// Determine mascot evolution stage name
const stageName = computed(() => {
    if (props.level <= 2) return 'Baby Finku Fox (Rookie)';
    if (props.level <= 4) return 'Explorer Finku Fox (Tracker)';
    if (props.level <= 6) return 'Saver Finku Fox (Saver)';
    if (props.level <= 9) return 'Planner Finku Fox (Planner)';
    return 'Grandmaster Finku Fox (Suhu)';
});

// Helper to check if an accessory is equipped
const isEquipped = (code: string) => {
    return props.equipped && props.equipped.includes(code);
};

// Base fox styling classes and SVGs based on level
const mainColor = computed(() => {
    if (props.level <= 2) return '#F97316'; // Soft orange
    if (props.level <= 4) return '#EA580C'; // Bright explorer orange
    if (props.level <= 6) return '#D97706'; // Golden yellow orange
    if (props.level <= 9) return '#C2410C'; // Rich amber orange
    return '#E11D48'; // Majestic rose/red orange
});
</script>

<template>
    <div class="flex flex-col items-center justify-center select-none" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg 
            viewBox="0 0 200 200" 
            class="w-full h-full drop-shadow-xl transition-all duration-500 hover:scale-105"
            xmlns="http://www.w3.org/2000/svg"
        >
            <!-- Definitions for gradients and filters -->
            <defs>
                <!-- Golden Aura for Suhu (Lv 10+) -->
                <radialGradient id="auraGlow" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#FBBF24" stop-opacity="0.6" />
                    <stop offset="70%" stop-color="#F59E0B" stop-opacity="0.2" />
                    <stop offset="100%" stop-color="#F59E0B" stop-opacity="0" />
                </radialGradient>
                
                <!-- Ear Shadow Gradient -->
                <linearGradient id="earShadow" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#000000" stop-opacity="0.3" />
                    <stop offset="100%" stop-color="#000000" stop-opacity="0" />
                </linearGradient>

                <!-- Soft Glow Filter -->
                <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="8" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
            </defs>

            <!-- Background Aura (Suhu Level 10+) -->
            <circle v-if="level >= 10" cx="100" cy="100" r="85" fill="url(#auraGlow)" filter="url(#glow)" class="animate-pulse" />

            <!-- ACCESSORY BACK: Royal Cape (cape_royal) -->
            <g v-if="isEquipped('cape_royal')">
                <path d="M 60,110 L 40,175 C 40,185 160,185 160,175 L 140,110 Z" fill="#DC2626" stroke="#991B1B" stroke-width="2" />
                <path d="M 60,110 L 80,120 L 120,120 L 140,110" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5" />
                <!-- Gold Clasp -->
                <circle cx="100" cy="115" r="5" fill="#EAB308" stroke="#CA8A04" stroke-width="1" />
            </g>

            <!-- 1. TAIL -->
            <!-- Tailwind changes size/look depending on Level -->
            <g class="tail-layer transition-all duration-500">
                <!-- Baby Tail (Level 1-2) -->
                <path v-if="level <= 2" d="M 125,120 C 150,110 165,125 155,145 C 145,165 125,160 125,130" :fill="mainColor" />
                <!-- Medium Tail (Level 3-9) -->
                <path v-else-if="level <= 9" d="M 125,120 C 165,95 185,125 170,155 C 155,185 125,170 120,135" :fill="mainColor" />
                <!-- Majestic Fire Tail (Level 10+) -->
                <g v-else>
                    <path d="M 125,120 C 175,85 200,125 185,165 C 170,205 125,185 120,135" fill="#E11D48" />
                    <!-- Flamy tips -->
                    <path d="M 170,110 C 185,90 200,110 185,135 Z" fill="#F97316" />
                    <path d="M 155,140 C 170,125 180,145 165,165 Z" fill="#FBBF24" />
                </g>
                <!-- Tail White Tip -->
                <path v-if="level <= 2" d="M 150,130 C 158,128 160,135 155,145 C 151,149 146,145 146,138 Z" fill="#FFFFFF" />
                <path v-else d="M 160,130 C 176,122 178,135 170,155 C 164,162 155,155 155,145 Z" fill="#FFFFFF" />
            </g>

            <!-- 2. BODY -->
            <g class="body-layer transition-all duration-500">
                <!-- Base Body -->
                <path d="M 70,110 L 60,160 C 60,175 140,175 140,160 L 130,110 Z" :fill="mainColor" />
                <!-- White Chest Fluff -->
                <path d="M 85,110 C 85,135 115,135 115,110 C 105,125 95,125 85,110" fill="#FFFFFF" />

                <!-- LEVEL EVOLUTION BODY ITEMS -->
                <!-- Explorer Compass Vest (Level 3-4) -->
                <g v-if="level >= 3 && level <= 4">
                    <circle cx="100" cy="140" r="10" fill="#0284C7" stroke="#0369A1" stroke-width="1.5" />
                    <!-- Compass Needle -->
                    <line x1="100" y1="140" x2="100" y2="134" stroke="#EF4444" stroke-width="2" stroke-linecap="round" />
                    <line x1="100" y1="140" x2="100" y2="146" stroke="#F8FAFC" stroke-width="2" stroke-linecap="round" />
                </g>
                <!-- Gold Coin Holding (Level 5-6) -->
                <g v-if="level >= 5 && level <= 6">
                    <!-- Hands holding a giant gold coin -->
                    <circle cx="100" cy="140" r="14" fill="#F59E0B" stroke="#D97706" stroke-width="2" />
                    <circle cx="100" cy="140" r="10" fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-dasharray="3,2" />
                    <!-- Dollar sign or coin detail -->
                    <text x="100" y="145" font-family="sans-serif" font-weight="900" font-size="14" fill="#FFFFFF" text-anchor="middle">C</text>
                    <!-- Small paws -->
                    <circle cx="82" cy="142" r="5" :fill="mainColor" />
                    <circle cx="118" cy="142" r="5" :fill="mainColor" />
                </g>
                <!-- Ledger / Book (Level 7-9) -->
                <g v-if="level >= 7 && level <= 9">
                    <!-- Wise holding book -->
                    <rect x="85" y="130" width="30" height="22" rx="3" fill="#1E293B" stroke="#0F172A" stroke-width="1.5" />
                    <!-- Pages -->
                    <rect x="88" y="132" width="11" height="18" fill="#F8FAFC" />
                    <rect x="101" y="132" width="11" height="18" fill="#F8FAFC" />
                    <line x1="90" y1="136" x2="96" y2="136" stroke="#64748B" stroke-width="1" />
                    <line x1="90" y1="140" x2="96" y2="140" stroke="#64748B" stroke-width="1" />
                    <line x1="104" y1="136" x2="110" y2="136" stroke="#64748B" stroke-width="1" />
                    <line x1="104" y1="140" x2="110" y2="140" stroke="#64748B" stroke-width="1" />
                    <!-- Paw -->
                    <circle cx="80" cy="140" r="5" :fill="mainColor" />
                    <circle cx="120" cy="140" r="5" :fill="mainColor" />
                </g>
                <!-- Majestic Staff (Level 10+) -->
                <g v-if="level >= 10">
                    <!-- Grandmaster Staff -->
                    <line x1="60" y1="170" x2="60" y2="90" stroke="#78350F" stroke-width="4" stroke-linecap="round" />
                    <!-- Floating magical crystal at top of staff -->
                    <polygon points="60,75 66,85 60,95 54,85" fill="#FBBF24" stroke="#F59E0B" stroke-width="1" />
                    <circle cx="60" cy="85" r="8" fill="#FBBF24" opacity="0.3" filter="url(#glow)" />
                    <!-- Hand holding staff -->
                    <circle cx="60" cy="135" r="6" :fill="mainColor" />
                </g>
            </g>

            <!-- 3. FEET / PAWS -->
            <g class="feet-layer">
                <ellipse cx="78" cy="172" rx="10" ry="6" fill="#1E293B" />
                <ellipse cx="122" cy="172" rx="10" ry="6" fill="#1E293B" />
            </g>

            <!-- 4. EARS -->
            <g class="ears-layer transition-all duration-500">
                <!-- Left Ear -->
                <path d="M 55,60 L 40,10 L 80,45 Z" :fill="mainColor" />
                <path d="M 58,55 L 47,22 L 74,45 Z" fill="#FCA5A5" /> <!-- Inner Ear Pink -->
                <!-- Right Ear -->
                <path d="M 145,60 L 160,10 L 120,45 Z" :fill="mainColor" />
                <path d="M 142,55 L 153,22 L 126,45 Z" fill="#FCA5A5" /> <!-- Inner Ear Pink -->
            </g>

            <!-- 5. HEAD / FACE -->
            <!-- Head size changes based on Level: Rookies (Lv 1-2) have larger heads relative to body (Cute Chibi style) -->
            <g class="head-layer transition-all duration-500">
                <path d="M 50,75 C 50,45 150,45 150,75 C 150,105 130,115 100,115 C 70,115 50,105 50,75" :fill="mainColor" />
                <!-- White Cheeks -->
                <path d="M 50,75 C 50,95 75,108 85,95 C 95,85 70,75 50,75" fill="#FFFFFF" />
                <path d="M 150,75 C 150,95 125,108 115,95 C 105,85 130,75 150,75" fill="#FFFFFF" />
                
                <!-- Nose snout -->
                <polygon points="95,95 105,95 100,101" fill="#0F172A" />

                <!-- EYES -->
                <!-- Rookie Eyes (Lv 1-2): Big round cute eyes -->
                <g v-if="level <= 2">
                    <circle cx="78" cy="74" r="7" fill="#0F172A" />
                    <circle cx="76" cy="71" r="2.5" fill="#FFFFFF" /> <!-- Sparkle -->
                    <circle cx="122" cy="74" r="7" fill="#0F172A" />
                    <circle cx="124" cy="71" r="2.5" fill="#FFFFFF" /> <!-- Sparkle -->
                </g>
                <!-- Explorer & Saver Eyes (Lv 3-6): Smart smiling/round eyes -->
                <g v-else-if="level <= 6">
                    <circle cx="78" cy="74" r="5.5" fill="#0F172A" />
                    <circle cx="76.5" cy="71.5" r="2" fill="#FFFFFF" />
                    <circle cx="122" cy="74" r="5.5" fill="#0F172A" />
                    <circle cx="120.5" cy="71.5" r="2" fill="#FFFFFF" />
                    <!-- Smart eyebrows -->
                    <path d="M 70,64 Q 78,61 84,65" fill="none" stroke="#7C2D12" stroke-width="1.5" stroke-linecap="round" />
                    <path d="M 130,64 Q 122,61 116,65" fill="none" stroke="#7C2D12" stroke-width="1.5" stroke-linecap="round" />
                </g>
                <!-- Wise Eyes / Spectacles (Lv 7+) -->
                <g v-else>
                    <!-- Happy Wise Eyes -->
                    <path d="M 72,76 Q 78,70 84,76" fill="none" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round" />
                    <path d="M 128,76 Q 122,70 116,76" fill="none" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round" />
                    <!-- Wise eyebrows -->
                    <path d="M 68,63 Q 78,57 84,63" fill="none" stroke="#7C2D12" stroke-width="2.5" stroke-linecap="round" />
                    <path d="M 132,63 Q 122,57 116,63" fill="none" stroke="#7C2D12" stroke-width="2.5" stroke-linecap="round" />
                </g>

                <!-- Blush cheeks -->
                <ellipse cx="63" cy="83" rx="6" ry="3.5" fill="#F87171" opacity="0.4" />
                <ellipse cx="137" cy="83" rx="6" ry="3.5" fill="#F87171" opacity="0.4" />
            </g>

            <!-- ACCESSORY LAYER SLOT: GLASSES (glasses_cool) -->
            <g v-if="isEquipped('glasses_cool')">
                <!-- Cool Sunglasses -->
                <!-- Left lens -->
                <polygon points="62,70 90,70 87,88 65,88" fill="#0F172A" stroke="#E2E8F0" stroke-width="1" />
                <!-- Right lens -->
                <polygon points="110,70 138,70 135,88 113,88" fill="#0F172A" stroke="#E2E8F0" stroke-width="1" />
                <!-- Bridge -->
                <rect x="90" y="72" width="20" height="4" fill="#0F172A" />
                <!-- Reflections -->
                <line x1="68" y1="73" x2="78" y2="85" stroke="#FFFFFF" stroke-width="2" opacity="0.6" stroke-linecap="round" />
                <line x1="116" y1="73" x2="126" y2="85" stroke="#FFFFFF" stroke-width="2" opacity="0.6" stroke-linecap="round" />
            </g>

            <!-- ACCESSORY LAYER SLOT: NECK (scarf_winter, tie_fancy) -->
            <!-- 1. Winter Scarf -->
            <g v-if="isEquipped('scarf_winter')">
                <path d="M 68,105 C 80,100 120,100 132,105 C 135,115 125,120 100,120 C 75,120 65,115 68,105 Z" fill="#DC2626" stroke="#B91C1C" stroke-width="1" />
                <!-- Hanging part -->
                <path d="M 115,115 L 128,145 C 130,150 120,152 116,146 L 108,118 Z" fill="#DC2626" stroke="#B91C1C" stroke-width="1" />
                <!-- Stripes -->
                <path d="M 80,108 L 84,118" stroke="#F8FAFC" stroke-width="3" />
                <path d="M 95,109 L 99,119" stroke="#F8FAFC" stroke-width="3" />
                <path d="M 110,108 L 114,118" stroke="#F8FAFC" stroke-width="3" />
                <path d="M 120,126 L 128,129" stroke="#F8FAFC" stroke-width="3" />
                <path d="M 116,136 L 124,139" stroke="#F8FAFC" stroke-width="3" />
            </g>
            <!-- 2. Fancy Bow Tie -->
            <g v-if="isEquipped('tie_fancy')">
                <!-- Left bow -->
                <polygon points="100,113 85,105 85,121" fill="#0284C7" stroke="#0369A1" stroke-width="1" />
                <!-- Right bow -->
                <polygon points="100,113 115,105 115,121" fill="#0284C7" stroke="#0369A1" stroke-width="1" />
                <!-- Center knot -->
                <circle cx="100" cy="113" r="4" fill="#0369A1" />
            </g>

            <!-- ACCESSORY LAYER SLOT: HAT (hat_detective, crown_gold) -->
            <!-- 1. Detective Hat -->
            <g v-if="isEquipped('hat_detective')">
                <!-- Brim -->
                <path d="M 40,56 C 70,50 130,50 160,56 C 160,60 140,63 100,63 C 60,63 40,60 40,56 Z" fill="#78350F" stroke="#451A03" stroke-width="1" />
                <!-- Crown of hat -->
                <path d="M 60,53 C 62,20 138,20 140,53 Z" fill="#78350F" stroke="#451A03" stroke-width="1" />
                <!-- Ribbon -->
                <path d="M 61,48 C 70,44 130,44 139,48 C 139.5,53 60.5,53 61,48 Z" fill="#0F172A" />
            </g>
            <!-- 2. Gold Crown -->
            <g v-if="isEquipped('crown_gold')">
                <!-- Crown Base -->
                <path d="M 70,45 L 130,45 L 125,55 L 75,55 Z" fill="#F59E0B" stroke="#D97706" stroke-width="1.5" />
                <!-- Crown Spikes -->
                <polygon points="70,45 65,25 85,35 100,15 115,35 135,25 130,45" fill="#F59E0B" stroke="#D97706" stroke-width="1.5" />
                <!-- Jewels -->
                <circle cx="65" cy="25" r="2.5" fill="#DC2626" />
                <circle cx="100" cy="15" r="3" fill="#2563EB" />
                <circle cx="135" cy="25" r="2.5" fill="#DC2626" />
                <rect x="96" y="47" width="8" height="5" rx="1" fill="#16A34A" />
                <circle cx="82" cy="50" r="2" fill="#2563EB" />
                <circle cx="118" cy="50" r="2" fill="#2563EB" />
            </g>
        </svg>

        <!-- Dynamic evolution badge underneath -->
        <span v-if="!hideBadge" class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-2 px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full border border-slate-200/50 dark:border-slate-850 shadow-sm">
            🦊 {{ stageName }}
        </span>
    </div>
</template>

<style scoped>
.tail-layer {
    transform-origin: 120px 130px;
    animation: tailWag 4s ease-in-out infinite alternate;
}

.head-layer {
    transform-origin: 100px 85px;
    animation: headBob 6s ease-in-out infinite alternate;
}

@keyframes tailWag {
    0% { transform: rotate(-3deg); }
    100% { transform: rotate(5deg); }
}

@keyframes headBob {
    0% { transform: translateY(0px) rotate(-1deg); }
    100% { transform: translateY(1.5px) rotate(1deg); }
}
</style>
