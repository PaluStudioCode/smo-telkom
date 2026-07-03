import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: [
                    'Inter',
                    'Gotham Rounded',
                    'Gotham',
                    'Nunito Sans',
                    ...defaultTheme.fontFamily.sans,
                ],
            },
            spacing: {
                'section': '1.5rem',
                'section-lg': '2rem',
                'panel': '1rem',
                'panel-lg': '1.5rem',
                'control': '2.5rem',
            },
            borderRadius: {
                xs: '0.25rem',
                sm: 'calc(var(--radius) - 4px)',
                md: 'calc(var(--radius) - 2px)',
                lg: 'var(--radius)',
                xl: '0.75rem',
                panel: '0.5rem',
            },
            boxShadow: {
                soft: '0 1px 2px 0 rgb(15 23 42 / 0.06)',
                panel: '0 8px 24px -18px rgb(15 23 42 / 0.45)',
                focus: '0 0 0 3px rgb(228 35 19 / 0.18)',
            },
            colors: {
                'telkom-red': '#E42313',
                'telkom-red-dark': '#B91C1C',
                'telkom-red-soft': '#FEE2E2',
                'telkom-black': '#1D1D1B',
                'telkom-grey': '#706F6F',
                'telkom-grey-soft': '#F3F4F6',
                'telkom-white': '#FFFFFF',
                telkom: {
                    red: {
                        DEFAULT: '#E42313',
                        dark: '#B91C1C',
                        soft: '#FEE2E2',
                    },
                    black: '#1D1D1B',
                    grey: {
                        DEFAULT: '#706F6F',
                        soft: '#F3F4F6',
                    },
                    white: '#FFFFFF',
                },
                surface: '#FFFFFF',
                content: {
                    primary: '#1D1D1B',
                    secondary: '#475569',
                    muted: '#64748B',
                },
                status: {
                    success: {
                        DEFAULT: '#16A34A',
                        soft: '#DCFCE7',
                        foreground: '#166534',
                    },
                    warning: {
                        DEFAULT: '#D97706',
                        soft: '#FEF3C7',
                        foreground: '#92400E',
                    },
                    danger: {
                        DEFAULT: '#DC2626',
                        soft: '#FEE2E2',
                        foreground: '#991B1B',
                    },
                    info: {
                        DEFAULT: '#2563EB',
                        soft: '#DBEAFE',
                        foreground: '#1D4ED8',
                    },
                    neutral: {
                        DEFAULT: '#64748B',
                        soft: '#F1F5F9',
                        foreground: '#475569',
                    },
                },
                background: 'hsl(var(--background))',
                foreground: 'hsl(var(--foreground))',
                card: {
                    DEFAULT: 'hsl(var(--card))',
                    foreground: 'hsl(var(--card-foreground))',
                },
                popover: {
                    DEFAULT: 'hsl(var(--popover))',
                    foreground: 'hsl(var(--popover-foreground))',
                },
                primary: {
                    DEFAULT: 'hsl(var(--primary))',
                    foreground: 'hsl(var(--primary-foreground))',
                    dark: '#B91C1C',
                    soft: '#FEE2E2',
                },
                secondary: {
                    DEFAULT: 'hsl(var(--secondary))',
                    foreground: 'hsl(var(--secondary-foreground))',
                },
                muted: {
                    DEFAULT: 'hsl(var(--muted))',
                    foreground: 'hsl(var(--muted-foreground))',
                },
                accent: {
                    DEFAULT: 'hsl(var(--accent))',
                    foreground: 'hsl(var(--accent-foreground))',
                },
                destructive: {
                    DEFAULT: 'hsl(var(--destructive))',
                    foreground: 'hsl(var(--destructive-foreground))',
                },
                border: 'hsl(var(--border))',
                input: 'hsl(var(--input))',
                ring: 'hsl(var(--ring))',
                chart: {
                    '1': 'hsl(var(--chart-1))',
                    '2': 'hsl(var(--chart-2))',
                    '3': 'hsl(var(--chart-3))',
                    '4': 'hsl(var(--chart-4))',
                    '5': 'hsl(var(--chart-5))',
                },
            },
        },
    },

    plugins: [forms],
};
