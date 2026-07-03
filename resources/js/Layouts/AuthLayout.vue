<template>
    <div class="flex min-h-screen bg-surface">
        <!-- Left Side: Branding / Imagery -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-telkom-red overflow-hidden flex-col justify-between p-12 lg:p-24">
            
            <!-- Background Image & Overlay -->
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=2072&auto=format&fit=crop')] bg-cover bg-center opacity-40 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-telkom-red-dark/95 to-telkom-red/80 z-0 mix-blend-multiply"></div>
            
            <!-- Floating Decorative Ambient Lighting -->
            <div class="absolute top-1/4 right-0 w-72 h-72 bg-white/10 rounded-full blur-3xl z-0 pointer-events-none"></div>
            <div class="absolute bottom-1/4 left-10 w-64 h-64 bg-black/10 rounded-full blur-3xl z-0 pointer-events-none"></div>

            <!-- Animated Network Visual (SVG) -->
            <div class="absolute inset-0 z-0 pointer-events-none opacity-70">
                <svg viewBox="0 0 800 800" class="w-full h-full" preserveAspectRatio="xMidYMid slice">
                    <defs>
                        <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="4" result="blur" />
                            <feMerge>
                                <feMergeNode in="blur" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>
                    
                    <!-- Network Connections -->
                    <g stroke="rgba(255,255,255,0.15)" stroke-width="1.5">
                        <line x1="100" y1="200" x2="300" y2="150" />
                        <line x1="300" y1="150" x2="500" y2="250" />
                        <line x1="500" y1="250" x2="700" y2="150" />
                        <line x1="300" y1="150" x2="250" y2="400" />
                        <line x1="500" y1="250" x2="600" y2="500" />
                        <line x1="250" y1="400" x2="400" y2="600" />
                        <line x1="600" y1="500" x2="400" y2="600" />
                        <line x1="100" y1="500" x2="250" y2="400" />
                    </g>
                    
                    <!-- Animated Data Packets -->
                    <g stroke="rgba(255,255,255,0.8)" stroke-width="2" filter="url(#glow)">
                        <line x1="100" y1="200" x2="300" y2="150" stroke-dasharray="15 300" stroke-dashoffset="0">
                            <animate attributeName="stroke-dashoffset" from="315" to="0" dur="3s" repeatCount="indefinite" />
                        </line>
                        <line x1="300" y1="150" x2="500" y2="250" stroke-dasharray="20 300" stroke-dashoffset="0">
                            <animate attributeName="stroke-dashoffset" from="320" to="0" dur="2.5s" repeatCount="indefinite" />
                        </line>
                        <line x1="500" y1="250" x2="600" y2="500" stroke-dasharray="15 350" stroke-dashoffset="0">
                            <animate attributeName="stroke-dashoffset" from="365" to="0" dur="3.5s" repeatCount="indefinite" />
                        </line>
                        <line x1="400" y1="600" x2="250" y2="400" stroke-dasharray="15 300" stroke-dashoffset="0">
                            <animate attributeName="stroke-dashoffset" from="0" to="315" dur="2.8s" repeatCount="indefinite" />
                        </line>
                    </g>
                    
                    <!-- Network Nodes -->
                    <g fill="rgba(255,255,255,1)">
                        <circle cx="100" cy="200" r="4" filter="url(#glow)" />
                        <circle cx="300" cy="150" r="6" filter="url(#glow)">
                            <animate attributeName="r" values="6;8;6" dur="2s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="500" cy="250" r="7" filter="url(#glow)">
                            <animate attributeName="r" values="7;10;7" dur="2.5s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="700" cy="150" r="4" filter="url(#glow)" />
                        <circle cx="250" cy="400" r="5" filter="url(#glow)">
                            <animate attributeName="r" values="5;7;5" dur="3s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="600" cy="500" r="5" filter="url(#glow)" />
                        <circle cx="400" cy="600" r="6" filter="url(#glow)" />
                        <circle cx="100" cy="500" r="4" filter="url(#glow)" />
                        
                        <!-- Pulsing Rings (Signal effect) -->
                        <circle cx="500" cy="250" r="7" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1">
                            <animate attributeName="r" values="7;30" dur="2.5s" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="1;0" dur="2.5s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="300" cy="150" r="6" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1">
                            <animate attributeName="r" values="6;25" dur="2s" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="1;0" dur="2s" repeatCount="indefinite" />
                        </circle>
                    </g>
                </svg>
            </div>

            <!-- Top: Logo -->
            <div class="relative z-10">
                <img
                    src="/storage/telkom-indonesia-logo.svg"
                    alt="Telkom Indonesia"
                    class="h-12 w-auto object-contain brightness-0 invert opacity-100" 
                />
            </div>

            <!-- Middle: Main Content with Visual Elements -->
            <div class="relative z-10 my-auto pt-12">
                <!-- Status Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 text-white/90 text-sm font-medium mb-6 backdrop-blur-md shadow-sm">
                    <span class="flex h-2 w-2 rounded-full bg-green-400 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    </span>
                    Sistem Aktif & Terhubung
                </div>
                
                <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-5 leading-tight text-white">
                    Sistem Monitoring Operasional
                </h2>
                <p class="text-lg text-white/80 max-w-md font-medium leading-relaxed mb-10">
                    Pantau kinerja infrastruktur dan kelola operasional dengan lebih cepat, presisi, dan terintegrasi penuh.
                </p>

                <!-- Animated Network Visual has been moved to the background -->
            </div>

            <!-- Bottom: Footer -->
            <div class="relative z-10 text-white/60 text-sm mt-8">
                &copy; {{ new Date().getFullYear() }} Divisi Government Service Regional Sulbagteng.
            </div>
        </div>

        <!-- Right Side: Auth Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12 sm:px-12 lg:px-24">
            <div class="w-full max-w-md">
                <slot />
            </div>
        </div>
    </div>
</template>
